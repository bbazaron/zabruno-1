<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('pickup_address')->nullable()->after('recipient_phone');
        });

        $default = DB::table('settings')->where('key', 'pickup_address')->value('value');
        if (is_string($default) && trim($default) !== '') {
            DB::table('orders')->whereNull('pickup_address')->update(['pickup_address' => trim($default)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_address');
        });
    }
};
