<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Orders\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CustomerInvoiceController extends Controller
{
    public function show(Request $request, Order $order, InvoiceService $invoiceService)
    {
        [$invoice, $shippingAddress, $billingAddress] = $this->resolveInvoiceContext($request, $order, $invoiceService);

        $pdf = Pdf::loadView('pdf.invoice', compact(
            'invoice',
            'order',
            'shippingAddress',
            'billingAddress',
        ))->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function preview(Request $request, Order $order, InvoiceService $invoiceService)
    {
        [$invoice, $shippingAddress, $billingAddress] = $this->resolveInvoiceContext($request, $order, $invoiceService);

        return view('customer.account.orders.invoice', compact(
            'invoice',
            'order',
            'shippingAddress',
            'billingAddress',
        ));
    }

    private function resolveInvoiceContext(Request $request, Order $order, InvoiceService $invoiceService): array
    {
        abort_unless((int) $order->customer_id === (int) $request->user()?->id, 403);

        $order->loadMissing([
            'customer',
            'lineItems',
            'addresses',
            'shipments',
            'transactions',
        ]);

        $invoice = $invoiceService->ensureInvoiceForOrder($order);

        abort_unless($invoice, 404);

        $shippingAddress = $order->addresses->firstWhere('type', 'shipping');
        $billingAddress = $order->addresses->firstWhere('type', 'billing');

        return [$invoice, $shippingAddress, $billingAddress];
    }
}
