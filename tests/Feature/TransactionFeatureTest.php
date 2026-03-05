<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_transaction_with_payment_and_profit()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10000,
            'cost_price' => 5000, // Profit should be 5000
            'stock' => 10
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/transactions', [
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'total_amount' => 10000,
            'final_amount' => 10000,
            'paid_amount' => 20000,
            'change_amount' => 10000,
            'total_profit' => 5000,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $product->id,
            'profit' => 5000
        ]);
    }

    public function test_cannot_create_transaction_if_payment_is_less_than_total()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/transactions', [
            'payment_method' => 'cash',
            'paid_amount' => 5000, // Less than 10000
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1
                ]
            ]
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Uang yang dibayarkan kurang. Total: 10000, Dibayar: 5000']);
    }

    public function test_can_create_transaction_with_order_name()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10000,
            'cost_price' => 5000,
            'stock' => 10
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/transactions', [
            'payment_method' => 'cash',
            'paid_amount' => 10000,
            'order_name' => 'Budi Santoso',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1
                ]
            ]
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('transaction.order_name', 'Budi Santoso');

        $this->assertDatabaseHas('transactions', [
            'order_name' => 'Budi Santoso',
        ]);
    }

    public function test_can_search_transactions_by_order_name()
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'order_name' => 'Andi Wijaya',
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'order_name' => 'Siti Rahayu',
        ]);

        // Search should find only Andi
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=Andi');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_name', 'Andi Wijaya');

        // Search should find only Siti
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=Siti');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_name', 'Siti Rahayu');
    }
}
