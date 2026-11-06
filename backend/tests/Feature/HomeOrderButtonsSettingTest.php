<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeOrderButtonsSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buttons_are_visible_by_default(): void
    {
        $this->getJson('/api/settings/home-order-buttons')
            ->assertOk()
            ->assertJsonPath('custom_size', true)
            ->assertJsonPath('catalog_order', true);
    }

    public function test_admin_can_hide_each_home_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/settings/home-order-buttons', [
                'custom_size' => false,
                'catalog_order' => true,
            ])
            ->assertOk()
            ->assertJsonPath('custom_size', false)
            ->assertJsonPath('catalog_order', true);

        $this->getJson('/api/settings/home-order-buttons')
            ->assertOk()
            ->assertJsonPath('custom_size', false)
            ->assertJsonPath('catalog_order', true);
    }

    public function test_guest_cannot_change_home_order_buttons(): void
    {
        $this->patchJson('/api/admin/settings/home-order-buttons', [
            'custom_size' => false,
            'catalog_order' => false,
        ])->assertUnauthorized();
    }
}
