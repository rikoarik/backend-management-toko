<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->error('Please run CategorySeeder first!');
            return;
        }

        // Daftar produk template berdasarkan kategori
        $productTemplates = [
            'Makanan & Minuman' => [
                ['name' => 'Nasi Goreng', 'price' => 15000, 'stock' => 50],
                ['name' => 'Mie Ayam', 'price' => 12000, 'stock' => 45],
                ['name' => 'Bakso', 'price' => 13000, 'stock' => 40],
                ['name' => 'Sate Ayam', 'price' => 18000, 'stock' => 35],
                ['name' => 'Gado-Gado', 'price' => 10000, 'stock' => 30],
                ['name' => 'Soto Ayam', 'price' => 14000, 'stock' => 38],
                ['name' => 'Ayam Goreng', 'price' => 20000, 'stock' => 42],
                ['name' => 'Rendang', 'price' => 25000, 'stock' => 28],
                ['name' => 'Teh Botol', 'price' => 5000, 'stock' => 100],
                ['name' => 'Kopi Hitam', 'price' => 8000, 'stock' => 80],
                ['name' => 'Jus Jeruk', 'price' => 10000, 'stock' => 60],
                ['name' => 'Es Teh', 'price' => 4000, 'stock' => 90],
                ['name' => 'Air Mineral', 'price' => 3000, 'stock' => 150],
                ['name' => 'Cappuccino', 'price' => 15000, 'stock' => 55],
                ['name' => 'Milkshake', 'price' => 18000, 'stock' => 40],
            ],
            'Elektronik' => [
                ['name' => 'Mouse Wireless', 'price' => 75000, 'stock' => 25],
                ['name' => 'Keyboard Mechanical', 'price' => 350000, 'stock' => 15],
                ['name' => 'Webcam HD', 'price' => 250000, 'stock' => 18],
                ['name' => 'Power Bank 10000mAh', 'price' => 150000, 'stock' => 30],
                ['name' => 'Headset Gaming', 'price' => 450000, 'stock' => 12],
                ['name' => 'Kabel USB Type-C', 'price' => 25000, 'stock' => 80],
                ['name' => 'Speaker Bluetooth', 'price' => 200000, 'stock' => 22],
                ['name' => 'Charger Fast Charging', 'price' => 85000, 'stock' => 35],
                ['name' => 'Earphone TWS', 'price' => 180000, 'stock' => 28],
                ['name' => 'Flashdisk 32GB', 'price' => 45000, 'stock' => 50],
                ['name' => 'Mousepad Gaming', 'price' => 65000, 'stock' => 40],
                ['name' => 'USB Hub 4 Port', 'price' => 95000, 'stock' => 20],
                ['name' => 'Laptop Stand', 'price' => 120000, 'stock' => 18],
                ['name' => 'Monitor LED 24 inch', 'price' => 1500000, 'stock' => 8],
                ['name' => 'Smartwatch', 'price' => 850000, 'stock' => 10],
            ],
            'Pakaian' => [
                ['name' => 'Kaos Polos Hitam', 'price' => 45000, 'stock' => 60],
                ['name' => 'Kaos Polos Putih', 'price' => 45000, 'stock' => 65],
                ['name' => 'Kemeja Formal', 'price' => 120000, 'stock' => 35],
                ['name' => 'Celana Jeans', 'price' => 150000, 'stock' => 40],
                ['name' => 'Celana Chino', 'price' => 135000, 'stock' => 38],
                ['name' => 'Jaket Hoodie', 'price' => 180000, 'stock' => 30],
                ['name' => 'Sweater Rajut', 'price' => 95000, 'stock' => 32],
                ['name' => 'Dress Casual', 'price' => 165000, 'stock' => 25],
                ['name' => 'Rok Mini', 'price' => 75000, 'stock' => 28],
                ['name' => 'Kemeja Flanel', 'price' => 85000, 'stock' => 42],
                ['name' => 'Kaos Polo', 'price' => 65000, 'stock' => 48],
                ['name' => 'Celana Pendek', 'price' => 55000, 'stock' => 50],
                ['name' => 'Daster', 'price' => 50000, 'stock' => 35],
                ['name' => 'Gamis', 'price' => 200000, 'stock' => 20],
                ['name' => 'Blazer', 'price' => 250000, 'stock' => 15],
            ],
            'Kesehatan & Kecantikan' => [
                ['name' => 'Face Wash', 'price' => 35000, 'stock' => 45],
                ['name' => 'Moisturizer', 'price' => 85000, 'stock' => 38],
                ['name' => 'Sunscreen SPF 50', 'price' => 95000, 'stock' => 32],
                ['name' => 'Lip Balm', 'price' => 25000, 'stock' => 60],
                ['name' => 'Masker Wajah', 'price' => 15000, 'stock' => 80],
                ['name' => 'Vitamin C Serum', 'price' => 120000, 'stock' => 28],
                ['name' => 'Hand Sanitizer', 'price' => 18000, 'stock' => 70],
                ['name' => 'Body Lotion', 'price' => 45000, 'stock' => 42],
                ['name' => 'Parfum', 'price' => 150000, 'stock' => 25],
                ['name' => 'Deodorant', 'price' => 22000, 'stock' => 55],
                ['name' => 'Shampoo Anti Ketombe', 'price' => 32000, 'stock' => 48],
                ['name' => 'Conditioner', 'price' => 35000, 'stock' => 45],
                ['name' => 'Sabun Mandi', 'price' => 12000, 'stock' => 90],
                ['name' => 'Pasta Gigi', 'price' => 15000, 'stock' => 85],
                ['name' => 'Sikat Gigi', 'price' => 8000, 'stock' => 100],
            ],
            'Rumah Tangga' => [
                ['name' => 'Piring Keramik Set', 'price' => 125000, 'stock' => 22],
                ['name' => 'Gelas Kaca Set', 'price' => 85000, 'stock' => 28],
                ['name' => 'Sendok Garpu Set', 'price' => 45000, 'stock' => 35],
                ['name' => 'Wajan Teflon', 'price' => 180000, 'stock' => 18],
                ['name' => 'Panci Stainless', 'price' => 220000, 'stock' => 15],
                ['name' => 'Teko Listrik', 'price' => 165000, 'stock' => 20],
                ['name' => 'Blender', 'price' => 250000, 'stock' => 12],
                ['name' => 'Rice Cooker', 'price' => 350000, 'stock' => 10],
                ['name' => 'Vacuum Cleaner', 'price' => 850000, 'stock' => 8],
                ['name' => 'Setrika Listrik', 'price' => 180000, 'stock' => 16],
                ['name' => 'Dispenser Air', 'price' => 450000, 'stock' => 9],
                ['name' => 'Ember Plastik', 'price' => 25000, 'stock' => 50],
                ['name' => 'Sapu Lidi', 'price' => 15000, 'stock' => 60],
                ['name' => 'Pel Lantai', 'price' => 35000, 'stock' => 45],
                ['name' => 'Tempat Sampah', 'price' => 55000, 'stock' => 30],
            ],
            'Olahraga' => [
                ['name' => 'Matras Yoga', 'price' => 95000, 'stock' => 25],
                ['name' => 'Dumbell 5kg', 'price' => 150000, 'stock' => 18],
                ['name' => 'Bola Basket', 'price' => 180000, 'stock' => 15],
                ['name' => 'Bola Sepak', 'price' => 165000, 'stock' => 16],
                ['name' => 'Raket Badminton', 'price' => 220000, 'stock' => 12],
                ['name' => 'Shuttlecock', 'price' => 35000, 'stock' => 40],
                ['name' => 'Sepatu Lari', 'price' => 350000, 'stock' => 20],
                ['name' => 'Kaos Olahraga', 'price' => 65000, 'stock' => 35],
                ['name' => 'Celana Training', 'price' => 85000, 'stock' => 32],
                ['name' => 'Resistance Band', 'price' => 55000, 'stock' => 28],
                ['name' => 'Botol Minum Olahraga', 'price' => 45000, 'stock' => 50],
                ['name' => 'Skipping Rope', 'price' => 38000, 'stock' => 42],
                ['name' => 'Sarung Tangan Gym', 'price' => 75000, 'stock' => 22],
                ['name' => 'Handuk Olahraga', 'price' => 35000, 'stock' => 48],
                ['name' => 'Tas Olahraga', 'price' => 125000, 'stock' => 18],
            ],
            'Buku & Alat Tulis' => [
                ['name' => 'Novel Fiksi', 'price' => 75000, 'stock' => 30],
                ['name' => 'Buku Pelajaran', 'price' => 95000, 'stock' => 25],
                ['name' => 'Notebook A5', 'price' => 25000, 'stock' => 60],
                ['name' => 'Pensil 2B', 'price' => 3000, 'stock' => 150],
                ['name' => 'Pulpen Gel', 'price' => 5000, 'stock' => 120],
                ['name' => 'Penghapus', 'price' => 2000, 'stock' => 180],
                ['name' => 'Penggaris 30cm', 'price' => 8000, 'stock' => 90],
                ['name' => 'Spidol Whiteboard', 'price' => 12000, 'stock' => 70],
                ['name' => 'Stabilo Highlighter', 'price' => 15000, 'stock' => 65],
                ['name' => 'Lem Kertas', 'price' => 10000, 'stock' => 55],
                ['name' => 'Gunting', 'price' => 18000, 'stock' => 48],
                ['name' => 'Stapler', 'price' => 22000, 'stock' => 42],
                ['name' => 'Kertas HVS A4', 'price' => 45000, 'stock' => 38],
                ['name' => 'Map Plastik', 'price' => 8000, 'stock' => 80],
                ['name' => 'Binder Clip', 'price' => 6000, 'stock' => 95],
            ],
            'Mainan & Hobi' => [
                ['name' => 'Lego Set', 'price' => 250000, 'stock' => 15],
                ['name' => 'Boneka Teddy Bear', 'price' => 85000, 'stock' => 28],
                ['name' => 'Mobil Remote Control', 'price' => 180000, 'stock' => 18],
                ['name' => 'Puzzle 1000 Pieces', 'price' => 95000, 'stock' => 22],
                ['name' => 'Drone Mini', 'price' => 450000, 'stock' => 10],
                ['name' => 'Rubik Cube', 'price' => 45000, 'stock' => 35],
                ['name' => 'Action Figure', 'price' => 125000, 'stock' => 20],
                ['name' => 'Board Game', 'price' => 165000, 'stock' => 16],
                ['name' => 'Kartu Trading', 'price' => 55000, 'stock' => 40],
                ['name' => 'Model Kit Gundam', 'price' => 285000, 'stock' => 12],
                ['name' => 'Sepeda Anak', 'price' => 850000, 'stock' => 8],
                ['name' => 'Mainan Masak-masakan', 'price' => 120000, 'stock' => 18],
                ['name' => 'Slime DIY Kit', 'price' => 65000, 'stock' => 30],
                ['name' => 'Yo-yo', 'price' => 25000, 'stock' => 45],
                ['name' => 'Gitar Mainan', 'price' => 95000, 'stock' => 14],
            ],
        ];

        // Generate products untuk setiap kategori
        foreach ($categories as $category) {
            if (isset($productTemplates[$category->name])) {
                $products = $productTemplates[$category->name];
                
                foreach ($products as $product) {
                    // Generate barcode unik
                    $barcode = 'PRD' . str_pad($category->id, 2, '0', STR_PAD_LEFT) . rand(10000, 99999);
                    
                    Product::create([
                        'category_id' => $category->id,
                        'name' => $product['name'],
                        'description' => "Deskripsi untuk {$product['name']}. Produk berkualitas tinggi dengan harga terjangkau.",
                        'price' => $product['price'],
                        'stock' => $product['stock'],
                        'barcode' => $barcode,
                        'is_active' => true,
                    ]);
                }
            }
        }

        $this->command->info('Products seeded successfully!');
        $this->command->info('Total products: ' . Product::count());
    }
}
