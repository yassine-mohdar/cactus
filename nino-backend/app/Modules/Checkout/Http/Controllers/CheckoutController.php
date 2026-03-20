<?php

namespace App\Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Checkout\Http\Requests\ProcessCheckoutRequest;
use App\Modules\Checkout\Services\CheckoutService;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function process(ProcessCheckoutRequest $request, CheckoutService $service)
    {
        try {
            $order = $service->processCheckout(
                $request->validated(),
                $request->user('sanctum'),
                $request->header('X-Cart-Session-Id', $request->input('cart_session_id'))
            );

            return response()->json([
                'message' => 'Checkout processed successfully',
                'order_reference' => $order->reference_number,
                // Do not return raw DB IDs, only the reference for the frontend to track
            ], 201);

        } catch (Exception $e) {
            Log::error('Checkout processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Checkout failed.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 422);
        }
    }
}
