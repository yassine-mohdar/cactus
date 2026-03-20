<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Catalog\Models\Product;
use App\Modules\Cms\Models\BlogPost;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;

class BulkActionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    // ── Products ───────────────────────────────────────────
    public function productsBulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:products,id',
        ]);

        $count = count($request->ids);

        switch ($request->action) {
            case 'activate':
                Product::whereIn('id', $request->ids)->update(['is_active' => true]);
                return back()->with('success', "{$count} product(s) activated.");

            case 'deactivate':
                Product::whereIn('id', $request->ids)->update(['is_active' => false]);
                return back()->with('success', "{$count} product(s) deactivated.");

            case 'delete':
                Product::whereIn('id', $request->ids)->delete();
                return back()->with('success', "{$count} product(s) deleted.");
        }

        return back();
    }

    // ── Orders ─────────────────────────────────────────────
    public function ordersBulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:mark_processing,mark_shipped,export_csv',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:orders,id',
        ]);

        $count = count($request->ids);
        $orders = Order::whereIn('id', $request->ids)->get();

        switch ($request->action) {
            case 'mark_processing':
                $this->overrideOrderStatuses($orders, 'preparing', 'bulk mark processing');
                return back()->with('success', "{$count} order(s) marked processing.");

            case 'mark_shipped':
                $this->overrideOrderStatuses($orders, 'shipped', 'bulk mark shipped');
                return back()->with('success', "{$count} order(s) marked shipped.");

            case 'export_csv':
                return $this->exportOrdersCsv($request->ids);
        }

        return back();
    }

    // ── Content ────────────────────────────────────────────
    public function contentBulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,unpublish,archive,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:blog_posts,id',
        ]);

        $count = count($request->ids);

        switch ($request->action) {
            case 'publish':
                BlogPost::whereIn('id', $request->ids)->update(['status' => 'published', 'published_at' => now()]);
                return back()->with('success', "{$count} post(s) published.");

            case 'unpublish':
                BlogPost::whereIn('id', $request->ids)->update(['status' => 'draft', 'published_at' => null]);
                return back()->with('success', "{$count} post(s) unpublished.");

            case 'archive':
                BlogPost::whereIn('id', $request->ids)->update(['status' => 'archived']);
                return back()->with('success', "{$count} post(s) archived.");

            case 'delete':
                BlogPost::whereIn('id', $request->ids)->delete();
                return back()->with('success', "{$count} post(s) deleted.");
        }

        return back();
    }

    // ── Private Helpers ────────────────────────────────────
    private function exportOrdersCsv(array $ids)
    {
        $orders = Order::with([
                'customer:id,name,first_name,last_name,email,phone',
                'billingAddress:id,order_id,first_name,last_name,phone',
            ])
            ->whereIn('id', $ids)
            ->get();
        $filename = 'orders_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order #', 'Customer', 'Email', 'Status', 'Total', 'Date']);
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->reference_number,
                    $this->orderCustomerName($order),
                    $this->orderCustomerEmail($order),
                    $order->status?->label() ?? '',
                    number_format((float) ($order->grand_total ?? 0), 2),
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     */
    private function overrideOrderStatuses($orders, string $newStatus, string $note): void
    {
        foreach ($orders as $order) {
            $oldStatus = $order->status?->value ?? (string) $order->status;

            if ($oldStatus === $newStatus) {
                continue;
            }

            $order->update(['status' => $newStatus]);

            $this->audit->log(
                action: 'orders.status_overridden',
                target: $order,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                notes: $note,
                context: [
                    'module' => 'reports',
                    'source' => 'bulk_action_controller',
                    'bulk_action' => true,
                ],
            );
        }
    }
}
