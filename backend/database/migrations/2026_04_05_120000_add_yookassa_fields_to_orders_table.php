<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('yookassa_payment_id')->nullable()->after('total_amount');
            $table->string('yookassa_payment_status', 64)->nullable()->after('yookassa_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['yookassa_payment_id', 'yookassa_payment_status']);
        });
    }
};
