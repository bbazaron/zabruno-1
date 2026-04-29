<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

class OrderService
{
    public function __construct(
        private YooKassaClient $yooKassa,
    ) {}

    /**
     * Создаёт заказ и платёж в ЮKassa в одной транзакции БД. При сбое API заказ откатывается.
     * Письмо «заказ принят» ставится в очередь БД ({@see \App\Mail\OrderReceived}).
     *
     * @return array{order: Order, confirmation_url: string|null}
     */
    public function createOrder(Request $request, array $validated): array
    {
        $items = $validated['items'];
        unset($validated['items']);

        $validated['user_id'] = $this->resolveUserId($request);
        $validated['order_type'] = 'custom_tailoring';

        $productGender = $this->productGenderFromChildGender($validated['child_gender'] ?? null);
        $validated['total_amount'] = $this->calculateTotalAmount($items, $productGender);

        $returnUrl = $this->yookassaReturnUrl($request);

        return DB::transaction(function () use ($validated, $items, $returnUrl) {
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

            $order->load('items');

            $amount = (float) $order->total_amount;
            $confirmationUrl = null;

            if ($amount > 0) {
                if (! $this->yooKassa->isConfigured()) {
                    throw new \RuntimeException('ЮKassa не настроена: задайте YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
                }

                $amountFormatted = number_format($amount, 2, '.', '');
                $payment = $this->yooKassa->createRedirectPayment(
                    $amountFormatted,
                    'Заказ №'.$order->id,
                    $returnUrl,
                    ['order_id' => (string) $order->id],
                );

                $order->update([
                    'yookassa_payment_id' => $payment['id'],
                    'yookassa_payment_status' => $payment['status'],
                ]);

                $order = $order->fresh('items');
                $confirmationUrl = $payment['confirmation_url'];
            }

            $this->scheduleOrderReceivedEmail($order);

            return [
                'order' => $order,
                'confirmation_url' => $confirmationUrl,
            ];
        });
    }

    /**
     * Оформление заказа из корзины (ready_to_wear) с фиксированными размерами.
     *
     * @param  array{parent_full_name:string,parent_phone:string,parent_email?:string,comment?:string|null}  $validated
     * @return array{order: Order, confirmation_url: string|null}
     */
    public function createCartOrder(Request $request, array $validated): array
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            throw new \RuntimeException('Unauthorized');
        }

        $cartItems = UserProduct::query()
            ->with('product')
            ->where('user_id', $userId)
            ->get();

        if ($cartItems->isEmpty()) {
            throw new \RuntimeException('Корзина пуста');
        }

        $total = 0.0;
        $lines = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if ($product === null) {
                continue;
            }

            $qty = max(1, (int) $cartItem->quantity);
            $unitPrice = (float) $product->price;
            $lineTotal = $unitPrice * $qty;
            $total += $lineTotal;

