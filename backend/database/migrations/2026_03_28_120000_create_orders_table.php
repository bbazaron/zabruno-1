<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Заказ оформления школьной формы
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 32)->default('pending');
            // pending, confirmed, cancelled, completed — расширяется по бизнес-логике

            // Шаг 1. Данные ребёнка
            $table->string('child_full_name');
            $table->string('child_gender', 16); // boy | girl
            $table->string('settlement');
            $table->string('school');
            $table->string('class_num');
            $table->string('class_letter')->nullable();
            $table->string('school_year');

            // Шаг 2. Размер
            $table->string('size_from_table');
            $table->string('height_cm')->nullable();
            $table->string('chest_cm')->nullable();
            $table->string('waist_cm')->nullable();
            $table->string('hips_cm')->nullable();
            $table->text('figure_comment')->nullable();

            // Общий комментарий к комплекту (позиции — в order_items)
            $table->text('kit_comment')->nullable();

            // Шаг 4. Данные родителя
            $table->string('parent_full_name');
            $table->string('parent_phone');
            $table->string('parent_email');
            $table->string('messenger_max')->nullable();
            $table->string('messenger_telegram')->nullable();

            // Шаг 5. Получение
            $table->boolean('recipient_is_customer')->default(true);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone');

            // Шаг 6. Подтверждение
            $table->boolean('terms_accepted')->default(false);

            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
