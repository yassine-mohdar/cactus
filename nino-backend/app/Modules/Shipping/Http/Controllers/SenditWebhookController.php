<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Services\SenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenditWebhookController extends Controller
{
    public function __invoke(Request $request, ShippingCarrier $carrier, SenditService $senditService): JsonResponse
    {
        abort_unless($carrier->isSendit(), 404);

        $expectedSecret = trim((string) $carrier->setting('webhook_secret'));
        $expectedApiKey = trim((string) $carrier->setting('webhook_api_key'));
        $providedSecret = trim((string) (
            $request->query('secret')
            ?: $request->header('X-Sendit-Webhook-Secret')
            ?: data_get($request->all(), 'secret')
        ));
        $providedApiKey = trim((string) (
            $request->query('api_key')
            ?: $request->header('X-Sendit-Api-Key')
            ?: data_get($request->all(), 'api_key')
        ));

        abort_unless($expectedSecret !== '' && hash_equals($expectedSecret, $providedSecret), 403);
        abort_unless($expectedApiKey === '' || hash_equals($expectedApiKey, $providedApiKey), 403);

        $shipment = $senditService->syncWebhookDelivery($carrier, $request->all());

        return response()->json([
            'success' => true,
            'matched' => (bool) $shipment,
            'shipment_id' => $shipment?->id,
        ], $shipment ? 200 : 202);
    }
}
