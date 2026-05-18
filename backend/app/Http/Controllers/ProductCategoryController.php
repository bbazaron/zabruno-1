<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label', 'sort_order']);

        return response()->json(['categories' => $categories]);
    }
}
