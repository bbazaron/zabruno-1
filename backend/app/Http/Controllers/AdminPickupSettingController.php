<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\PickupAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPickupSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'pickup_address' => PickupAddress::defaultAddress(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => ['required', 'string', 'max:500'],
        ]);

        Setting::setValue(
            PickupAddress::SETTING_KEY,
            PickupAddress::normalize((string) $validated['pickup_address']),
        );

        return response()->json([
            'pickup_address' => PickupAddress::defaultAddress(),
            'message' => 'Адрес получения сохранён',
        ]);
    }
}
