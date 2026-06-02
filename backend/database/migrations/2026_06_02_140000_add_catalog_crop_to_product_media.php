<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_media', function (Blueprint $table) {
            if (! Schema::hasColumn('product_media', 'catalog_zoom')) {
                $table->unsignedSmallInteger('catalog_zoom')->default(100)->after('sort_order');
            }
            if (! Schema::hasColumn('product_media', 'catalog_pan_x')) {
                $table->smallInteger('catalog_pan_x')->default(0)->after('catalog_zoom');
            }
            if (! Schema::hasColumn('product_media', 'catalog_pan_y')) {
                $table->smallInteger('catalog_pan_y')->default(0)->after('catalog_pan_x');
            }
        });

        if (Schema::hasColumn('product_media', 'catalog_focus_y')) {
            Schema::table('product_media', function (Blueprint $table) {
                $table->dropColumn('catalog_focus_y');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_media', function (Blueprint $table) {
            if (Schema::hasColumn('product_media', 'catalog_pan_y')) {
                $table->dropColumn('catalog_pan_y');
            }
            if (Schema::hasColumn('product_media', 'catalog_pan_x')) {
                $table->dropColumn('catalog_pan_x');
            }
            if (Schema::hasColumn('product_media', 'catalog_zoom')) {
                $table->dropColumn('catalog_zoom');
            }
        });
    }
};
