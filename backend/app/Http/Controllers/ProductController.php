<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductSales;
use App\Support\ProductSchoolColors;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request) //каталог
    {
        $query = Product::query()->with('media');

        // Фильтр по полу
        if ($request->has('gender') && $request->gender !== 'all') {
            $query->where(function ($genderQuery) use ($request): void {
                $genderQuery->where('gender', $request->gender)
                    ->orWhere('gender', 'all');
            });
        }

        // Фильтр по категории
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $sortBy = $request->input('sortBy', 'popular');

        switch ($sortBy) {
            case 'price-asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price-desc':
                $query->orderBy('price', 'desc');
                break;
            case 'new':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
            default:
                ProductSales::applySalesCountSubquery($query)
                    ->orderByDesc('sales_count')
                    ->orderBy('products.id');
                break;
        }

        $products = $query->get();

        $payload = $products->map(function (Product $product) {
            $mediaItems = $this->formatCatalogMediaItems($product);
            $primaryImage = $mediaItems[0]['url'] ?? $this->toPublicMediaUrl($product->image);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $primaryImage,
                'media' => $mediaItems,
                'gender' => $product->gender,
                'category' => $product->category,
                'color' => ProductSchoolColors::serializeList($product->schoolColorsList()),
                'sizes' => $product->sizesList(),
                'price' => $product->price,
                'original_price' => $product->original_price,
                'in_stock' => $product->in_stock,
            ];
        });

        return response()->json($payload);
    }

    public function getProductPage($id)
    {
        $product = Product::with('media')->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Товар не найден'
            ], 404);
        }

        $mediaItems = $this->formatCatalogMediaItems($product);
        $primaryImage = $mediaItems[0]['url'] ?? $this->toPublicMediaUrl($product->image);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'originalPrice' => $product->original_price,
            'color' => ProductSchoolColors::serializeList($product->schoolColorsList()),
            'sizes' => $product->sizesList(),
            'season' => $product->season,
            'category' => $product->category,
            'gender' => $product->gender,
            'image' => $primaryImage,
            'media' => $mediaItems,
            'inStock' => $product->in_stock,
        ]);
    }

    /**
     * @return list<array{url: string, catalog_zoom: int, catalog_pan_x: int, catalog_pan_y: int}>
     */
    private function formatCatalogMediaItems(Product $product): array
    {
        $items = $product->media
            ->map(function ($media): ?array {
                $url = $this->toPublicMediaUrl($media->path);
                if (! is_string($url) || $url === '') {
                    return null;
                }

                return [
                    'url' => $url,
                    'catalog_zoom' => $this->clampCatalogZoom($media->catalog_zoom),
                    'catalog_pan_x' => $this->clampCatalogPan($media->catalog_pan_x),
                    'catalog_pan_y' => $this->clampCatalogPan($media->catalog_pan_y),
                ];
            })
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        $fallbackUrl = $this->toPublicMediaUrl($product->image);
        if (! is_string($fallbackUrl) || $fallbackUrl === '') {
            return [];
        }

        return [[
            'url' => $fallbackUrl,
            'catalog_zoom' => 100,
            'catalog_pan_x' => 0,
            'catalog_pan_y' => 0,
        ]];
    }

    private function clampCatalogZoom(mixed $value): int
    {
        $zoom = (int) $value;
        if ($zoom < 100) {
            return 100;
        }
        if ($zoom > 250) {
            return 250;
        }

        return $zoom;
    }

    private function clampCatalogPan(mixed $value): int
    {
        $pan = (int) $value;
        if ($pan < -100) {
            return -100;
        }
        if ($pan > 100) {
            return 100;
        }

        return $pan;
    }

    private function toPublicMediaUrl(?string $path): ?string
    {
        $path = trim((string) ($path ?? ''));
        if ($path === '' || $path === '0' || strtolower($path) === 'null') {
            return null;
        }
        if (str_starts_with($path, '/storage/')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            if (is_string($urlPath) && $urlPath !== '') {
                $storagePos = strpos($urlPath, '/storage/');
                if ($storagePos !== false) {
                    return substr($urlPath, $storagePos);
                }
            }

            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }
}
