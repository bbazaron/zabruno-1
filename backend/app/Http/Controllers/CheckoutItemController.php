<?php

namespace App\Http\Controllers;

use App\Models\CheckoutItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gender = $this->normalizeGender($request->query('gender'));

        $items = CheckoutItem::query()
            ->with('product')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function (CheckoutItem $item) use ($gender): bool {
                if ($gender === null) {
                    return true;
                }
                $itemGender = $item->resolvedGender();

                return $itemGender === $gender || $itemGender === 'all';
            })
            ->values()
            ->map(fn (CheckoutItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->resolvedName(),
                'price' => $item->resolvedPrice(),
                'image' => $item->resolvedImageUrl(),
                'gender' => $item->resolvedGender(),
                'sort_order' => $item->sort_order,
            ]);

        return response()->json($items);
    }

    private function normalizeGender(mixed $raw): ?string
    {
        $value = is_string($raw) ? strtolower(trim($raw)) : '';

        return match ($value) {
            'boy', 'boys' => 'boys',
            'girl', 'girls' => 'girls',
            default => null,
        };
    }
}
