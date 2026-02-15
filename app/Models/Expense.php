<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'amount',
        'description',
        'expense_date'
    ];

    protected $casts = [
        'amount' => 'integer',
        'expense_date' => 'date:Y-m-d',
    ];

    // Expense categories
    public const CATEGORY_STOCK = 'beli_stok';
    public const CATEGORY_OPERATIONAL = 'operasional';
    public const CATEGORY_SALARY = 'gaji';
    public const CATEGORY_OTHER = 'lainnya';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_STOCK => 'Beli Stok',
            self::CATEGORY_OPERATIONAL => 'Operasional',
            self::CATEGORY_SALARY => 'Gaji',
            self::CATEGORY_OTHER => 'Lainnya',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
