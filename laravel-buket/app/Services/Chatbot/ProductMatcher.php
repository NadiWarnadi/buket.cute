<?php

namespace App\Services\Chatbot;

use App\Models\Product;

class ProductMatcher
{
    // Threshold untuk fuzzy matching - produk harus memiliki similarity minimal ini
    const MIN_SIMILARITY = 0.6; // Naikkan dari 0.3 ke 0.6 (60%)

    /**
     * Deteksi apakah query adalah pertanyaan catalog (bukan order spesifik)
     */
    private function isCatalogQuestion(string $query): bool
    {
        $catalogKeywords = [
            'apa saja', 'ada apa', 'apa aja', 'ada buket apa',
            'katalog', 'list', 'menu', 'lihat', 'daftar produk',
            'pilihan', 'opsi', 'ada apa aja', 'punya apa',
            'rekomendasi', 'suggest'
        ];

        $query = strtolower(trim($query));
        foreach ($catalogKeywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    public function match(string $query): array
    {
        // Cek dulu apakah ini pertanyaan katalog
        if ($this->isCatalogQuestion($query)) {
            return ['matched' => false, 'candidates' => [], 'is_catalog_question' => true];
        }

        $query = strtolower(trim($query));

        // Exact match (nama produk mengandung query) - hanya jika query cukup panjang & specific
        if (strlen($query) >= 3) {
            $exact = Product::where('is_active', true)
                ->where('name', 'like', "%{$query}%")
                ->first();

            if ($exact && strlen($query) >= 4) { // Exact match memerlukan minimal 4 karakter
                return ['matched' => true, 'product' => $exact];
            }
        }

        // Fuzzy search berdasarkan similarity per kata
        $products = Product::where('is_active', true)->get();
        $candidates = [];
        foreach ($products as $product) {
            $similarity = $this->calculateSimilarity($query, $product->name);
            // Naikkan threshold dari 0.3 ke 0.6
            if ($similarity > self::MIN_SIMILARITY) {
                $candidates[] = [
                    'product' => $product,
                    'similarity' => $similarity
                ];
            }
        }

        // Jika confidence terlalu tinggi untuk 1 match, return saja
        if (count($candidates) === 1 && $candidates[0]['similarity'] >= 0.75) {
            return ['matched' => true, 'product' => $candidates[0]['product']];
        }

        if (count($candidates) > 1) {
            // Urutkan berdasarkan similarity tertinggi
            usort($candidates, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
            return [
                'matched' => false,
                'candidates' => collect($candidates)->take(5)->pluck('product')
            ];
        }

        // Tidak cukup match bahkan dengan threshold rendah
        return ['matched' => false, 'candidates' => [], 'is_catalog_question' => false];
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
}