<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutItem extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'image',
        'gender',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function resolvedName(): string
    {
        $fromProduct = trim((string) ($this->product?->name ?? ''));
        if ($fromProduct !== '') {
            return $fromProduct;
        }

        return trim((string) $this->name);
    }

    public function resolvedPrice(): string
    {
        if ($this->product !== null && $this->product->price !== null) {
            return number_format((float) $this->product->price, 2, '.', '');
        }

        return number_format((float) $this->price, 2, '.', '');
    }

    public function resolvedGender(): string
    {
        $fromProduct = trim((string) ($this->product?->gender ?? ''));
        if (in_array($fromProduct, ['boys', 'girls', 'all'], true)) {
            return $fromProduct;
        }

        $own = trim((string) $this->gender);

        return in_array($own, ['boys', 'girls', 'all'], true) ? $own : 'all';
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->product !== null) {
            $fromProduct = $this->toPublicMediaUrl($this->product->image);
            if ($fromProduct !== null) {
                return $fromProduct;
            }
        }

        return $this->toPublicMediaUrl($this->image);
    }

    public function publicImageUrl(): ?string
    {
        return $this->resolvedImageUrl();
    }

    public function toPublicMediaUrl(?string $path): ?string
    {
        $path = trim((string) ($path ?? ''));
        if ($path === '' || $path === '0' || strtolower($path) === 'null') {
            return null;
        }
        if (str_starts_with($path, '/storage/')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }
}
