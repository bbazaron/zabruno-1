<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $items = $this->cartService->getCartItems($user->id);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'selected_size' => ['required', 'string', 'in:XS,S,M,L,XL'],
        ]);

        try {
            $item = $this->cartService->addProduct(
                $user->id,
                (int) $validated['product_id'],
                (int) ($validated['quantity'] ?? 1),
                (string) $validated['selected_size'],
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Товар добавлен в корзину',
            'item' => $item,
        ], 201);
    }

    public function update(Request $request, int $itemId): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = $this->cartService->updateQuantity($user->id, $itemId, (int) $validated['quantity']);
        if ($item === null) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'message' => 'Количество обновлено',
            'item' => $item,
        ]);
    }

    public function remove(Request $request, int $itemId): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $deleted = $this->cartService->removeItem($user->id, $itemId);
        if (! $deleted) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'message' => 'Товар удалён из корзины',
        ]);
    }
}
