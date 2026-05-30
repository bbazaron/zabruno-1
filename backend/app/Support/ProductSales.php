<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ProductSales
{
    /** @var list<string> */
    public const SOLD_ORDER_STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'production',
        'completed',
        'partially_refunded',
    ];

    public static function applySalesCountSubquery(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('products.*');
        }

        return $query->selectSub(
            static::salesCountSubquery(),
            'sales_count',
        );
    }

    public static function salesCountSubquery(): \Closure
    {
        return function (QueryBuilder $sub): void {
            $sub->from('order_items')
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', self::SOLD_ORDER_STATUSES)
                ->where(function (QueryBuilder $match): void {
                    $match->whereColumn('order_items.product_id', 'products.id')
                        ->orWhere(function (QueryBuilder $fallback): void {
                            $fallback->whereNull('order_items.product_id')
                                ->whereColumn('order_items.product_name', 'products.name')
                                ->where(function (QueryBuilder $gender): void {
                                    $gender->whereNull('orders.child_gender')
                                        ->orWhere(function (QueryBuilder $boys): void {
                                            $boys->where('orders.child_gender', 'boy')
                                                ->where('products.gender', 'boys');
                                        })
                                        ->orWhere(function (QueryBuilder $girls): void {
                                            $girls->where('orders.child_gender', 'girl')
                                                ->where('products.gender', 'girls');
                                        });
                                });
                        });
                });
        };
    }
}
