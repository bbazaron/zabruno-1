<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('color')->nullable()->change();
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->string('selected_color', 255)->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('selected_color', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('selected_color', 50)->nullable()->change();
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->string('selected_color', 50)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->change();
        });
    }
};
