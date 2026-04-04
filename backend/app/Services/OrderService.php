<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class OrderService
{
    public function createOrder(Request $request, array $validated): Order
    {
        $items = $validated['items'];
        unset($validated['items']);

        $validated['user_id'] = $this->resolveUserId($request);

        $productGender = $this->productGenderFromChildGender($validated['child_gender'] ?? null);
        $validated['total_amount'] = $this->calculateTotalAmount($items, $productGender);

        return DB::transaction(function () use ($validated, $items) {
            /** @var Order $order */
            $order = Order::create($validated);

            foreach ($items as $index => $row) {
                $order->items()->create([
                    'position' => $index,
                    'product_name' => $row['product_name'],
                    'quantity' => (int) $row['quantity'],
                    'size_override' => $row['size_override'] ?? null,
                    'line_comment' => $row['line_comment'] ?? null,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Сумма заказа и строки: для каждой позиции цена из products по точному совпадению названия × количество.
     * При заданном $productGender (boys/girls) выбирается товар с тем же полом — иначе при одинаковых названиях
     * у разных полов подставлялся бы первый попавшийся в БД (неверное фото и цена).
     * Позиции без совпадения в каталоге дают line_total 0.
     *
     * @param  array<int, array{product_name?: string, quantity?: int}>  $items
     * @param  'boys'|'girls'|null  $productGender
     * @return array{total: string, lines: list<array{product_name: string, quantity: int, unit_price: string|null, line_total: string, image: string|null}>}
     */
    public function calculateOrderTotalsAndLines(array $items, ?string $productGender = null): array
    {
        $sum = 0.0;
        $lines = [];
        foreach ($items as $row) {
            $name = trim((string) ($row['product_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $qty = (int) ($row['quantity'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }
            $query = Product::query()->where('name', $name);
            if ($productGender !== null && $productGender !== '') {
                $query->where('gender', $productGender);
            }
            $product = $query->first();
            if ($product === null) {
                $lines[] = [
                    'product_name' => $name,
                    'quantity' => $qty,
                    'unit_price' => null,
                    'line_total' => number_format(0.0, 2, '.', ''),
                    'image' => null,
                ];

                continue;
            }
            $unit = (float) $product->price;
            $lineTotal = $unit * $qty;
            $sum += $lineTotal;
            $lines[] = [
                'product_name' => $name,
                'quantity' => $qty,
                'unit_price' => number_format($unit, 2, '.', ''),
                'line_total' => number_format($lineTotal, 2, '.', ''),
                'image' => $product->image,
            ];
        }

        return [
            'total' => number_format($sum, 2, '.', ''),
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<int, array{product_name?: string, quantity?: int}>  $items
     * @param  'boys'|'girls'|null  $productGender
     */
    public function calculateTotalAmount(array $items, ?string $productGender = null): string
    {
        return $this->calculateOrderTotalsAndLines($items, $productGender)['total'];
    }

    /**
     * Пол ребёнка в заказе (boy/girl) → значение поля products.gender (boys/girls).
     */
    public function productGenderFromChildGender(?string $childGender): ?string
    {
        return match ($childGender) {
            'boy' => 'boys',
            'girl' => 'girls',
            default => null,
        };
    }

    public function resolveUserId(Request $request): ?int
    {
        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken === null) {
            return null;
        }

        $user = $accessToken->tokenable;
        if (! $user instanceof User) {
            return null;
        }

        return $user->id;
    }

    /**
     * Возвращает заказы текущего пользователя вместе с позициями.
     *
     * @return EloquentCollection<int, Order>
     */
    public function getOrders(Request $request): EloquentCollection
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            return new EloquentCollection();
        }

        return Order::query()
            ->with('items')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Возвращает все заказы для админ-панели.
     *
     * @return EloquentCollection<int, Order>
     */
    public function getAllOrders(): EloquentCollection
    {
        return Order::query()
            ->with(['items', 'user'])
            ->latest()
            ->get();
    }

    public function getAdminOrderById(int $orderId): ?Order
    {
        return Order::query()
            ->with(['items', 'user'])
            ->find($orderId);
    }

    public function updateAdminOrderStatus(int $orderId, string $status): ?Order
    {
        $order = Order::query()->find($orderId);
        if ($order === null) {
            return null;
        }

        $order->update(['status' => $status]);

        return $order->fresh(['items', 'user']);
    }
}
