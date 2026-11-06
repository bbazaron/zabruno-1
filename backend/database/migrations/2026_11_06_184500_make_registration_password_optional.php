<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registration_codes')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE registration_codes ALTER COLUMN password DROP NOT NULL');
        }

        if (! Schema::hasColumn('registration_codes', 'completion_token')) {
            Schema::table('registration_codes', function (Blueprint $table) {
                $table->string('completion_token')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('registration_codes')) {
            return;
        }

        if (Schema::hasColumn('registration_codes', 'completion_token')) {
            Schema::table('registration_codes', function (Blueprint $table) {
                $table->dropColumn('completion_token');
            });
        }
    }
};
