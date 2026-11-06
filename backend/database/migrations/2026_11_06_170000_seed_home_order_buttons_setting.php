<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'home_order_buttons_visible')->exists()) {
            DB::table('settings')->insert([
                'key' => 'home_order_buttons_visible',
                'value' => '1',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'home_order_buttons_visible')->delete();
    }
};
