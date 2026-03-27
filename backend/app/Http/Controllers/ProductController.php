<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request) //каталог
    {
        $query = Product::query();

        // Фильтр по полу
        if ($request->has('gender') && $request->gender !== 'all') {
            $query->where('gender', $request->gender);
        }

        // Фильтр по категории
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Можно добавить сортировку
        if ($request->has('sortBy')) {
            switch ($request->sortBy) {
                case 'price-asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price-desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    break;
            }
        }

        $products = $query->get();

        return response()->json($products);
    }

    public function getProductPage($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Товар не найден'
            ], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'originalPrice' => $product->original_price,
            'category' => $product->category,
            'gender' => $product->gender,
            'image' => $product->image,
//            'media' => $product->media->map(fn($m) => $m->url), // массив ссылок на изображения/видео
            'inStock' => $product->in_stock,
        ]);
    }
}
