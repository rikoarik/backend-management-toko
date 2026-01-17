<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Makanan & Minuman',
                'description' => 'Berbagai jenis makanan dan minuman siap saji'
            ],
            [
                'name' => 'Elektronik',
                'description' => 'Peralatan elektronik dan gadget'
            ],
            [
                'name' => 'Pakaian',
                'description' => 'Pakaian pria, wanita, dan anak-anak'
            ],
            [
                'name' => 'Kesehatan & Kecantikan',
                'description' => 'Produk kesehatan dan kecantikan'
            ],
            [
                'name' => 'Rumah Tangga',
                'description' => 'Perlengkapan dan peralatan rumah tangga'
            ],
            [
                'name' => 'Olahraga',
                'description' => 'Peralatan dan perlengkapan olahraga'
            ],
            [
                'name' => 'Buku & Alat Tulis',
                'description' => 'Buku, majalah, dan alat tulis'
            ],
            [
                'name' => 'Mainan & Hobi',
                'description' => 'Mainan anak dan perlengkapan hobi'
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
