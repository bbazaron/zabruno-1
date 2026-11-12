<?php

use App\Support\ProductSchoolColors;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $colors = array_values(array_filter(
            ProductSchoolColors::defaults(),
            fn (string $item): bool => ! $this->isSchoolLabel($item),
        ));
        foreach (ProductSchoolColors::defaults() as $item) {
            if ($this->isSchoolLabel($item)) {
                $this->ensureSchoolName($item);
            }
        }
        ProductSchoolColors::storeDefaults($colors);

        $products = DB::table('products')->get(['id', 'color', 'school_color_excluded', 'school_color_extra']);
        foreach ($products as $product) {
            foreach (['color', 'school_color_excluded', 'school_color_extra'] as $column) {
                foreach (ProductSchoolColors::parseList($product->{$column}) as $item) {
                    if ($this->isSchoolLabel($item)) {
                        $this->ensureSchoolName($item);
                    }
                }
            }

            DB::table('products')->where('id', $product->id)->update([
                'color' => $this->withoutSchoolLabels($product->color),
                'school_color_excluded' => $this->withoutSchoolLabels($product->school_color_excluded),
                'school_color_extra' => $this->withoutSchoolLabels($product->school_color_extra),
            ]);
        }
    }

    public function down(): void
    {
    }

    private function isSchoolLabel(string $value): bool
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return false;
        }

        foreach (['школ', 'лицей', 'гимназ'] as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
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

    private function withoutSchoolLabels(mixed $raw): ?string
    {
        $colors = [];
        foreach (ProductSchoolColors::parseList($raw) as $item) {
            if ($this->isSchoolLabel($item) || in_array($item, $colors, true)) {
                continue;
            }
            $colors[] = $item;
        }

        return ProductSchoolColors::serializeList($colors);
    }
};
