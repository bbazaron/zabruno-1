<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutItemMedia extends Model
{
    protected $fillable = [
        'checkout_item_id',
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

    public function checkoutItem(): BelongsTo
    {
        return $this->belongsTo(CheckoutItem::class);
    }
}
