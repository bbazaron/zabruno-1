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
            $query->where('gender', $request->gender);
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
            $mediaUrls = $product->media
                ->map(fn ($m) => $this->toPublicMediaUrl($m->path))
                ->filter(fn ($u) => is_string($u) && $u !== '')
                ->values()
                ->all();
            if (empty($mediaUrls) && ! empty($product->image)) {
                $mediaUrls[] = $this->toPublicMediaUrl($product->image);
            }
            $primaryImage = $mediaUrls[0] ?? $this->toPublicMediaUrl($product->image);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $primaryImage,
                'media' => $mediaUrls,
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

        $mediaUrls = $product->media
            ->map(fn ($m) => $this->toPublicMediaUrl($m->path))
            ->filter(fn ($u) => is_string($u) && $u !== '')
            ->values()
            ->all();
        if (empty($mediaUrls) && ! empty($product->image)) {
            $mediaUrls[] = $this->toPublicMediaUrl($product->image);
        }
        $primaryImage = $mediaUrls[0] ?? $product->image;

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
            'media' => $mediaUrls,
            'inStock' => $product->in_stock,
        ]);
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
