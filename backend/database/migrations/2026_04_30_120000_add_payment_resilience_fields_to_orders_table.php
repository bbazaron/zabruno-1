<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('yookassa_idempotence_key', 100)->nullable()->after('yookassa_payment_status');
            $table->unique('yookassa_idempotence_key');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['yookassa_idempotence_key']);
            $table->dropColumn('yookassa_idempotence_key');
        });
    }
};
