<?php

namespace App\Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Http\Request;

class CheckoutPaymentMethodController extends Controller
{
    public function __invoke(Request $request, PaymentMethodAvailabilityService $paymentMethodAvailability)
    {
        $shippingMethod = null;

        if ($request->filled('shipping_method_id')) {
            $shippingMethod = ShippingMethod::query()
                ->with('shippingCarrier')
                ->enabled()
                ->find($request->integer('shipping_method_id'));
        }

        return response()->json([
            'data' => $paymentMethodAvailability->eligibleMethodOptions($shippingMethod),
        ]);
    }
}
