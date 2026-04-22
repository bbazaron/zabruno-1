<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->string('selected_size', 8)->nullable()->after('product_id');
            $table->dropUnique('user_products_user_id_product_id_unique');
            $table->unique(['user_id', 'product_id', 'selected_size'], 'user_products_user_product_size_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->dropUnique('user_products_user_product_size_unique');
            $table->unique(['user_id', 'product_id']);
            $table->dropColumn('selected_size');
        });
    }
};
