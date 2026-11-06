<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('settings')->where('key', 'home_order_buttons_visible')->value('value');
        $value = in_array(strtolower(trim((string) $legacy)), ['0', 'false', 'no'], true) ? '0' : '1';

        foreach (['home_button_custom_size_visible', 'home_button_catalog_order_visible'] as $key) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }

        DB::table('settings')->where('key', 'home_order_buttons_visible')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'home_button_custom_size_visible',
            'home_button_catalog_order_visible',
        ])->delete();
    }
};
