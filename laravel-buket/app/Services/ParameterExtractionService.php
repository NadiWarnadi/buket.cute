<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ParameterExtractionService
{
    /**
     * Extract order parameters from message text
     * Detects: product name, quantity, address
     * 
     * @param string $message
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

        // Extract product name using fuzzy matching against database
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
     * Uses fuzzy matching against product list
     * IMPORTANT: Uses eager loading to avoid N+1 queries
     */
    private function extractProductName(string $message): ?array
    {
        $message = strtolower(trim($message));

        // Load semua produk aktif (dengan eager loading)
        $products = Product::where('is_active', true)
            ->with('category') // Eager load category untuk избежать N+1
            ->get();

        if ($products->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $highestSimilarity = 0.3; // Minimum similarity threshold

        // Cari product yang paling matching
        foreach ($products as $product) {
            // Kombinasi: nama produk + kategori untuk lebih flexible
            $searchTerms = [
                $product->name,
                $product->category->name . ' ' . $product->name,
                $product->slug,
            ];

            foreach ($searchTerms as $term) {
                $similarity = $this->calculateSimilarity($message, $term);

                if ($similarity > $highestSimilarity) {
                    $highestSimilarity = $similarity;
                    $bestMatch = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'category' => $product->category->name,
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
            '/nama\s+(?:saya\s+)?(.+?)(?:\s+(?:atau|alias)|,|\.|$)/i',
            '/panggil\s+(?:saja\s+)?(.+?)(?:\s+(?:atau|alias)|,|\.|$)/i',
            '/mau\s+pesan|saya\s+(.+?)(?:\s|$)/i', // Fallback: first name after saya
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
     * Calculate similarity between message and search term
     * Using multiple techniques: substring, levenshtein, keyword overlap
     */
    private function calculateSimilarity(string $message, string $term): float
    {
        $message = strtolower(trim($message));
        $term = strtolower(trim($term));

        if ($message === $term) {
            return 1.0;
        }

        $scores = [];

        // 1. Substring match (high weight)
        if (strpos($message, $term) !== false) {
            $scores[] = 0.9;
        } elseif (strpos($term, $message) !== false) {
            $scores[] = 0.8;
        }

        // 2. Levenshtein distance for typo detection
        $messageWords = preg_split('/\s+/', $message, -1, PREG_SPLIT_NO_EMPTY);
        $termWords = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($termWords as $termWord) {
            foreach ($messageWords as $msgWord) {
                if (strlen($msgWord) > 2) { // Ignore very short words
                    $maxLen = max(strlen($msgWord), strlen($termWord));
                    $distance = levenshtein($msgWord, $termWord);
                    $wordSimilarity = 1 - ($distance / $maxLen);

                    if ($wordSimilarity > 0.7) {
                        $scores[] = $wordSimilarity;
                    }
                }
            }
        }

        return !empty($scores) ? (array_sum($scores) / count($scores)) : 0;
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
