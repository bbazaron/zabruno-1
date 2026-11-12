<?php

use App\Support\ProductSchoolColors;
use App\Support\SchoolColorLabel;
use App\Support\SchoolKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VARIANT_UNIQUE = 'user_products_user_product_variant_unique';

    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('name');
            $table->timestamps();
            $table->unique(['city', 'name']);
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('selected_gender')->constrained('schools')->nullOnDelete();
            $table->string('school_city')->nullable()->after('school_id');
            $table->string('school_name')->nullable()->after('school_city');
            $table->string('school_key', 64)->default('')->after('school_name');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('selected_gender')->constrained('schools')->nullOnDelete();
            $table->string('school_city')->nullable()->after('school_id');
            $table->string('school_name')->nullable()->after('school_city');
        });

        $this->splitCatalogSchoolColors();
        $this->dropUserProductsUniqueConstraints();
        $this->splitCartAndOrders();

        Schema::table('user_products', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'product_id', 'selected_size', 'selected_color', 'selected_class', 'selected_gender', 'school_key'],
                self::VARIANT_UNIQUE
            );
        });
    }

    public function down(): void
    {
        $this->dropUserProductsUniqueConstraints();

        Schema::table('user_products', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'product_id', 'selected_size', 'selected_color', 'selected_class', 'selected_gender'],
                self::VARIANT_UNIQUE
            );
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['school_city', 'school_name']);
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['school_city', 'school_name', 'school_key']);
        });

        Schema::dropIfExists('schools');
    }

    private function splitCatalogSchoolColors(): void
    {
        $schoolNames = [];
        $colors = [];

        $remember = static function (string $raw) use (&$schoolNames, &$colors): void {
            $parts = SchoolColorLabel::split($raw);
            if ($parts['split'] && $parts['school'] !== '') {
                $schoolNames[$parts['school']] = $parts['school'];
            }
            if ($parts['color'] !== '') {
                $colors[$parts['color']] = $parts['color'];
            } elseif (! $parts['split'] && trim($raw) !== '') {
                $colors[trim($raw)] = trim($raw);
            }
        };

        foreach (ProductSchoolColors::defaults() as $option) {
            $remember($option);
        }

        $products = DB::table('products')->get(['id', 'color', 'school_color_excluded', 'school_color_extra']);
        foreach ($products as $product) {
            foreach (['color', 'school_color_excluded', 'school_color_extra'] as $column) {
                foreach (ProductSchoolColors::parseList($product->{$column}) as $option) {
                    $remember($option);
                }
            }
        }

        $now = now();
        foreach ($schoolNames as $name) {
            $exists = DB::table('schools')->where('city', '')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }
            DB::table('schools')->insert([
                'city' => '',
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        ProductSchoolColors::storeDefaults(array_values($colors));

        foreach ($products as $product) {
            DB::table('products')->where('id', $product->id)->update([
                'color' => $this->colorsOnly($product->color),
                'school_color_excluded' => $this->colorsOnly($product->school_color_excluded),
                'school_color_extra' => $this->colorsOnly($product->school_color_extra),
            ]);
        }
    }

    private function splitCartAndOrders(): void
    {
        foreach (DB::table('user_products')->orderBy('id')->get() as $row) {
            $parts = SchoolColorLabel::split((string) ($row->selected_color ?? ''));
            if ($parts['split']) {
                $this->ensureSchoolName($parts['school']);
            }
            $schoolName = $parts['split'] ? $parts['school'] : '';
            $color = $parts['split'] ? ($parts['color'] !== '' ? $parts['color'] : null) : ($row->selected_color ?: null);
            DB::table('user_products')->where('id', $row->id)->update([
                'school_name' => $schoolName !== '' ? $schoolName : null,
                'school_key' => $schoolName !== '' ? SchoolKey::forCustom('', $schoolName) : '',
                'selected_color' => $color,
            ]);
        }

        $this->mergeDuplicateCartRows();

        foreach (DB::table('order_items')->orderBy('id')->get() as $row) {
            $parts = SchoolColorLabel::split((string) ($row->selected_color ?? ''));
            if (! $parts['split']) {
                continue;
            }
            $this->ensureSchoolName($parts['school']);
            DB::table('order_items')->where('id', $row->id)->update([
                'school_name' => $parts['school'],
                'selected_color' => $parts['color'] !== '' ? $parts['color'] : null,
            ]);
        }

        $orders = DB::table('orders')
            ->where('order_type', 'ready_to_wear')
            ->where(function ($query): void {
                $query->whereNull('school')->orWhere('school', '')->orWhere('school', 'Не указано');
            })
            ->get(['id']);

        foreach ($orders as $order) {
            $names = [];
            $itemNames = DB::table('order_items')->where('order_id', $order->id)->pluck('school_name');
            foreach ($itemNames as $name) {
                $name = trim((string) $name);
                if ($name !== '' && ! in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
            if ($names === []) {
                continue;
            }
            $school = implode(', ', $names);
            if (mb_strlen($school) > 255) {
                $school = mb_substr($school, 0, 254).'…';
            }
            DB::table('orders')->where('id', $order->id)->update(['school' => $school]);
        }
    }

    private function ensureSchoolName(string $name): void
    {
        $name = trim($name);
        if ($name === '' || DB::table('schools')->where('name', $name)->exists()) {
            return;
        }

        $now = now();
        DB::table('schools')->insert([
            'city' => '',
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function colorsOnly(mixed $raw): ?string
    {
        $colors = [];
        foreach (ProductSchoolColors::parseList($raw) as $option) {
            $parts = SchoolColorLabel::split($option);
            $color = $parts['color'];
            if ($color === '' || in_array($color, $colors, true)) {
                continue;
            }
            $colors[] = $color;
        }

        return ProductSchoolColors::serializeList($colors);
    }

    private function mergeDuplicateCartRows(): void
    {
        $seen = [];
        foreach (DB::table('user_products')->orderBy('id')->get() as $row) {
            $key = implode("\0", [
                (string) $row->user_id,
                (string) $row->product_id,
                (string) $row->selected_size,
                (string) $row->selected_color,
                (string) $row->selected_class,
                (string) $row->selected_gender,
                (string) $row->school_key,
            ]);
            if (isset($seen[$key])) {
                DB::table('user_products')->where('id', $seen[$key])->increment('quantity', (int) $row->quantity);
                DB::table('user_products')->where('id', $row->id)->delete();
                continue;
            }
            $seen[$key] = $row->id;
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

        if ($this->constraintExists('user_products', self::VARIANT_UNIQUE)) {
            Schema::table('user_products', function (Blueprint $table) {
                $table->dropUnique(self::VARIANT_UNIQUE);
            });
        }
    }

    private function constraintExists(string $table, string $constraint): bool
    {
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
