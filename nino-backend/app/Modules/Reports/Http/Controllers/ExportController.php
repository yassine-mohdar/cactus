<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Promotions\Models\Coupon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function products(): StreamedResponse
    {
        $filename = 'products_' . now()->format('Y-m-d_His') . '.csv';

        return $this->streamCsv($filename, ['ID', 'Name', 'SKU', 'Category', 'Price', 'Stock', 'Status', 'Created'], function ($file) {
            Product::with(['categories', 'variants'])->chunk(200, function ($products) use ($file) {
                foreach ($products as $product) {
                    $categoryNames = $product->categories->pluck('name')->implode(', ');
                    $status = $product->status === 'published' ? 'Active' : ucfirst((string) $product->status);

                    if ($product->variants->isEmpty()) {
                        fputcsv($file, [
                            $product->id,
                            $product->name,
                            $product->sku ?? '',
                            $categoryNames,
                            number_format((float) ($product->price ?? 0), 2),
                            (int) ($product->quantity ?? 0),
                            $status,
                            $product->created_at->format('Y-m-d'),
                        ]);

                        continue;
                    }

                    foreach ($product->variants as $variant) {
                        fputcsv($file, [
                            $product->id,
                            $product->name,
                            $variant->sku ?? '',
                            $categoryNames,
                            number_format((float) ($variant->price ?? $product->price ?? 0), 2),
                            (int) ($variant->quantity ?? 0),
                            $status,
                            $product->created_at->format('Y-m-d'),
                        ]);
                    }
                }
            });
        });
    }

    public function orders(Request $request): StreamedResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $filename = "orders_{$from}_to_{$to}.csv";

        return $this->streamCsv($filename, ['Order #', 'Customer', 'Email', 'Phone', 'Status', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Date'], function ($file) use ($from, $to) {
            Order::with([
                    'customer:id,name,first_name,last_name,email,phone',
                    'billingAddress:id,order_id,first_name,last_name,phone',
                ])
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orderByDesc('created_at')
                ->chunk(200, function ($orders) use ($file) {
                    foreach ($orders as $order) {
                        fputcsv($file, [
                            $order->reference_number,
                            $this->orderCustomerName($order),
                            $this->orderCustomerEmail($order),
                            $this->orderCustomerPhone($order),
                            $order->status?->label() ?? '',
                            number_format((float) ($order->subtotal ?? 0), 2),
                            number_format((float) ($order->discount_total ?? 0), 2),
                            number_format((float) ($order->shipping_total ?? 0), 2),
                            number_format((float) ($order->grand_total ?? 0), 2),
                            $order->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
        });
    }

    public function transactions(Request $request): StreamedResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $filename = "transactions_{$from}_to_{$to}.csv";

        return $this->streamCsv($filename, ['Reference', 'Type', 'Status', 'Method', 'Gateway', 'Amount', 'Fee', 'Net', 'Currency', 'Date'], function ($file) use ($from, $to) {
            PaymentTransaction::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orderByDesc('created_at')
                ->chunk(200, function ($txns) use ($file) {
                    foreach ($txns as $txn) {
                        fputcsv($file, [
                            $txn->reference,
                            $txn->type->value ?? $txn->type,
                            $txn->status->value ?? $txn->status,
                            $txn->payment_method->value ?? $txn->payment_method,
                            $txn->gateway ?? '',
                            number_format($txn->amount, 2),
                            number_format($txn->fee_amount, 2),
                            number_format($txn->net_amount, 2),
                            $txn->currency,
                            $txn->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
        });
    }

    public function inventory(): StreamedResponse
    {
        $filename = 'inventory_' . now()->format('Y-m-d_His') . '.csv';

        return $this->streamCsv($filename, ['Product', 'SKU', 'Branch', 'Available', 'Reserved', 'Physical', 'Price', 'Status'], function ($file) {
            StockItem::with(['product', 'variant', 'branch'])->chunk(200, function ($items) use ($file) {
                foreach ($items as $item) {
                    $availableQuantity = $item->available_quantity;

                    fputcsv($file, [
                        $item->product?->name ?? '',
                        $item->sku ?? '',
                        $item->branch?->name ?? 'Global',
                        $availableQuantity,
                        (int) ($item->reserved_quantity ?? 0),
                        (int) ($item->quantity ?? 0),
                        number_format($item->unit_price, 2),
                        $availableQuantity > 0 ? 'In Stock' : 'Out of Stock',
                    ]);
                }
            });
        });
    }

    public function importCatalog(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);

        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($header, $row);

                $product = Product::updateOrCreate(
                    ['name' => $data['Name'] ?? $data['name'] ?? null],
                    [
                        'slug' => \Illuminate\Support\Str::slug($data['Name'] ?? $data['name'] ?? ''),
                        'status' => ($data['Status'] ?? $data['status'] ?? 'Active') === 'Active' ? 'published' : 'draft',
                    ]
                );

                if (!empty($data['SKU'] ?? $data['sku'] ?? null)) {
                    ProductVariant::updateOrCreate(
                        ['product_id' => $product->id, 'sku' => $data['SKU'] ?? $data['sku']],
                        [
                            'price' => floatval(str_replace(',', '', $data['Price'] ?? $data['price'] ?? 0)),
                            'quantity' => intval($data['Stock'] ?? $data['stock'] ?? 0),
                        ]
                    );
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($imported + count($errors) + 2) . ": " . $e->getMessage();
            }
        }

        fclose($handle);

        $msg = "{$imported} product(s) imported.";
        if (count($errors)) {
            $msg .= " " . count($errors) . " error(s).";
        }

        return back()->with('success', $msg)->with('import_errors', $errors);
    }

    // ── Private Helper ─────────────────────────────────────
    private function streamCsv(string $filename, array $headers, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            $writer($file);
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function orderCustomerName(Order $order): string
    {
        $billingAddress = $order->billingAddress;
        $customer = $order->customer;

        if ($billingAddress) {
            return trim($billingAddress->first_name . ' ' . $billingAddress->last_name);
        }

        return trim($customer?->full_name ?: ($customer?->name ?? ''));
    }

    private function orderCustomerEmail(Order $order): string
    {
        return $order->customer?->email ?? '';
    }

    private function orderCustomerPhone(Order $order): string
    {
        return $order->billingAddress?->phone ?? $order->customer?->phone ?? '';
    }
}
