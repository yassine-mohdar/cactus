<?php

namespace App\Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Checkout\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(Request $request, CartService $service)
    {
        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        return response()->json([
            'cart' => $service->getSummary($cart),
            // Send session ID back so frontend can persist it if we generated a new one
            'session_id' => $cart->session_id, 
        ]);
    }

    public function addItem(Request $request, CartService $service)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        $service->addItem(
            $cart, 
            $request->product_id, 
            $request->variant_id, 
            $request->quantity ?? 1
        );

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => $service->getSummary($cart)
        ]);
    }

    public function updateItem(Request $request, int $itemId, CartService $service)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        $service->updateQuantity($cart, $itemId, $request->quantity);

        return response()->json([
            'cart' => $service->getSummary($cart)
        ]);
    }

    public function removeItem(Request $request, int $itemId, CartService $service)
    {
        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        $service->removeItem($cart, $itemId);

        return response()->json([
            'message' => 'Item removed',
            'cart' => $service->getSummary($cart)
        ]);
    }

    public function applyCoupon(Request $request, CartService $service)
    {
        $request->validate(['code' => 'required|string']);

        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        $result = $service->applyCoupon($cart, $request->code, $request->user('sanctum'));

        if (! ($result['valid'] ?? false)) {
            return response()->json([
                'message' => $result['error'] ?? 'Coupon could not be applied.',
                'error_code' => $result['error_code'] ?? 'COUPON_INVALID',
                'cart' => $service->getSummary($cart->fresh()),
            ], 422);
        }

        return response()->json([
            'message' => 'Coupon applied',
            'cart' => $service->getSummary($cart->fresh())
        ]);
    }

    public function removeCoupon(Request $request, CartService $service)
    {
        $cart = $service->getCart(
            $request->user('sanctum'), 
            $request->header('X-Cart-Session-Id')
        );

        $service->removeCoupon($cart);

        return response()->json([
            'message' => 'Coupon removed',
            'cart' => $service->getSummary($cart)
        ]);
    }
}
