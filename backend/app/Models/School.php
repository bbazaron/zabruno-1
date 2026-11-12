<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'city',
        'name',
    ];

    public function cartItems(): HasMany
    {
        return $this->hasMany(UserProduct::class);
    }
}
