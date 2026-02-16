<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'transaction_code' => 'TRX-' . time() . '-' . mt_rand(100, 999),
            'user_id' => User::factory(),
            'total_amount' => 50000,
            'discount_amount' => 0,
            'final_amount' => 50000,
            'paid_amount' => 50000,
            'change_amount' => 0,
            'total_profit' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
