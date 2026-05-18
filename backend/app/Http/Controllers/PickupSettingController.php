<?php

namespace App\Http\Controllers;

use App\Support\PickupAddress;
use Illuminate\Http\JsonResponse;

class PickupSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'pickup_address' => PickupAddress::defaultAddress(),
        ]);
    }
}
