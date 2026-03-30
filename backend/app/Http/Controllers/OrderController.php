<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function createOrder(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request, $request->validated());

        return response()->json([
            'message' => 'Заказ принят',
            'order' => $order,
        ], 201);
    }

    public function getOrders(\Illuminate\Http\Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $orders = $this->orderService->getOrders($request);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function getAllOrders(): JsonResponse
    {
        $orders = $this->orderService->getAllOrders();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function getAdminOrderById(int $id): JsonResponse
    {
        $order = $this->orderService->getAdminOrderById($id);

        if ($order === null) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Допустимые значения status заданы в миграции orders (комментарий) и здесь же для валидации.
     */
    public function updateAdminOrderStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:32', 'in:pending,confirmed,cancelled,completed'],
        ]);

        $order = $this->orderService->updateAdminOrderStatus($id, $validated['status']);

        if ($order === null) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Статус обновлён',
            'order' => $order,
        ]);
    }
}
