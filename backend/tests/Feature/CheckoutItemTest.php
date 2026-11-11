<?php

namespace Tests\Feature;

use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_returns_active_items_for_gender(): void
    {
        CheckoutItem::query()->create([
            'name' => 'Жилет',
            'price' => 3500,
            'gender' => 'boys',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        CheckoutItem::query()->create([
            'name' => 'Юбка',
            'price' => 2800,
            'gender' => 'girls',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        CheckoutItem::query()->create([
            'name' => 'Скрыто',
            'price' => 1000,
            'gender' => 'all',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/checkout-items?gender=boy');

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('Жилет', $names);
        $this->assertNotContains('Юбка', $names);
        $this->assertNotContains('Скрыто', $names);
    }

    public function test_admin_can_add_checkout_item_from_catalog_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::query()->create([
            'name' => 'Жилет школьный',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 3500,
            'sizes' => ['32', '34'],
            'in_stock' => true,
        ]);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/checkout-items', [
            'source' => 'catalog',
            'product_id' => $product->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $create->assertCreated();
        $this->assertDatabaseHas('checkout_items', [
            'product_id' => $product->id,
            'name' => 'Жилет школьный',
        ]);
        $this->assertSame('Жилет школьный', $create->json('item.name'));
    }

    public function test_admin_can_create_catalog_product_and_checkout_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/checkout-items', [
            'source' => 'new',
            'name' => 'Брюки',
            'price' => 4200,
            'gender' => 'all',
            'category' => 'pants',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $create->assertCreated();
        $this->assertDatabaseHas('products', ['name' => 'Брюки', 'category' => 'pants']);
        $this->assertDatabaseHas('checkout_items', ['name' => 'Брюки']);
        $this->assertNotNull($create->json('item.product_id'));
    }

    public function test_admin_can_create_checkout_card_with_photo_like_catalog(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $create = $this->actingAs($admin, 'sanctum')->post('/api/admin/checkout-items', [
            'source' => 'new',
            'name' => 'Жилет',
            'price' => 3500,
            'gender' => 'boys',
            'category' => 'vests',
            'is_active' => '1',
            'media' => [
                UploadedFile::fake()->createWithContent('vest.png', $png),
            ],
        ], ['Accept' => 'application/json']);

        $create->assertCreated();
        $productId = $create->json('item.product_id');
        $product = Product::query()->with('media')->findOrFail($productId);
        $this->assertNotNull($product->image);
        $this->assertCount(1, $product->media);
        $this->assertSame($product->image, $product->media->first()->path);
        $this->assertSame($product->image, $create->json('item.image'));
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_admin_can_reorder_checkout_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = CheckoutItem::query()->create([
            'name' => 'Первая',
            'price' => 1000,
            'gender' => 'all',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $second = CheckoutItem::query()->create([
            'name' => 'Вторая',
            'price' => 2000,
            'gender' => 'all',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson('/api/admin/checkout-items/reorder', [
            'ids' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $names = collect($response->json('items'))->pluck('name')->all();
        $this->assertSame(['Вторая', 'Первая'], $names);
        $this->assertSame(0, (int) $second->fresh()->sort_order);
        $this->assertSame(1, (int) $first->fresh()->sort_order);
    }
}
