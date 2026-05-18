<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{id: string, label: string, sort_order: int}>
     */
    private const CATEGORIES = [
        ['id' => 'cardigans', 'label' => 'Кардиганы', 'sort_order' => 1],
        ['id' => 'vests', 'label' => 'Жилеты', 'sort_order' => 2],
        ['id' => 'skirts', 'label' => 'Юбки', 'sort_order' => 3],
        ['id' => 'pants', 'label' => 'Брюки', 'sort_order' => 4],
        ['id' => 'sets', 'label' => 'Комплекты', 'sort_order' => 5],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::CATEGORIES as $category) {
            if (! DB::table('product_categories')->where('id', $category['id'])->exists()) {
                DB::table('product_categories')->insert($category);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('product_categories')->whereIn(
            'id',
            array_column(self::CATEGORIES, 'id'),
        )->delete();
    }
};
