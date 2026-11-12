<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_items', function (Blueprint $table) {
            if (! Schema::hasColumn('checkout_items', 'description')) {
                $table->text('description')->nullable()->after('price');
            }
        });

        if (! Schema::hasTable('checkout_item_media')) {
            Schema::create('checkout_item_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('checkout_item_id')->constrained('checkout_items')->cascadeOnDelete();
                $table->string('path');
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedSmallInteger('catalog_zoom')->default(100);
                $table->smallInteger('catalog_pan_x')->default(0);
                $table->smallInteger('catalog_pan_y')->default(0);
                $table->timestamps();
            });
        }

        $this->snapshotCatalogIntoCheckoutItems();

        if (Schema::hasColumn('checkout_items', 'product_id')) {
            Schema::table('checkout_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->dropUnique(['product_id']);
                $table->dropColumn('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_item_media');

        Schema::table('checkout_items', function (Blueprint $table) {
            if (Schema::hasColumn('checkout_items', 'description')) {
                $table->dropColumn('description');
            }
            if (! Schema::hasColumn('checkout_items', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('products')
                    ->nullOnDelete();
                $table->unique('product_id');
            }
        });
    }

    private function snapshotCatalogIntoCheckoutItems(): void
    {
        if (! Schema::hasColumn('checkout_items', 'product_id')) {
            return;
        }

        $items = DB::table('checkout_items')->orderBy('id')->get();
        foreach ($items as $item) {
            $product = $item->product_id
                ? DB::table('products')->where('id', $item->product_id)->first()
                : null;

            $name = trim((string) ($product->name ?? ''));
            if ($name === '') {
                $name = trim((string) $item->name);
            }

            $price = $product !== null && $product->price !== null ? $product->price : $item->price;
            $gender = $this->genderOrFallback($product->gender ?? null, (string) $item->gender);
            $description = $product !== null ? $product->description : null;

            $sources = [];
            if ($product !== null) {
                $sources = DB::table('product_media')
                    ->where('product_id', $product->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($media): array => [
                        'path' => (string) $media->path,
                        'sort_order' => (int) $media->sort_order,
                        'catalog_zoom' => (int) ($media->catalog_zoom ?? 100),
                        'catalog_pan_x' => (int) ($media->catalog_pan_x ?? 0),
                        'catalog_pan_y' => (int) ($media->catalog_pan_y ?? 0),
                    ])
                    ->all();
            }

            if ($sources === []) {
                $fallback = trim((string) ($product->image ?? $item->image ?? ''));
                if ($fallback !== '') {
                    $sources[] = [
                        'path' => $fallback,
                        'sort_order' => 0,
                        'catalog_zoom' => 100,
                        'catalog_pan_x' => 0,
                        'catalog_pan_y' => 0,
                    ];
                }
            }

            $primary = null;
            $sort = 0;
            foreach ($sources as $source) {
                $copied = $this->copyIntoCheckoutStorage($source['path']);
                if ($copied === null) {
                    continue;
                }
                DB::table('checkout_item_media')->insert([
                    'checkout_item_id' => $item->id,
                    'path' => $copied,
                    'sort_order' => $sort,
                    'catalog_zoom' => $source['catalog_zoom'],
                    'catalog_pan_x' => $source['catalog_pan_x'],
                    'catalog_pan_y' => $source['catalog_pan_y'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $primary ??= $copied;
                $sort++;
            }

            DB::table('checkout_items')->where('id', $item->id)->update([
                'name' => $name !== '' ? $name : $item->name,
                'price' => $price,
                'description' => $description,
                'gender' => $gender,
                'image' => $primary ?? $item->image,
            ]);
        }
    }

    private function genderOrFallback(mixed $preferred, string $fallback): string
    {
        $preferred = is_string($preferred) ? trim($preferred) : '';
        if (in_array($preferred, ['boys', 'girls', 'all'], true)) {
            return $preferred;
        }
        $fallback = trim($fallback);

        return in_array($fallback, ['boys', 'girls', 'all'], true) ? $fallback : 'all';
    }

    private function copyIntoCheckoutStorage(string $path): ?string
    {
        $relative = preg_replace('#^/?storage/#', '', trim($path)) ?? '';
        $relative = ltrim($relative, '/');
        if ($relative === '' || preg_match('/^https?:\/\//i', $path) === 1) {
            return null;
        }
        if (! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $extension = pathinfo($relative, PATHINFO_EXTENSION);
        $target = 'checkout-items/'.Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        Storage::disk('public')->copy($relative, $target);

        return $target;
    }
};
