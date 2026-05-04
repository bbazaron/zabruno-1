<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->string('selected_color', 50)->nullable()->after('selected_size');
            $table->string('selected_class', 32)->nullable()->after('selected_color');

            $table->dropUnique('user_products_user_product_size_unique');
            $table->unique(
                ['user_id', 'product_id', 'selected_size', 'selected_color', 'selected_class'],
                'user_products_user_product_size_color_class_unique'
            );
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('selected_color', 50)->nullable()->after('size_override');
            $table->string('selected_class', 32)->nullable()->after('selected_color');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['selected_color', 'selected_class']);
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->dropUnique('user_products_user_product_size_color_class_unique');
            $table->unique(['user_id', 'product_id', 'selected_size'], 'user_products_user_product_size_unique');

            $table->dropColumn(['selected_color', 'selected_class']);
        });
    }
};
