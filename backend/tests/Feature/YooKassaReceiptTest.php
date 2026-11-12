<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\YooKassaReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YooKassaReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_uses_unit_price_so_quantity_times_amount_matches_payment(): void
    {
        $product = Product::query()->create([
            'name' => 'Жилет',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 1500,
            'in_stock' => true,
        ]);

        $order = $this->orderWithItem($product, 2, '3000.00');
        $receipt = YooKassaReceipt::fromOrder($order, '3000.00');

        $this->assertCount(1, $receipt['items']);
        $this->assertSame('2.00', $receipt['items'][0]['quantity']);
        $this->assertSame('1500.00', $receipt['items'][0]['amount']['value']);
        $this->assertSame(3000.0, $this->receiptTotal($receipt['items']));
    }

    public function test_single_quantity_receipt_amount_equals_unit_price(): void
    {
        $product = Product::query()->create([
            'name' => 'Жилет',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 1500,
            'in_stock' => true,
        ]);

        $order = $this->orderWithItem($product, 1, '1500.00');
        $receipt = YooKassaReceipt::fromOrder($order, '1500.00');

        $this->assertSame('1.00', $receipt['items'][0]['quantity']);
        $this->assertSame('1500.00', $receipt['items'][0]['amount']['value']);
        $this->assertSame(1500.0, $this->receiptTotal($receipt['items']));
    }

    public function test_total_difference_is_not_multiplied_by_quantity(): void
    {
        $product = Product::query()->create([
            'name' => 'Жилет',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 1000,
            'in_stock' => true,
        ]);

        $order = $this->orderWithItem($product, 2, '2500.00');
        $receipt = YooKassaReceipt::fromOrder($order, '2500.00');

        $this->assertSame(2500.0, $this->receiptTotal($receipt['items']));
        $quantities = array_map(static fn (array $line): string => $line['quantity'], $receipt['items']);
        $this->assertNotContains('2.00', $quantities);
    }

    private function orderWithItem(Product $product, int $quantity, string $total): Order
    {
        $order = Order::query()->create([
            'status' => 'pending_payment',
            'order_type' => 'ready_to_wear',
            'child_full_name' => 'Готовая одежда',
            'child_gender' => 'boy',
            'settlement' => 'Не указано',
            'school' => 'Не указано',
            'class_num' => '-',
            'class_letter' => '-',
            'school_year' => '-',
            'size_from_table' => 'Готовые размеры',
            'parent_full_name' => 'Иванова Мария',
            'parent_phone' => '+79990001122',
            'parent_email' => 'parent@example.com',
            'recipient_is_customer' => true,
            'recipient_phone' => '+79990001122',
            'terms_accepted' => true,
            'total_amount' => $total,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'position' => 0,
            'product_name' => $product->name,
            'quantity' => $quantity,
        ]);

        return $order->fresh('items');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function receiptTotal(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += round((float) $item['quantity'] * (float) $item['amount']['value'], 2);
        }

        return round($sum, 2);
    }
}
