<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'default_school_colors')->exists()) {
            DB::table('settings')->insert([
                'key' => 'default_school_colors',
                'value' => "Школа №2, черный\nШкола №3, белый",
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'default_school_colors')->delete();
    }
};
