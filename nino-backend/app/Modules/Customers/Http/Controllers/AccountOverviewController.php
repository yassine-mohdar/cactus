<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Services\CustomerAccountPresenter;
use Illuminate\Http\Request;

class AccountOverviewController extends Controller
{
    public function __construct(
        private readonly CustomerAccountPresenter $presenter,
    ) {}

    /**
     * Retrieve the customer's account overview data.
     */
    public function index(Request $request)
    {
        return response()->json(
            $this->presenter->overview($request->user()),
        );
    }
}
