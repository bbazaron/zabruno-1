<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('total_amount');
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->string('yookassa_payment_id');
            $table->string('yookassa_refund_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('reason_code', 64);
            $table->string('reason_comment', 500)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('idempotence_key', 100)->unique();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->unique('yookassa_refund_id');
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });
    }
};
