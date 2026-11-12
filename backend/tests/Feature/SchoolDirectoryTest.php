<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\School;
use App\Models\User;
use App\Support\ProductSchoolColors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_school_colors_are_split_into_directory_and_colors(): void
    {
        $this->assertDatabaseHas('schools', [
            'city' => '',
            'name' => 'Школа №2',
        ]);
        $this->assertDatabaseHas('schools', [
            'city' => '',
            'name' => 'Школа №3',
        ]);

        $colors = ProductSchoolColors::defaults();
        $this->assertContains('черный', $colors);
        $this->assertContains('белый', $colors);
        $this->assertNotContains('Школа №2, черный', $colors);
    }

    public function test_public_list_includes_schools_even_without_a_city(): void
    {
        School::query()->create([
            'city' => 'Казань',
            'name' => 'Лицей 1',
        ]);

        $response = $this->getJson('/api/schools');

        $response->assertOk();
        $names = collect($response->json('schools'))->pluck('name')->all();
        $this->assertContains('Лицей 1', $names);
        $this->assertContains('Школа №2', $names);
    }

    public function test_catalog_order_copies_city_and_school_onto_the_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $headers = ['Authorization' => 'Bearer '.$admin->createToken('test')->plainTextToken];
        $product = Product::query()->create([
            'name' => 'Жилет',
            'category' => 'vests',
            'gender' => 'boys',
            'price' => 0,
            'in_stock' => true,
            'sizes' => ['32'],
        ]);

        $this->postJson('/api/admin/schools', [
            'city' => 'Казань',
            'name' => 'Гимназия 5',
        ], $headers)->assertCreated();

        $schoolId = (int) School::query()->where('name', 'Гимназия 5')->value('id');

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'selected_size' => '32',
            'selected_color' => 'синий',
            'school_id' => $schoolId,
        ], $headers)->assertCreated();

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'selected_size' => '34',
            'school_city' => 'Москва',
            'school_name' => 'Школа у дома',
        ], $headers)->assertCreated();

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'selected_size' => '36',
        ], $headers)->assertStatus(422);

        $orderResponse = $this->postJson('/api/createCartOrder', [
            'parent_full_name' => 'Иванова Мария',
            'parent_phone' => '+79990001122',
            'parent_email' => $admin->email,
        ], $headers);

        $orderResponse->assertCreated();
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('Казань, Москва', $order->settlement);
        $this->assertSame('Гимназия 5, Школа у дома', $order->school);
        $this->assertSame('Гимназия 5', $order->items[0]->school_name);
        $this->assertSame('Казань', $order->items[0]->school_city);
        $this->assertSame('синий', $order->items[0]->selected_color);
        $this->assertSame('Школа у дома', $order->items[1]->school_name);
        $this->assertSame('Москва', $order->items[1]->school_city);
    }
}
