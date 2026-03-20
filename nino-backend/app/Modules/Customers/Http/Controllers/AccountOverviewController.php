<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountOverviewController extends Controller
{
    /**
     * Retrieve the customer's account overview data.
     * Stubs out Order History until the Orders module is built.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('addresses');
        
        // P6-ACCOUNT-01 / 02 / 03 / 04 
        // These rely on the Orders module, which is targeted for Phase 7/8.
        // We stub these values so the frontend can build against the API design safely.
        $recentOrders = [];
        $totalOrders = 0;
        $activeOrders = [];

        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->full_name, // Computed attribute
                'email' => $user->email,
                'avatar' => $user->avatar,
                'marketing_opt_in' => $user->marketing_opt_in,
            ],
            'addresses' => $user->addresses,
            'orders' => [
                'total' => $totalOrders,
                'recent' => $recentOrders,
                'active' => $activeOrders,
                'notice' => 'Order history is temporarily unavailable as the module is under construction.'
            ]
        ]);
    }
}
