<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProduct extends Model
{
    protected $table = 'user_products';

    protected $fillable = [
        'user_id',
        'product_id',
        'selected_size',
        'selected_color',
        'selected_class',
        'selected_gender',
        'school_id',
        'school_city',
        'school_name',
        'school_key',
        'quantity',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
