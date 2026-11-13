<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.yookassa.shop_id' => 'shop',
            'services.yookassa.secret_key' => 'secret',
            'services.yookassa.frontend_url' => 'https://shop.test',
        ]);
    }

    public function test_repeat_request_reuses_pending_payment_link(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, [
            'yookassa_payment_id' => 'pay-live',
            'yookassa_payment_status' => 'pending',
        ]);

        $posts = 0;
        Http::fake(function ($request) use (&$posts, $order) {
            if ($request->method() === 'GET' && str_contains($request->url(), '/payments/pay-live')) {
                return Http::response([
                    'id' => 'pay-live',
                    'status' => 'pending',
                    'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
                    'metadata' => ['order_id' => (string) $order->id],
                    'confirmation' => [
                        'type' => 'redirect',
                        'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=live',
                    ],
                ]);
            }

            if ($request->method() === 'POST') {
                $posts++;
            }

            return Http::response(['message' => 'unexpected'], 500);
        });

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertOk()
            ->assertJsonPath('reused', true)
            ->assertJsonPath('confirmation_url', 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=live');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertOk()
            ->assertJsonPath('reused', true);

        $this->assertSame(0, $posts);
        $this->assertSame('pay-live', $order->fresh()->yookassa_payment_id);
    }

    public function test_cancelled_payment_creates_one_new_link_and_second_click_reuses_it(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, [
            'status' => 'payment_cancelled',
            'yookassa_payment_id' => 'pay-old',
            'yookassa_payment_status' => 'canceled',
            'yookassa_idempotence_key' => 'order:old:payment:v2',
        ]);

        $posts = 0;
        Http::fake(function ($request) use (&$posts, $order) {
            if ($request->method() === 'GET' && str_contains($request->url(), '/payments/pay-old')) {
                return Http::response([
                    'id' => 'pay-old',
                    'status' => 'canceled',
                    'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
                    'metadata' => ['order_id' => (string) $order->id],
                ]);
            }

            if ($request->method() === 'GET' && str_contains($request->url(), '/payments/pay-new')) {
                return Http::response([
                    'id' => 'pay-new',
                    'status' => 'pending',
                    'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
                    'metadata' => ['order_id' => (string) $order->id],
                    'confirmation' => [
                        'type' => 'redirect',
                        'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=new',
                    ],
                ]);
            }

            if ($request->method() === 'POST' && str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/payments')) {
                $posts++;

                return Http::response([
                    'id' => 'pay-new',
                    'status' => 'pending',
                    'confirmation' => [
                        'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=new',
                    ],
                ]);
            }

            return Http::response(['message' => 'unexpected '.$request->url()], 500);
        });

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertOk()
            ->assertJsonPath('reused', false)
            ->assertJsonPath('confirmation_url', 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=new');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertOk()
            ->assertJsonPath('reused', true);

        $this->assertSame(1, $posts);
        $fresh = $order->fresh();
        $this->assertSame('pending_payment', $fresh->status);
        $this->assertSame('pay-new', $fresh->yookassa_payment_id);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'yookassa_payment_id' => 'pay-new',
        ]);
    }

    public function test_other_user_and_paid_order_cannot_get_a_link(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makeOrder($owner);

        $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertNotFound();

        $order->update(['status' => 'confirmed', 'yookassa_payment_status' => 'succeeded']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/pay')
            ->assertStatus(422);

        $this->postJson('/api/orders/'.$order->id.'/pay')->assertUnauthorized();
    }

    public function test_admin_can_issue_payment_link_for_client_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create();
        $order = $this->makeOrder($client, [
            'yookassa_payment_id' => 'pay-live',
            'yookassa_payment_status' => 'pending',
        ]);

        Http::fake([
            'https://api.yookassa.ru/v3/payments/pay-live' => Http::response([
                'id' => 'pay-live',
                'status' => 'pending',
                'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
                'confirmation' => [
                    'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=admin',
                ],
            ]),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/orders/'.$order->id.'/payment-link')
            ->assertOk()
            ->assertJsonPath('reused', true)
            ->assertJsonPath('confirmation_url', 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=admin');

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/admin/orders/'.$order->id.'/payment-link')
            ->assertForbidden();
    }

    public function test_second_successful_payment_is_refunded_without_changing_the_order(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, [
            'status' => 'confirmed',
            'yookassa_payment_id' => 'pay-current',
            'yookassa_payment_status' => 'succeeded',
            'order_received_email_sent_at' => now(),
        ]);

        OrderPayment::query()->create([
            'order_id' => $order->id,
            'yookassa_payment_id' => 'pay-old',
            'idempotence_key' => 'order:'.$order->id.':payment:attempt:1',
            'amount' => '1500.00',
            'status' => 'pending',
        ]);

        Http::fake([
            'https://api.yookassa.ru/v3/payments/pay-old' => Http::response([
                'id' => 'pay-old',
                'status' => 'succeeded',
                'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
            ]),
            'https://api.yookassa.ru/v3/refunds' => Http::response([
                'id' => 'refund-dup',
                'status' => 'succeeded',
                'payment_id' => 'pay-old',
                'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
            ]),
        ]);

        $this->postJson('/api/yookassa/webhook', [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay-old'],
        ])->assertOk()
            ->assertJsonPath('status', 'duplicate_refunded');

        $fresh = $order->fresh();
        $this->assertSame('confirmed', $fresh->status);
        $this->assertSame('pay-current', $fresh->yookassa_payment_id);
        $this->assertSame('0.00', number_format((float) $fresh->refunded_amount, 2, '.', ''));
        $this->assertDatabaseHas('payment_refunds', [
            'order_id' => $order->id,
            'yookassa_payment_id' => 'pay-old',
            'yookassa_refund_id' => 'refund-dup',
            'reason_code' => 'duplicate_payment',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeOrder(User $user, array $extra = []): Order
    {
        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'child_full_name' => 'Ребёнок',
            'child_gender' => 'boy',
            'settlement' => 'Чита',
            'school' => 'Школа',
            'class_num' => '1',
            'school_year' => '2026',
            'size_from_table' => '128',
            'parent_full_name' => 'Родитель',
            'parent_phone' => '+79990001122',
            'parent_email' => 'parent@example.com',
            'recipient_is_customer' => true,
            'recipient_phone' => '+79990001122',
            'terms_accepted' => true,
            'total_amount' => '1500.00',
            'yookassa_idempotence_key' => 'order-key-'.$user->id.'-'.str()->random(8),
        ], $extra));
    }
}
