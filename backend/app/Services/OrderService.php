<?php

namespace App\Services;

use App\Models\Order;
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
