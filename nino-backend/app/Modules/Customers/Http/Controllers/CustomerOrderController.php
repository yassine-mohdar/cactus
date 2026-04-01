<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Services\CustomerAccountPresenter;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function __construct(
        private readonly CustomerAccountPresenter $presenter,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request)
    {
        $customer = $request->user();
        $payload = $this->presenter->history($customer);

        return view('customer.account.orders.index', [
            'customer' => $customer,
            'orders' => $payload['orders'],
            'summary' => $payload['summary'],
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $customer = $request->user();
        $payload = $this->presenter->detail($customer, $order);
        $invoice = $this->invoiceService->ensureInvoiceForOrder($payload['order']);

        return view('customer.account.orders.show', [
            'customer' => $customer,
            'order' => $payload['order'],
            'summary' => $payload['summary'],
            'shippingAddress' => $payload['shipping_address'],
            'billingAddress' => $payload['billing_address'],
            'shipment' => $payload['shipment'],
            'tracking' => $payload['tracking'],
            'invoice' => $invoice,
        ]);
    }
}
