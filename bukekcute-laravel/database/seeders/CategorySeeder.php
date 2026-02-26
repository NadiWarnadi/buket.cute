<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Bucket Ucapan',
                'description' => 'Rangkaian bucket dengan ucapan spesial untuk berbagai acara',
            ],
            [
                'name' => 'Bucket Bunga',
                'description' => 'Bucket bunga segar pilihan dengan berbagai warna',
            ],
            [
                'name' => 'Bucket Cokelat',
                'description' => 'Bucket berisi berbagai macam cokelat dan permen premium',
            ],
            [
                'name' => 'Bucket Snack',
                'description' => 'Bucket dengan berbagai pilihan snack dan camilan enak',
            ],
            [
                'name' => 'Bucket Custom',
                'description' => 'Bucket yang dapat disesuaikan dengan kebutuhan Anda',
            ],
        ];

        foreach ($categories as $category) {
            // Auto-generate slug dari name
            $category['slug'] = Str::slug($category['name']);
            Category::create($category);
        }
    }
}