            $lines[] = [
                'product_name' => (string) $product->name,
                'quantity' => $qty,
                'size_override' => $cartItem->selected_size,
            ];
        }

        if ($lines === []) {
            throw new \RuntimeException('В корзине нет доступных товаров');
        }

        $email = trim((string) ($validated['parent_email'] ?? ''));
        if ($email === '') {
            $user = User::query()->find($userId);
            $email = $user?->email ?? '';
        }

        $payload = [
            'user_id' => $userId,
            'order_type' => 'ready_to_wear',
            'status' => 'pending',
            'child_full_name' => 'Готовая одежда',
            'child_gender' => 'boy',
            'settlement' => 'Не указано',
            'school' => 'Не указано',
            'class_num' => '-',
            'class_letter' => '-',
            'school_year' => '-',
            'size_from_table' => 'Готовые размеры',
            'height_cm' => null,
            'chest_cm' => null,
            'waist_cm' => null,
            'hips_cm' => null,
            'figure_comment' => null,
            'kit_comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
            'parent_full_name' => trim((string) $validated['parent_full_name']),
            'parent_phone' => trim((string) $validated['parent_phone']),
            'parent_email' => $email,
            'messenger_max' => null,
            'messenger_telegram' => null,
            'recipient_is_customer' => true,
            'recipient_name' => null,
            'recipient_phone' => trim((string) $validated['parent_phone']),
            'terms_accepted' => true,
            'total_amount' => number_format($total, 2, '.', ''),
        ];

        $returnUrl = $this->yookassaReturnUrl($request);

        return DB::transaction(function () use ($payload, $lines, $cartItems, $returnUrl) {
            /** @var Order $order */
            $order = Order::create($payload);

            foreach ($lines as $index => $line) {
                $order->items()->create([
                    'position' => $index,
                    'product_name' => $line['product_name'],
                    'quantity' => $line['quantity'],
                    'size_override' => $line['size_override'],
                    'line_comment' => null,
                ]);
            }

            $order->load('items');
            $amount = (float) $order->total_amount;
            $confirmationUrl = null;

            if ($amount > 0) {
                if (! $this->yooKassa->isConfigured()) {
                    throw new \RuntimeException('ЮKassa не настроена: задайте YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
                }

                $amountFormatted = number_format($amount, 2, '.', '');
                $payment = $this->yooKassa->createRedirectPayment(
                    $amountFormatted,
                    'Заказ №'.$order->id,
                    $returnUrl,
                    ['order_id' => (string) $order->id],
                );

                $order->update([
                    'yookassa_payment_id' => $payment['id'],
                    'yookassa_payment_status' => $payment['status'],
                ]);

                $order = $order->fresh('items');
                $confirmationUrl = $payment['confirmation_url'];
            }

            UserProduct::query()
                ->whereIn('id', $cartItems->pluck('id')->all())
                ->delete();

            $this->scheduleOrderReceivedEmail($order);

            return [
                'order' => $order,
                'confirmation_url' => $confirmationUrl,
            ];
        });
    }

    private function scheduleOrderReceivedEmail(Order $order): void
    {
        $email = trim((string) $order->parent_email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $orderId = $order->id;

        DB::afterCommit(function () use ($email, $orderId): void {
            $fresh = Order::query()->with('items')->find($orderId);
            if ($fresh === null) {
                Log::warning('Письмо о заказе не поставлено в очередь: заказ не найден после коммита', [
                    'order_id' => $orderId,
                ]);

                return;
            }

            try {
                Mail::to($email)->queue(new OrderReceived($fresh));
            } catch (\Throwable $e) {
                Log::error('Не удалось поставить в очередь БД письмо о принятом заказе', [
                    'order_id' => $orderId,
                    'queue_connection' => 'database',
                    'exception' => $e,
                ]);
                report($e);

                try {
                    error_log(sprintf(
                        'OrderReceived queue failed (order_id=%s, connection=database): %s',
                        (string) $orderId,
                        $e->getMessage()
                    ));
                } catch (\Throwable) {
                    // ignore secondary failures
                }
            }
        });
    }

    private function yookassaReturnUrl(Request $request): string
    {
        $base = $this->frontendBaseUrlFromRequest($request);

        if ($base === '') {
            $base = rtrim((string) config('services.yookassa.frontend_url'), '/');
        }

        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        }

        return $base.'/orderCheckout?fromPayment=1';
    }

    private function frontendBaseUrlFromRequest(Request $request): string
    {
        $origin = trim((string) $request->headers->get('origin', ''));
        $referer = trim((string) $request->headers->get('referer', ''));

        $originUrl = $this->normalizeFrontendBaseUrl($origin);
        if ($originUrl !== '') {
            return $originUrl;
        }

        $refererUrl = $this->normalizeFrontendBaseUrl($referer);
        if ($refererUrl !== '') {
            return $refererUrl;
        }

        return '';
    }

    private function normalizeFrontendBaseUrl(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        $parts = parse_url($raw);
        if (! is_array($parts)) {
            return '';
        }

        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        if (! is_string($scheme) || ! is_string($host) || $scheme === '' || $host === '') {
            return '';
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return sprintf('%s://%s%s', $scheme, $host, $port);
    }

    /**
     * Сумма заказа и строки: для каждой позиции цена из products по точному совпадению названия × количество.
     * При заданном $productGender (boys/girls) выбирается товар с тем же полом
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

    public function getAllOrdersPaginated(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));
        $orderType = trim((string) $request->query('order_type', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $sort = trim((string) $request->query('sort', 'new'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 25);
        if ($perPage < 1) {
            $perPage = 25;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = Order::query()
            ->with(['items', 'user']);

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($orderType !== '' && $orderType !== 'all') {
            $query->where('order_type', $orderType);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $idSearch = ltrim($search, '#');
            $query->where(function ($q) use ($search, $idSearch): void {
                $q->where('parent_full_name', 'like', '%'.$search.'%')
                    ->orWhere('parent_phone', 'like', '%'.$search.'%')
                    ->orWhere('parent_email', 'like', '%'.$search.'%')
                    ->orWhere('child_full_name', 'like', '%'.$search.'%')
                    ->orWhere('school', 'like', '%'.$search.'%')
                    ->orWhere('status', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$idSearch.'%');
            });
        }

        $query->orderBy('created_at', $sort === 'old' ? 'asc' : 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Возвращает все заказы для экспорта в админке.
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

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateAdminOrder(int $orderId, array $validated): ?Order
    {
        /** @var array<int, array{product_name: string, quantity: int, size_override?: string|null, line_comment?: string|null}> $items */
        $items = $validated['items'];
        unset($validated['items']);

        $productGender = $this->productGenderFromChildGender($validated['child_gender'] ?? null);
        $validated['total_amount'] = $this->calculateTotalAmount($items, $productGender);

        return DB::transaction(function () use ($orderId, $validated, $items) {
            $order = Order::query()->find($orderId);
            if ($order === null) {
                return null;
            }

            $order->update($validated);
            $order->items()->delete();

            foreach ($items as $index => $row) {
                $order->items()->create([
                    'position' => $index,
                    'product_name' => $row['product_name'],
                    'quantity' => (int) $row['quantity'],
                    'size_override' => $row['size_override'] ?? null,
                    'line_comment' => $row['line_comment'] ?? null,
                ]);
            }

            return $order->fresh(['items', 'user']);
        });
    }
}
