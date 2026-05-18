<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class IngredientPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data bahan baku
        $ingredients = [
            ['name' => 'Mawar Merah', 'description' => 'Bunga mawar merah segar', 'stock' => 0, 'unit' => 'Tangkai', 'min_stock' => 20],
            ['name' => 'Baby Breath', 'description' => 'Isian rangkaian', 'stock' => 0, 'unit' => 'Ikat', 'min_stock' => 10],
            ['name' => 'Kertas Wrapping', 'description' => 'Kertas coklat atau motif', 'stock' => 0, 'unit' => 'Lembar', 'min_stock' => 30],
            ['name' => 'Sponge Basah', 'description' => 'Sponge untuk rangkaian', 'stock' => 0, 'unit' => 'Pcs', 'min_stock' => 10],
            ['name' => 'Pita Satin', 'description' => 'Pita hias', 'stock' => 0, 'unit' => 'Meter', 'min_stock' => 15],
            ['name' => 'Kawat Bunga', 'description' => 'Kawat penopang tangkai', 'stock' => 0, 'unit' => 'Batang', 'min_stock' => 25],
            ['name' => 'Daun Kelor', 'description' => 'Daun segar untuk dekorasi', 'stock' => 0, 'unit' => 'Ikat', 'min_stock' => 10],
        ];

        $insertedIngredients = [];
        foreach ($ingredients as $data) {
            $ingredient = Ingredient::create($data);
            $insertedIngredients[$ingredient->name] = $ingredient;
        }

        // Data pembelian (stok masuk)
        $purchases = [
            [
                'supplier' => 'Toko Bunga Indah',
                'items' => [
                    ['name' => 'Mawar Merah', 'quantity' => 100, 'price' => 5000],
                    ['name' => 'Baby Breath', 'quantity' => 20, 'price' => 8000],
                    ['name' => 'Kertas Wrapping', 'quantity' => 50, 'price' => 1500],
                ],
            ],
            [
                'supplier' => 'Supplier Alat Bunga',
                'items' => [
                    ['name' => 'Sponge Basah', 'quantity' => 30, 'price' => 3000],
                    ['name' => 'Pita Satin', 'quantity' => 40, 'price' => 2000],
                    ['name' => 'Kawat Bunga', 'quantity' => 60, 'price' => 1000],
                    ['name' => 'Daun Kelor', 'quantity' => 25, 'price' => 4000],
                ],
            ],
            [
                'supplier' => 'Agen Bunga Segar',
                'items' => [
                    ['name' => 'Mawar Merah', 'quantity' => 80, 'price' => 4500],
                    ['name' => 'Baby Breath', 'quantity' => 15, 'price' => 7500],
                ],
            ],
        ];

        foreach ($purchases as $purchaseData) {
            $total = 0;
            $items = [];
            foreach ($purchaseData['items'] as $itemData) {
                $ingredient = $insertedIngredients[$itemData['name']] ?? null;
                if (!$ingredient) continue;
                $qty = $itemData['quantity'];
                $price = $itemData['price'];
                $subtotal = $qty * $price;
                $total += $subtotal;
                $items[] = [
                    'ingredient' => $ingredient,
                    'quantity' => $qty,
                    'price' => $price,
                ];
            }

            if (empty($items)) continue;

            $purchase = Purchase::create([
                'supplier' => $purchaseData['supplier'],
                'total' => $total,
            ]);

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'ingredient_id' => $item['ingredient']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                // Update stok
                $item['ingredient']->increment('stock', $item['quantity']);

                // Catat stock movement
                StockMovement::create([
                    'ingredient_id' => $item['ingredient']->id,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'description' => "Pembelian dari {$purchaseData['supplier']}",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }
        }
    }
}