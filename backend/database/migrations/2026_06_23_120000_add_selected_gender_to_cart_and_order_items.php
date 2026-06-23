<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const USER_PRODUCTS_UNIQUE_CONSTRAINTS = [
        'user_products_user_product_variant_unique',
        'user_products_user_product_size_color_class_unique',
        'user_products_user_product_size_unique',
        'user_products_user_id_product_id_unique',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('user_products', 'selected_gender')) {
            Schema::table('user_products', function (Blueprint $table) {
                $table->string('selected_gender', 8)->nullable()->after('selected_class');
            });
        }

        $this->dropUserProductsUniqueConstraints();

        if (! $this->userProductsVariantUniqueExists()) {
            Schema::table('user_products', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'product_id', 'selected_size', 'selected_color', 'selected_class', 'selected_gender'],
                    'user_products_user_product_variant_unique'
                );
            });
        }

        if (! Schema::hasColumn('order_items', 'selected_gender')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('selected_gender', 8)->nullable()->after('selected_class');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'selected_gender')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('selected_gender');
            });
        }

        $this->dropUserProductsUniqueConstraints();

        if (Schema::hasColumn('user_products', 'selected_gender')) {
            if (! $this->userProductsSizeColorClassUniqueExists()) {
                Schema::table('user_products', function (Blueprint $table) {
                    $table->unique(
                        ['user_id', 'product_id', 'selected_size', 'selected_color', 'selected_class'],
                        'user_products_user_product_size_color_class_unique'
                    );
                });
            }

            Schema::table('user_products', function (Blueprint $table) {
                $table->dropColumn('selected_gender');
            });
        }
    }

    private function dropUserProductsUniqueConstraints(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                DO $$
                DECLARE constraint_name text;
                BEGIN
                    FOR constraint_name IN
                        SELECT con.conname
                        FROM pg_constraint con
                        INNER JOIN pg_class rel ON rel.oid = con.conrelid
                        INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                        WHERE nsp.nspname = current_schema()
                          AND rel.relname = 'user_products'
                          AND con.contype = 'u'
                    LOOP
                        EXECUTE format('ALTER TABLE user_products DROP CONSTRAINT IF EXISTS %I', constraint_name);
                    END LOOP;
                END $$;
                SQL);

            return;
        }

        foreach (self::USER_PRODUCTS_UNIQUE_CONSTRAINTS as $constraint) {
            if ($this->constraintExists('user_products', $constraint)) {
                Schema::table('user_products', function (Blueprint $table) use ($constraint) {
                    $table->dropUnique($constraint);
                });
            }
        }
    }

    private function userProductsVariantUniqueExists(): bool
    {
        return $this->constraintExists('user_products', 'user_products_user_product_variant_unique');
    }

    private function userProductsSizeColorClassUniqueExists(): bool
    {
        return $this->constraintExists('user_products', 'user_products_user_product_size_color_class_unique');
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'select 1 from pg_constraint where conname = ? limit 1',
                [$constraint],
            );

            return $row !== null;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("pragma index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $constraint) {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'select 1 from information_schema.table_constraints
             where table_schema = ? and table_name = ? and constraint_name = ?
             limit 1',
            [$database, $table, $constraint],
        );

        return $row !== null;
    }
};
