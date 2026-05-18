<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReportProfitTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_profit()
    {
        $user = User::factory()->create([
            'username' => 'testuser1'
        ]);
        $this->actingAs($user, 'sanctum');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Transaction 1: Cost 8000, Sales 10000 -> Profit 2000
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-001',
            'user_id' => $user->id,
            'total_amount' => 10000,
            'discount_amount' => 0,
            'final_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Test',
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 5000,
            'cost_price' => 4000, // 2 * 4000 = 8000 total cost
            'subtotal' => 10000
        ]);

        $response = $this->getJson('/api/v1/reports/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'today_sales' => 10000,
                'today_profit' => 2000
            ]);
    }

    public function test_sales_daily_returns_correct_profit()
    {
        $user = User::factory()->create([
            'username' => 'testuser2'
        ]);
        $this->actingAs($user, 'sanctum');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $date = '2024-01-15';

        // Transaction 1: Cost 8000, Sales 10000 -> Profit 2000
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-002',
            'user_id' => $user->id,
            'total_amount' => 10000,
            'discount_amount' => 0,
            'final_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Test',
        ]);

        // Manually set date
        $transaction->created_at = $date . ' 10:00:00';
        $transaction->save();

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 5000,
            'cost_price' => 4000,
            'subtotal' => 10000
        ]);

        $response = $this->getJson("/api/v1/reports/sales?type=daily&start_date={$date}&end_date={$date}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals($date, $data[0]['date']);
        $this->assertEquals(10000, $data[0]['total_sales']);
        $this->assertEquals(2000, $data[0]['total_profit']);
    }
}
