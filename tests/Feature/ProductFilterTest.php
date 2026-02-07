<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and authenticate
        $user = User::factory()->create([
            'username' => 'testuser',
        ]);
        $this->actingAs($user);
    }

    public function test_can_filter_products_by_name_asc()
    {
        $category = Category::factory()->create();

        Product::factory()->create(['name' => 'Coca Cola', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Aqua', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Sprite', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products?filter=NAME_ASC');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Aqua', $data[0]['name']);
        $this->assertEquals('Coca Cola', $data[1]['name']);
        $this->assertEquals('Sprite', $data[2]['name']);
    }

    public function test_can_filter_products_by_name_desc()
    {
        $category = Category::factory()->create();

        Product::factory()->create(['name' => 'Coca Cola', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Aqua', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Sprite', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products?filter=NAME_DESC');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Sprite', $data[0]['name']);
        $this->assertEquals('Coca Cola', $data[1]['name']);
        $this->assertEquals('Aqua', $data[2]['name']);
    }
}
