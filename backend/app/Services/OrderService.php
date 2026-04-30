<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Models\Order;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\UserProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $amount = round((float) $validated['total_amount'], 2);
        if ($amount > 0) {
            $validated['status'] = 'pending_payment';
        }

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated, $items) {
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

            return $order;
        });

        $order->load('items');
        $confirmationUrl = null;

        if ($amount > 0) {
            $paymentData = $this->createPaymentForOrder($order, $returnUrl);
            $order = $paymentData['order'];
            $confirmationUrl = $paymentData['confirmation_url'];
        }

        $this->scheduleOrderReceivedEmail($order);

        return [
            'order' => $order,
            'confirmation_url' => $confirmationUrl,
        ];
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
            'status' => $total > 0 ? 'pending_payment' : 'pending',
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

        /** @var Order $order */
        $order = DB::transaction(function () use ($payload, $lines) {
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

            return $order;
        });

        $order->load('items');
        $amount = (float) $order->total_amount;
        $confirmationUrl = null;

        if ($amount > 0) {
            $paymentData = $this->createPaymentForOrder($order, $returnUrl);
            $order = $paymentData['order'];
            $confirmationUrl = $paymentData['confirmation_url'];
        }

        UserProduct::query()
            ->whereIn('id', $cartItems->pluck('id')->all())
            ->delete();

        $this->scheduleOrderReceivedEmail($order);

        return [
            'order' => $order,
            'confirmation_url' => $confirmationUrl,
        ];
    }

    /**
     * @return array{order: Order, confirmation_url: string}
     */
    private function createPaymentForOrder(Order $order, string $returnUrl): array
    {
        if (! $this->yooKassa->isConfigured()) {
            throw new \RuntimeException('ЮKassa не настроена: задайте YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
        }

        $amountFormatted = number_format((float) $order->total_amount, 2, '.', '');
        $idempotenceKey = $this->canUseYookassaIdempotenceKeyColumn() && $order->yookassa_idempotence_key
            ? $order->yookassa_idempotence_key
            : sprintf('order:%d:payment:v1', $order->id);

        if ($this->canUseYookassaIdempotenceKeyColumn() && $order->yookassa_idempotence_key !== $idempotenceKey) {
            $order->update(['yookassa_idempotence_key' => $idempotenceKey]);
        }

        $payment = $this->yooKassa->createRedirectPayment(
            $amountFormatted,
            'Заказ №'.$order->id,
            $returnUrl,
            ['order_id' => (string) $order->id],
            $idempotenceKey,
        );

        $order->update([
            'yookassa_payment_id' => $payment['id'],
            'yookassa_payment_status' => $payment['status'],
        ]);

        return [
            'order' => $order->fresh('items'),
            'confirmation_url' => $payment['confirmation_url'],
        ];
    }

    private function canUseYookassaIdempotenceKeyColumn(): bool
    {
        static $hasColumn;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('orders', 'yookassa_idempotence_key');
        }

        return $hasColumn;
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

        $orders = Order::query()
            ->with('items')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $this->attachLinePricingToOrders($orders);

        return $orders;
    }

    /**
     * Возвращает статус последнего заказа с платежом для экрана возврата из YooKassa.
     *
     * @return array{order_id:int,payment_id:string,status:string,label:string}|null
     */
    public function latestPaymentStatus(Request $request): ?array
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            return null;
        }

        $order = Order::query()
            ->where('user_id', $userId)
            ->whereNotNull('yookassa_payment_id')
            ->latest()
            ->first();
        if ($order === null) {
            return null;
        }

        return [
            'order_id' => (int) $order->id,
            'payment_id' => (string) $order->yookassa_payment_id,
            'status' => (string) $order->status,
            'label' => $this->humanOrderStatus((string) $order->status),
        ];
    }

    private function humanOrderStatus(string $status): string
    {
        return match ($status) {
            'confirmed' => 'Оплачен',
            'pending_payment' => 'Ожидает подтверждения',
            'partially_refunded' => 'Частично возвращён',
            'refunded' => 'Возвращён',
            'payment_cancelled', 'cancelled' => 'Отменен',
            default => 'Не найден',
        };
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

        $orders = $query->paginate($perPage, ['*'], 'page', $page);
        $this->attachLinePricingToOrders($orders->getCollection());

        return $orders;
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
        $order = Order::query()
            ->with(['items', 'user'])
            ->find($orderId);

        if ($order !== null) {
            $this->attachLinePricingToOrders(new EloquentCollection([$order]));
        }

        return $order;
    }

    /**
     * Добавляет к order_items поля unit_price/line_total для корректного отображения сумм на фронте.
     *
     * @param  EloquentCollection<int, Order>  $orders
     */
    private function attachLinePricingToOrders(EloquentCollection $orders): void
    {
        if ($orders->isEmpty()) {
            return;
        }

        $names = $orders
            ->flatMap(fn (Order $order) => $order->items->pluck('product_name'))
            ->map(fn (mixed $name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        /** @var Collection<string, \Illuminate\Support\Collection<int, Product>> $productsByName */
        $productsByName = Product::query()
            ->whereIn('name', $names->all())
            ->get()
            ->groupBy('name');

        foreach ($orders as $order) {
            $productGender = $this->productGenderFromChildGender($order->child_gender);

            foreach ($order->items as $item) {
                $name = trim((string) $item->product_name);
                $qty = max(1, (int) $item->quantity);
                $unitPrice = null;

                $candidates = $productsByName->get($name);
                if ($candidates instanceof Collection && $candidates->isNotEmpty()) {
                    $match = null;
                    if ($productGender !== null) {
                        $match = $candidates->first(fn (Product $p) => $p->gender === $productGender);
                    }
                    if (! $match) {
                        $match = $candidates->first();
                    }

                    if ($match instanceof Product) {
                        $unitPrice = (float) $match->price;
                    }
                }

                if ($unitPrice === null) {
                    $item->setAttribute('unit_price', null);
                    $item->setAttribute('line_total', null);
                    continue;
                }

                $lineTotal = $unitPrice * $qty;
                $item->setAttribute('unit_price', number_format($unitPrice, 2, '.', ''));
                $item->setAttribute('line_total', number_format($lineTotal, 2, '.', ''));
            }
        }
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
     * @return array{order: Order, refund: PaymentRefund, available_to_refund: string}
     */
    public function createAdminRefund(
        int $orderId,
        int $adminUserId,
        float $amount,
        string $reasonCode,
        ?string $reasonComment = null,
    ): array {
        if ($amount <= 0) {
            throw new \RuntimeException('Сумма возврата должна быть больше нуля');
        }

        return DB::transaction(function () use ($orderId, $adminUserId, $amount, $reasonCode, $reasonComment) {
            /** @var Order|null $order */
            $order = Order::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new \RuntimeException('Заказ не найден');
            }

            if (! is_string($order->yookassa_payment_id) || $order->yookassa_payment_id === '') {
                throw new \RuntimeException('У заказа нет платежа ЮKassa');
            }
            if ((string) $order->yookassa_payment_status !== 'succeeded') {
                throw new \RuntimeException('Возврат доступен только для оплаченных заказов');
            }

            $totalAmount = round((float) $order->total_amount, 2);
            $refundedAmount = round((float) ($order->refunded_amount ?? 0), 2);
            $availableToRefund = round($totalAmount - $refundedAmount, 2);
            $requestedAmount = round($amount, 2);

            if ($requestedAmount - $availableToRefund > 0.01) {
                throw new \RuntimeException('Сумма возврата превышает доступный лимит');
            }

            $amountFormatted = number_format($requestedAmount, 2, '.', '');
            $idempotenceKey = (string) Str::uuid();

            $refund = PaymentRefund::query()->create([
                'order_id' => $order->id,
                'created_by' => $adminUserId,
                'yookassa_payment_id' => (string) $order->yookassa_payment_id,
                'amount' => $amountFormatted,
                'reason_code' => $reasonCode,
                'reason_comment' => $reasonComment,
                'status' => 'pending',
                'idempotence_key' => $idempotenceKey,
            ]);

            Log::info('Admin refund: request started', [
                'order_id' => $order->id,
                'payment_id' => $order->yookassa_payment_id,
                'refund_id' => $refund->id,
                'amount' => $amountFormatted,
                'reason_code' => $reasonCode,
                'admin_user_id' => $adminUserId,
                'idempotence_key' => $idempotenceKey,
            ]);

            try {
                $gatewayRefund = $this->yooKassa->createRefund(
                    (string) $order->yookassa_payment_id,
                    $amountFormatted,
                    'Возврат по заказу №'.$order->id,
                    $idempotenceKey,
                    [
                        'order_id' => (string) $order->id,
                        'refund_id' => (string) $refund->id,
                        'reason_code' => $reasonCode,
                    ],
                );
            } catch (\Throwable $e) {
                $refund->update([
                    'status' => 'failed',
                    'gateway_response' => ['error' => $e->getMessage()],
                ]);

                Log::error('Admin refund: gateway call failed', [
                    'order_id' => $order->id,
                    'refund_id' => $refund->id,
                    'amount' => $amountFormatted,
                    'admin_user_id' => $adminUserId,
                    'idempotence_key' => $idempotenceKey,
                    'exception' => $e,
                ]);

                throw $e;
            }

            $refund->update([
                'yookassa_refund_id' => $gatewayRefund['id'],
                'status' => $gatewayRefund['status'],
                'gateway_response' => $gatewayRefund,
            ]);

            $updatedRefundedAmount = round($refundedAmount + $requestedAmount, 2);
            $isFullyRefunded = abs($totalAmount - $updatedRefundedAmount) <= 0.01;
            $order->update([
                'refunded_amount' => number_format($updatedRefundedAmount, 2, '.', ''),
                'status' => $isFullyRefunded ? 'refunded' : 'partially_refunded',
            ]);

            Log::info('Admin refund: completed', [
                'order_id' => $order->id,
                'payment_id' => $order->yookassa_payment_id,
                'refund_id' => $refund->id,
                'gateway_refund_id' => $gatewayRefund['id'],
                'amount' => $amountFormatted,
                'status' => $gatewayRefund['status'],
                'admin_user_id' => $adminUserId,
            ]);

            return [
                'order' => $order->fresh(['items', 'user']),
                'refund' => $refund->fresh(),
                'available_to_refund' => number_format(max(0, $totalAmount - $updatedRefundedAmount), 2, '.', ''),
            ];
        });
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
