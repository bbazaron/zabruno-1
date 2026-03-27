<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'gender', 'season', 'price', 'original_price', 'color',
        'image', 'description', 'in_stock'
    ];

    protected $casts = [
        'in_stock' => 'boolean'
    ];
}
