<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
        'catalog_zoom',
        'catalog_pan_x',
        'catalog_pan_y',
    ];

    protected function casts(): array
    {
        return [
            'catalog_zoom' => 'integer',
            'catalog_pan_x' => 'integer',
            'catalog_pan_y' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
