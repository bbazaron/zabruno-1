<?php

namespace Tests\Feature;

use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\ProductMedia;
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
            'description' => 'Тёплый жилет',
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
        $vest = collect($response->json())->firstWhere('name', 'Жилет');
        $this->assertSame('Тёплый жилет', $vest['description']);
        $this->assertSame('3500.00', $vest['price']);
    }

    public function test_copy_from_catalog_keeps_its_own_price_and_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('products/vest.png', $png);

        $product = Product::query()->create([
            'name' => 'Жилет школьный',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 1000,
            'description' => 'Описание из каталога',
            'image' => 'products/vest.png',
            'sizes' => ['32', '34'],
            'in_stock' => true,
        ]);
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'path' => 'products/vest.png',
            'sort_order' => 0,
            'catalog_zoom' => 120,
            'catalog_pan_x' => 5,
            'catalog_pan_y' => -5,
        ]);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/checkout-items', [
            'name' => 'Жилет на мерки',
            'price' => 2500,
            'description' => 'Пошив по меркам',
            'gender' => 'boys',
            'copy_from_product_id' => $product->id,
            'copy_media' => true,
            'is_active' => true,
        ]);

        $create->assertCreated();
        $create->assertJsonPath('item.price', '2500.00');
        $create->assertJsonPath('item.description', 'Пошив по меркам');
        $this->assertCount(1, $create->json('item.media'));
        $copiedPath = $create->json('item.media.0.path');
        $this->assertNotSame('products/vest.png', $copiedPath);
        $this->assertSame(120, $create->json('item.media.0.catalog_zoom'));
        Storage::disk('public')->assertExists($copiedPath);
        Storage::disk('public')->assertExists('products/vest.png');

        $product->refresh();
        $this->assertSame('1000.00', number_format((float) $product->price, 2, '.', ''));
        $this->assertSame('Жилет школьный', $product->name);
        $this->assertSame(1, Product::query()->count());

        $again = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/checkout-items', [
            'name' => 'Жилет на мерки, второй',
            'price' => 2700,
            'gender' => 'boys',
            'copy_from_product_id' => $product->id,
            'copy_media' => false,
        ]);
        $again->assertCreated();
        $this->assertSame([], $again->json('item.media'));
        $this->assertSame(2, CheckoutItem::query()->count());

        $public = $this->getJson('/api/checkout-items?gender=boy');
        $public->assertOk();
        $row = collect($public->json())->firstWhere('name', 'Жилет на мерки');
        $this->assertSame('2500.00', $row['price']);
        $this->assertCount(1, $row['media']);
        $this->assertSame(120, $row['media'][0]['catalog_zoom']);
    }

    public function test_copy_keeps_only_selected_catalog_photos(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('products/a.png', $png);
        Storage::disk('public')->put('products/b.png', $png);

        $product = Product::query()->create([
            'name' => 'Жилет школьный',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 1000,
            'image' => 'products/a.png',
            'sizes' => ['32'],
            'in_stock' => true,
        ]);
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'path' => 'products/a.png',
            'sort_order' => 0,
        ]);
        $second = ProductMedia::query()->create([
            'product_id' => $product->id,
            'path' => 'products/b.png',
            'sort_order' => 1,
            'catalog_zoom' => 110,
        ]);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/checkout-items', [
            'name' => 'Жилет на мерки',
            'price' => 2500,
            'gender' => 'boys',
            'copy_from_product_id' => $product->id,
            'copy_media' => false,
            'media_sequence' => ['source:'.$second->id],
            'media_catalog_zoom' => ['source:'.$second->id => 180],
            'media_catalog_pan_x' => ['source:'.$second->id => 10],
            'media_catalog_pan_y' => ['source:'.$second->id => -4],
        ]);

        $create->assertCreated();
        $this->assertCount(1, $create->json('item.media'));
        $this->assertSame(180, $create->json('item.media.0.catalog_zoom'));
        $this->assertSame(10, $create->json('item.media.0.catalog_pan_x'));
        $this->assertNotSame('products/b.png', $create->json('item.media.0.path'));
        $this->assertSame(2, $product->media()->count());
    }

    public function test_admin_can_create_sewing_card_without_touching_catalog(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $create = $this->actingAs($admin, 'sanctum')->post('/api/admin/checkout-items', [
            'name' => 'Жилет',
            'price' => 3500,
            'description' => 'Два фото',
            'gender' => 'boys',
            'is_active' => '1',
            'media' => [
                UploadedFile::fake()->createWithContent('vest-a.png', $png),
                UploadedFile::fake()->createWithContent('vest-b.png', $png),
            ],
        ], ['Accept' => 'application/json']);

        $create->assertCreated();
        $this->assertSame(0, Product::query()->count());
        $this->assertCount(2, $create->json('item.media'));
        $item = CheckoutItem::query()->with('media')->findOrFail($create->json('item.id'));
        $this->assertSame($item->media->first()->path, $item->image);
        Storage::disk('public')->assertExists($item->media[0]->path);
        Storage::disk('public')->assertExists($item->media[1]->path);
        $this->assertStringStartsWith('checkout-items/', $item->media[0]->path);
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
