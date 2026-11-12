<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\JsonResponse;

class SchoolController extends Controller
{
    public function index(): JsonResponse
    {
        $schools = School::query()
            ->orderByRaw("case when city = '' then 1 else 0 end")
            ->orderBy('city')
            ->orderBy('name')
            ->get(['id', 'city', 'name']);

        return response()->json([
            'schools' => $schools,
        ]);
    }
}
