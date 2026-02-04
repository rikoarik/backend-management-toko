<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'cost_price',
        'wholesale_price',
        'retail_price',
        'stock',
        'image',
        'barcode',
        'is_active'
    ];

    protected $casts = [
        'price' => 'integer',
        'cost_price' => 'integer',
        'wholesale_price' => 'integer',
        'retail_price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
