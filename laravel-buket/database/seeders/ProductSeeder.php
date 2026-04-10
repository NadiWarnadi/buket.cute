<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat atau Pastikan Kategori Ada
        $categoriesData = [
            ['name' => 'Buket Mawar', 'slug' => 'buket-mawar', 'description' => 'Koleksi buket bunga mawar berbagai warna'],
            ['name' => 'Snack Bouquet', 'slug' => 'snack-bouquet', 'description' => 'Bouquet dengan kombinasi bunga dan snack'],
            ['name' => 'Hampers', 'slug' => 'hampers', 'description' => 'Paket lengkap bunga dan hadiah'],
            ['name' => 'Bouquet Wisuda', 'slug' => 'bouquet-wisuda', 'description' => 'Bouquet spesial untuk wisuda'],
        ];

        foreach ($categoriesData as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Ambil ID kategori setelah dibuat
        $cats = Category::pluck('id', 'slug');

        // 2. Data Produk
        $products = [
            [
                'category_id' => $cats['buket-mawar'],
                'name' => 'Buket Mawar Merah',
                'slug' => 'buket-mawar-merah',
                'description' => 'Buket mawar merah segar untuk ekspresi cinta',
                'price' => 75000,
                'stock' => 50,
                'is_active' => 1,
            ],
            [
                'category_id' => $cats['buket-mawar'],
                'name' => 'Buket Mawar Putih',
                'slug' => 'buket-mawar-putih',
                'description' => 'Buket mawar putih elegan untuk berbagai acara',
                'price' => 70000,
                'stock' => 45,
                'is_active' => 1,
            ],
            [
                'category_id' => $cats['snack-bouquet'],
                'name' => 'Snack Bouquet Premium',
                'slug' => 'snack-bouquet-premium',
                'description' => 'Kombinasi bunga dan snack premium dalam satu paket',
                'price' => 120000,
                'stock' => 30,
                'is_active' => 1,
            ],
            [
                'category_id' => $cats['hampers'],
                'name' => 'Hamper Ulang Tahun',
                'slug' => 'hamper-ulang-tahun',
                'description' => 'Hamper lengkap untuk ulang tahun dengan bunga dan hadiah',
                'price' => 150000,
                'stock' => 20,
                'is_active' => 1,
            ],
            [
                'category_id' => $cats['bouquet-wisuda'],
                'name' => 'Bouquet Wisuda Premium',
                'slug' => 'bouquet-wisuda-premium',
                'description' => 'Bouquet spesial wisuda dengan ucapan selamat',
                'price' => 125000,
                'stock' => 25,
                'is_active' => 1,
            ],
        ];

        // 3. Eksekusi Insert/Update Produk
        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['slug' => $productData['slug']], // Kunci pengecekan
                $productData
            );
        }

        $this->command->info('✅ Product Seeder berhasil disinkronisasi!');
    }
}
