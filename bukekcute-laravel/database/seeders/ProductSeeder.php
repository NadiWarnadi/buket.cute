<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Bucket Ucapan
            [
                'category_id' => 1,
                'name' => 'Bucket Happy Birthday',
                'slug' => 'bucket-happy-birthday',
                'description' => 'Bucket dengan ucapan Happy Birthday yang cantik dan penuh warna',
                'price' => 150000,
                'stock' => 10,
                'is_active' => true
            ],
            [
                'category_id' => 1,
                'name' => 'Bucket Selamat Datang',
                'slug' => 'bucket-selamat-datang',
                'description' => 'Bucket sambutan untuk menyambut kedatangan tamu istimewa',
                'price' => 125000,
                'stock' => 8,
                'is_active' => true
            ],
            [
                'category_id' => 1,
                'name' => 'Bucket Selamat Menikah',
                'slug' => 'bucket-selamat-menikah',
                'description' => 'Bucket spesial untuk pasangan pengantin baru',
                'price' => 200000,
                'stock' => 5,
                'is_active' => true
            ],

            // Bucket Bunga
            [
                'category_id' => 2,
                'name' => 'Bucket Mawar Merah',
                'slug' => 'bucket-mawar-merah',
                'description' => 'Bucket berisi mawar merah segar pilihan berkualitas',
                'price' => 180000,
                'stock' => 12,
                'is_active' => true
            ],
            [
                'category_id' => 2,
                'name' => 'Bucket Bunga Mix',
                'slug' => 'bucket-bunga-mix',
                'description' => 'Kombinasi bunga-bunga pilihan dalam satu bucket',
                'price' => 160000,
                'stock' => 15,
                'is_active' => true
            ],
            [
                'category_id' => 2,
                'name' => 'Bucket Tulip Orange',
                'slug' => 'bucket-tulip-orange',
                'description' => 'Bucket dengan tulip warna orange yang cerah dan segar',
                'price' => 170000,
                'stock' => 10,
                'is_active' => true
            ],

            // Bucket Cokelat
            [
                'category_id' => 3,
                'name' => 'Bucket Cokelat Premium',
                'slug' => 'bucket-cokelat-premium',
                'description' => 'Bucket cokelat premium lokal dan import',
                'price' => 250000,
                'stock' => 8,
                'is_active' => true
            ],
            [
                'category_id' => 3,
                'name' => 'Bucket Ferrero Mix',
                'slug' => 'bucket-ferrero-mix',
                'description' => 'Bucket berisi berbagai varian cokelat Ferrero',
                'price' => 220000,
                'stock' => 6,
                'is_active' => true
            ],

            // Bucket Snack
            [
                'category_id' => 4,
                'name' => 'Bucket Jajanan Lokal',
                'slug' => 'bucket-jajanan-lokal',
                'description' => 'Bucket dengan berbagai snack dan jajanan lokal pilihan',
                'price' => 120000,
                'stock' => 20,
                'is_active' => true
            ],
            [
                'category_id' => 4,
                'name' => 'Bucket Cemilan Sehat',
                'slug' => 'bucket-cemilan-sehat',
                'description' => 'Bucket berisi cemilan sehat dan bergizi',
                'price' => 140000,
                'stock' => 12,
                'is_active' => true
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
