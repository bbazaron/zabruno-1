<?php

namespace App\Http\Controllers;

use App\Support\HomeOrderButtons;
use Illuminate\Http\JsonResponse;

class HomeOrderButtonsSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(HomeOrderButtons::state());
    }
}
