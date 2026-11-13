<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('yookassa_payment_id')->nullable()->unique();
            $table->string('idempotence_key', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->nullable();
            $table->text('confirmation_url')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
