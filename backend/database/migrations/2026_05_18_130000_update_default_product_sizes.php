<?php

use App\Support\ProductSizes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $newDefault = json_encode(ProductSizes::DEFAULT_SIZES, JSON_UNESCAPED_UNICODE);
        $legacyDefault = ProductSizes::normalizeList(['128', '134', '140', '146', '152', '158']);

        foreach (DB::table('products')->select('id', 'sizes')->get() as $product) {
            $current = ProductSizes::normalizeList($product->sizes);

            if ($current === [] || $current === $legacyDefault) {
                DB::table('products')->where('id', $product->id)->update(['sizes' => $newDefault]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op: do not revert product-specific size rows
    }
};
