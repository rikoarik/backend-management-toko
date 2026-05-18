<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents; 

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Categories first
        $this->call(CategorySeeder::class);
        
        // Then seed Products (depends on categories)
        $this->call(ProductSeeder::class);

        // User::factory(10)->create();
  
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);  
    }
}
 