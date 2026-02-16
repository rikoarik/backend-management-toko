<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SerializesDateToLocal;

class Transaction extends Model
{
    use HasFactory, SerializesDateToLocal;

    protected $fillable = [
        'transaction_code',
        'user_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'paid_amount',
        'change_amount',
        'total_profit',
        'payment_method',
        'status',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'discount_amount' => 'integer',
        'final_amount' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'total_profit' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
