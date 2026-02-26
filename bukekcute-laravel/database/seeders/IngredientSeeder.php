<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            [
                'name' => 'Tepung Terigu',
                'description' => 'Tepung terigu premium untuk kue',
                'stock' => 50,
                'unit' => 'kg',
                'min_stock' => 10,
            ],
            [
                'name' => 'Gula Putih',
                'description' => 'Gula putih kristal halus',
                'stock' => 25,
                'unit' => 'kg',
                'min_stock' => 5,
            ],
            [
                'name' => 'Telur',
                'description' => 'Telur ayam kampung segar',
                'stock' => 120,
                'unit' => 'pcs',
                'min_stock' => 30,
            ],
            [
                'name' => 'Mentega',
                'description' => 'Mentega premium untuk kue',
                'stock' => 15,
                'unit' => 'kg',
                'min_stock' => 5,
            ],
            [
                'name' => 'Susu Cair',
                'description' => 'Susu cair pasteurisasi',
                'stock' => 20,
                'unit' => 'liter',
                'min_stock' => 5,
            ],
            [
                'name' => 'Cokelat Bubuk',
                'description' => 'Cokelat bubuk murni',
                'stock' => 8,
                'unit' => 'kg',
                'min_stock' => 2,
            ],
            [
                'name' => 'Vanili Pasta',
                'description' => 'Vanili pasta asli',
                'stock' => 2,
                'unit' => 'liter',
                'min_stock' => 0.5,
            ],
            [
                'name' => 'Baking Soda',
                'description' => 'Soda kue berkualitas tinggi',
                'stock' => 5,
                'unit' => 'kg',
                'min_stock' => 1,
            ],
            [
                'name' => 'Minyak Kelapa Sawit',
                'description' => 'Minyak kelapa sawit murni',
                'stock' => 30,
                'unit' => 'liter',
                'min_stock' => 10,
            ],
            [
                'name' => 'Garam',
                'description' => 'Garam dapur halus',
                'stock' => 10,
                'unit' => 'kg',
                'min_stock' => 2,
            ],
            [
                'name' => 'Krim Kental',
                'description' => 'Krim kental untuk hiasan',
                'stock' => 12,
                'unit' => 'liter',
                'min_stock' => 3,
            ],
            [
                'name' => 'Gula Pasir',
                'description' => 'Gula pasir premium',
                'stock' => 40,
                'unit' => 'kg',
                'min_stock' => 8,
            ],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}
