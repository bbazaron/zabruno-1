<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'order_type',
        'child_full_name',
        'child_gender',
        'settlement',
        'school',
        'class_num',
        'class_letter',
        'school_year',
        'size_from_table',
        'height_cm',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'figure_comment',
        'kit_comment',
        'parent_full_name',
        'parent_phone',
        'parent_email',
        'messenger_max',
        'messenger_telegram',
        'recipient_is_customer',
        'recipient_name',
        'recipient_phone',
        'terms_accepted',
        'total_amount',
        'yookassa_payment_id',
        'yookassa_payment_status',
    ];

    protected function casts(): array
    {
        return [
            'recipient_is_customer' => 'boolean',
            'terms_accepted' => 'boolean',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }
}
