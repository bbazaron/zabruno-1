<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'gender', 'season', 'price', 'original_price', 'color',
        'image', 'description', 'in_stock'
    ];

    protected $casts = [
        'in_stock' => 'boolean'
    ];

    public function cartItems(): HasMany
    {
        return $this->hasMany(UserProduct::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }
}
