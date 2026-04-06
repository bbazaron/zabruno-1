<?php

namespace App\Http\Controllers;

use App\Services\YooKassaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-уведомления ЮKassa
 */
class YooKassaController extends Controller
{
    public function __construct(
        private YooKassaClient $yooKassa,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON'], 400);
        }

        $result = $this->yooKassa->handleWebhook($payload);

        return response()->json($result['json'], $result['http']);
    }
}
