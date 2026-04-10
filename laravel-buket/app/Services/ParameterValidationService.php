<?php

namespace App\Services;

use App\Models\Product;

class ParameterValidationService
{
    /**
     * Define required parameters for order
     */
    const REQUIRED_PARAMETERS = [
        'customer_name',
        'customer_address',
        'product_id',
        'quantity',
    ];

    /**
     * Validate if all required parameters are filled
     * 
     * @param array $data Order data
     * @return array ['valid' => bool, 'missing' => [], 'errors' => []]
     */
    public function validateOrderParameters(array $data): array
    {
        $missing = [];
        $errors = [];

        // Check required fields
        foreach (self::REQUIRED_PARAMETERS as $param) {
            if (empty($data[$param])) {
                $missing[] = $param;
            }
        }

        // Validate data types & ranges
        if (!empty($data['quantity']) && ((int)$data['quantity'] < 1 || (int)$data['quantity'] > 1000)) {
            $errors[] = 'Quantity harus antara 1-1000 biji';
        }

        if (!empty($data['product_id'])) {
            $product = Product::find($data['product_id']);
            if (!$product) {
                $errors[] = 'Produk tidak ditemukan';
            } elseif ($product->stock < ($data['quantity'] ?? 0)) {
                $errors[] = "Stock produk tidak cukup (tersedia: {$product->stock})";
            }
        }

        if (!empty($data['customer_address']) && strlen($data['customer_address']) < 5) {
            $errors[] = 'Alamat terlalu pendek (minimal 5 karakter)';
        }

        return [
            'valid' => empty($missing) && empty($errors),
            'missing' => $missing,
            'errors' => $errors,
        ];
    }

    /**
     * Get user-friendly labels untuk missing parameters
     * 
     * @param array $missing
     * @return array
     */
    public function getMissingParameterLabels(array $missing): array
    {
        $labels = [
            'customer_name' => 'Nama Anda',
            'customer_address' => 'Alamat pengiriman',
            'product_id' => 'Produk yang dipesan',
            'quantity' => 'Jumlah pesanan',
            'customer_phone' => 'Nomor WhatsApp',
        ];

        return array_map(fn($param) => $labels[$param] ?? $param, $missing);
    }

    /**
     * Generate follow-up question untuk parameter yang hilang
     * 
     * @param array $missing
     * @param array $existingData
     * @return string|null
     */
    public function generateFollowUpQuestion(array $missing, array $existingData = []): ?string
    {
        if (empty($missing)) {
            return null;
        }

        // Urutkan: prioritas pertanyaan
        $priority = ['customer_name', 'product_id', 'quantity', 'customer_address'];
        $firstMissing = null;

        foreach ($priority as $param) {
            if (in_array($param, $missing)) {
                $firstMissing = $param;
                break;
            }
        }

        if (!$firstMissing) {
            $firstMissing = $missing[0] ?? null;
        }

        $questions = [
            'customer_name' => 'Maaf ka, siapa nama Anda untuk pesanan ini?',
            'product_id' => 'Produk "{product_name}" belum kami kenali. Bisa sebutkan nama produk yang tersedia? (contoh: Buket Merah, Hampers Romantis, dsb)',
            'quantity' => 'Berapa jumlah yang mau dipesan? (dalam biji/buket)',
            'customer_address' => 'Berapa alamat lengkap pengiriman Anda?',
            'customer_phone' => 'Berapa nomor WhatsApp untuk konfirmasi pesanan?',
        ];

        $question = $questions[$firstMissing] ?? null;

        // Replace variables
        if ($question && isset($existingData['product_name'])) {
            $question = str_replace('{product_name}', $existingData['product_name'], $question);
        }

        return $question;
    }

    /**
     * Get next step dalam order flow
     * 
     * @param array $data Current data
     * @return string Step name (collecting_name, collecting_product, collecting_qty, collecting_address, confirming, completed)
     */
    public function getNextStep(array $data): string
    {
        $steps = [
            'customer_name' => 'collecting_name',
            'product_id' => 'collecting_product',
            'quantity' => 'collecting_quantity',
            'customer_address' => 'collecting_address',
        ];

        foreach ($steps as $param => $step) {
            if (empty($data[$param])) {
                return $step;
            }
        }

        return 'confirming'; // Semua data terkumpul
    }

    /**
     * Format order summary untuk display
     */
    public function formatOrderSummary(array $data): string
    {
        $summary = [];

        if (!empty($data['customer_name'])) {
            $summary[] = "Nama: {$data['customer_name']}";
        }

        if (!empty($data['product_name'])) {
            $summary[] = "Produk: {$data['product_name']}";
        }

        if (!empty($data['quantity'])) {
            $summary[] = "Jumlah: {$data['quantity']} biji";
        }

        if (!empty($data['price']) && !empty($data['quantity'])) {
            $total = $data['price'] * $data['quantity'];
            $summary[] = "Harga: Rp " . number_format($total, 0, ',', '.');
        }

        if (!empty($data['customer_address'])) {
            $summary[] = "Alamat: {$data['customer_address']}";
        }

        return implode("\n", $summary);
    }
}
