<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ParameterExtractionService
{
    /**
     * Extract order parameters from message text
     * Detects: product name, quantity, address
     * * @param string $message
     * @param array $existingData Previous extracted data
     * @return array ['product_name', 'quantity', 'address', 'notes']
     */
    public function extractParameters(string $message, array $existingData = []): array
    {
        $extracted = [];

        // Extract quantity (e.g., "2 biji", "3 buket", "2 pcs")
        $extracted['quantity'] = $this->extractQuantity($message);

        // Extract address (keywords: alamat, kota, jalan, dst, no)
        $extracted['address'] = $this->extractAddress($message);

        // Extract product name using custom tokenization & database matching
        $extracted['product_data'] = $this->extractProductName($message);

        // Extract customer name if mentioned
        $extracted['customer_name'] = $this->extractCustomerName($message);

        // Extract other notes
        $extracted['raw_message'] = $message;

        Log::debug('Extracted parameters', [
            'message' => $message,
            'extracted' => $extracted,
        ]);

        return $extracted;
    }

    /**
     * Extract quantity from message
     * Patterns: "2 biji", "3 buket", "4 pcs", "lima bunga", etc
     */
    private function extractQuantity(string $message): ?int
    {
        $message = strtolower($message);

        // Number word mapping
        $numberWords = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'satu puluh' => 10, 'dua puluh' => 20, 'tiga puluh' => 30,
        ];

        // Quantity units
        $units = '(biji|buket|pcs|bunga|tangkai|ikat|set|piece|qty)';

        // Pattern: "2 biji" or "dua biji"
        $patterns = [
            '/(\d+)\s*' . $units . '/i',
            '/(' . implode('|', array_keys($numberWords)) . ')\s*' . $units . '/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                if (is_numeric($matches[1])) {
                    return (int) $matches[1];
                } else {
                    return $numberWords[$matches[1]] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Extract address from message
     * Patterns: "alamat jakarta", "kota bandung jl. merdeka no 23", etc
     */
    private function extractAddress(string $message): ?string
    {
        $message = strtolower($message);

        // Keywords untuk address
        $addressKeywords = ['alamat', 'kota', 'jl\.', 'jalan', 'jln', 'desa', 'kelurahan', 'no\.', 'no', 'dst'];

        // Try pattern: "keyword + content"
        $pattern = '/(?:' . implode('|', $addressKeywords) . ')\s+([^.!?]+?)(?:\s+(?:dan|yang|itu|berapaan|harga)|$)/i';

        if (preg_match($pattern, $message, $matches)) {
            $address = trim($matches[1]);
            $address = preg_replace('/\s+/', ' ', $address); // Normalize spaces
            return !empty($address) ? $address : null;
        }

        return null;
    }

    /**
     * Extract product name and find matching product from database
     * Uses lightweight tokenization & word-by-word intersection matching
     * 100% Local, RAM efficient, and safe from memory spikes
     */
    private function extractProductName(string $message): ?array
    {
        $message = strtolower(trim($message));

        // 1. Bersihkan kata-kata sampah (Stopwords) khas chat WhatsApp Indonesia
        $stopwords = [
            'ada', 'apa', 'aja', 'ya', 'kak', 'dong', 'sis', 'gan', 'mau', 'pesen', 
            'pesan', 'beli', 'halo', 'min', 'bisa', 'kirim', 'ke', 'yg', 'yang', 'di',
            'permisi', 'pagi', 'siang', 'sore', 'malam', 'order', 'nih', 'itunya'
        ];

        // Pecah kalimat chat user menjadi array kata (Tokenization)
        $messageWords = preg_split('/\s+/', $message, -1, PREG_SPLIT_NO_EMPTY);

        // Filter kata-kata sampah agar tersisa kata kunci produk murni
        $keywords = array_diff($messageWords, $stopwords);

        if (empty($keywords)) {
            return null;
        }

        // 2. Load semua produk aktif (Eager loading category tetap dipertahankan)
        $products = Product::where('is_active', true)
            ->with('category')
            ->get();

        if ($products->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $highestSimilarity = 0.4; // Threshold minimal kemiripan kata (40%)

        foreach ($products as $product) {
            // Pecah nama produk di database menjadi potongan kata
            $productNameLower = strtolower($product->name);
            $productWords = preg_split('/\s+/', $productNameLower, -1, PREG_SPLIT_NO_EMPTY);
            
            // Satukan kata dari kategori jika tersedia untuk menambah akurasi
            if ($product->category) {
                $categoryWords = preg_split('/\s+/', strtolower($product->category->name), -1, PREG_SPLIT_NO_EMPTY);
                $productWords = array_merge($productWords, $categoryWords);
            }

            // 3. Hitung berapa banyak kata dari chat user yang COCOK dengan data produk
            $matchedWords = 0;
            foreach ($keywords as $keyword) {
                // Abaikan kata kunci jika berupa angka murni (karena kemungkinan itu data Quantity)
                if (is_numeric($keyword)) {
                    continue;
                }

                foreach ($productWords as $prodWord) {
                    // Cek kesamaan kata dengan toleransi typo 1 huruf atau substring match
                    $distance = levenshtein($keyword, $prodWord);
                    if ($distance <= 1 || strpos($prodWord, $keyword) !== false || strpos($keyword, $prodWord) !== false) {
                        $matchedWords++;
                        break; // Keluar ke kata kunci user berikutnya
                    }
                }
            }

            // 4. Hitung persentase skor kemiripan (Intersection Over Max Words)
            $totalUniqueWords = max(count($keywords), count($productWords));
            
            if ($totalUniqueWords > 0) {
                $similarity = $matchedWords / $totalUniqueWords;

                // Cari produk dengan skor kecocokan tertinggi
                if ($similarity > $highestSimilarity) {
                    $highestSimilarity = $similarity;
                    $bestMatch = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'category' => $product->category ? $product->category->name : 'Uncategorized',
                        'price' => $product->price,
                        'stock' => $product->stock,
                        'similarity' => $similarity,
                    ];
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Extract customer name from message
     * Simple heuristic: first capitalized word or after "nama" keyword
     */
    private function extractCustomerName(string $message): ?string
    {
        // Pattern: "nama saya ... " atau "saya ... "
        $patterns = [
            '/nama\s+(?:saya\s+)?([A-Za-z\s]+?)(?:\s+(?:atau|alias)|,|\.|$)/i',
            '/panggil\s+(?:saja\s+)?([A-Za-z\s]+?)(?:\s+(?:atau|alias)|,|\.|$)/i',
            '/mau\s+pesan|saya\s+([A-Za-z]+)/i', // Fallback: first name after saya
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $name = trim($matches[1] ?? '');
                if (!empty($name) && strlen($name) > 1) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Parse multi-product orders (jika ada)
     * E.g., "2 buket merah, 3 buket putih"
     */
    public function extractMultipleProducts(string $message): array
    {
        // Split by common separators
        $separator = preg_split('/[,;dan]/i', $message);

        $products = [];
        foreach ($separator as $part) {
            $extracted = $this->extractParameters($part);
            if (!empty($extracted['product_data'])) {
                $products[] = [
                    'product_data' => $extracted['product_data'],
                    'quantity' => $extracted['quantity'] ?? 1,
                ];
            }
        }

        return $products;
    }
}