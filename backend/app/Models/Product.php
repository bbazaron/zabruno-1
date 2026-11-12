<?php

namespace App\Models;

use App\Support\ProductSchoolColors;
use App\Support\ProductSizes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'gender', 'season', 'price', 'original_price', 'color',
        'school_color_excluded', 'school_color_extra', 'sizes', 'class_code',
        'image', 'description', 'in_stock',
    ];

    protected $casts = [
        'in_stock' => 'boolean',
        'sizes' => 'array',
    ];

    /**
     * @return list<string>
     */
    public function sizesList(): array
    {
        $list = ProductSizes::normalizeList($this->sizes);

        return $list !== [] ? $list : ProductSizes::defaultList();
    }

    /**
     * @return list<string>
     */
    public function schoolColorsList(): array
    {
        return ProductSchoolColors::forProduct($this);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(UserProduct::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }
}
