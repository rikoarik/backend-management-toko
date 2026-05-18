<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_transactions_returns_csv_download()
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $user = User::factory()->create([
            'username' => 'testuser'
        ]);
        $this->actingAs($user, 'sanctum');

        // Create dummy data
        $category = Category::factory()->create(['name' => 'Beverages']);
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Cola']);

        $transaction = Transaction::create([
            'transaction_code' => 'TRX-TEST-001',
            'user_id' => $user->id,
            'total_amount' => 10000,
            'discount_amount' => 0,
            'final_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Test Note',
        ]);

        // Manually set created_at as it is not fillable
        $transaction->created_at = '2024-01-15 10:00:00';
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

        $response = $this->getJson('/api/v1/reports/transactions/export?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(200);

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('transactions_2024-01-01_2024-01-31.xlsx', function (\App\Exports\TransactionsExport $export) {
            // Can add more assertions on the export object here if needed
            return true;
        });
    }

    public function test_export_transactions_validation_error()
    {
        $user = User::factory()->create([
            'username' => 'testuser_val'
        ]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/reports/transactions/export');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }
}
