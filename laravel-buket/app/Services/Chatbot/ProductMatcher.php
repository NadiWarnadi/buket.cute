<?php

namespace App\Services\Chatbot;

use App\Models\Product;

class ProductMatcher
{
    public function match(string $query): array
    {
        $query = strtolower(trim($query));

        // Exact match (nama produk mengandung query)
        $exact = Product::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->first();

        if ($exact) {
            return ['matched' => true, 'product' => $exact];
        }

        // Fuzzy search berdasarkan similarity per kata
        $products = Product::where('is_active', true)->get();
        $candidates = [];
        foreach ($products as $product) {
            $similarity = $this->calculateSimilarity($query, $product->name);
            if ($similarity > 0.3) {
                $candidates[] = $product;
            }
        }

        if (count($candidates) === 1) {
            return ['matched' => true, 'product' => $candidates[0]];
        }

        if (count($candidates) > 1) {
            // Urutkan berdasarkan similarity tertinggi
            usort($candidates, function ($a, $b) use ($query) {
                return $this->calculateSimilarity($query, $b->name) <=> $this->calculateSimilarity($query, $a->name);
            });
            return ['matched' => false, 'candidates' => collect($candidates)->take(5)];
        }

        return ['matched' => false, 'candidates' => []];
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
}