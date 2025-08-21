<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar kategori yang akan kita masukkan
        $categories = [
            'Bitcoin & Kripto',
            'Investasi',
            'Keuangan Pribadi',
            'Panduan Pemula',
            'Teknologi Blockchain',
        ];

        // Looping untuk membuat setiap kategori
        foreach ($categories as $categoryName) {
            Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName) . '-' . Str::random(5),
            ]);
        }
    }
}
