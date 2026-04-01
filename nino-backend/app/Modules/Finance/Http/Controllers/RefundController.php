<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Finance\Models\RefundRequest;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
        $this->middleware('permission.any:finance.viewAny,payments.refund')->only('index');
        $this->middleware('permission.any:payments.refund')->only(['approve', 'reject', 'complete']);
    }

    public function index(Request $request)
    {
        $query = RefundRequest::with(['order', 'customer', 'transaction']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('reference', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refunds = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = [
            'pending' => RefundRequest::pending()->count(),
            'approved' => RefundRequest::approved()->count(),
            'total_refunded' => RefundRequest::completed()->sum('amount'),
            'total_requests' => RefundRequest::count(),
        ];

        return view('admin.finance.refunds.index', compact('refunds', 'stats'));
    }

    public function approve(RefundRequest $refund)
    {
        $before = $this->refundSnapshot($refund);
        $refund->approve(auth()->id());

        $this->audit->log(
            action: 'finance.refund.updated',
            target: $refund->fresh(),
            oldValues: $before,
            newValues: $this->refundSnapshot($refund->fresh()),
            notes: "Refund {$refund->reference} approved",
            context: [
                'module' => 'finance',
                'source' => 'refund_controller',
                'transition' => 'approve',
            ],
        );

        return back()->with('success', "Refund {$refund->reference} approved.");
    }

    public function reject(Request $request, RefundRequest $refund)
    {
        $before = $this->refundSnapshot($refund);
        $reason = $request->input('reason', '');
        $refund->reject(auth()->id(), $reason);

        $this->audit->log(
            action: 'finance.refund.updated',
            target: $refund->fresh(),
            oldValues: $before,
            newValues: $this->refundSnapshot($refund->fresh()),
            notes: "Refund {$refund->reference} rejected",
            context: [
                'module' => 'finance',
                'source' => 'refund_controller',
                'transition' => 'reject',
            ],
        );

        return back()->with('success', "Refund {$refund->reference} rejected.");
    }

    public function complete(RefundRequest $refund)
    {
        $before = $this->refundSnapshot($refund);
        $refund->complete(auth()->id());

        $this->audit->log(
            action: 'finance.refund.updated',
            target: $refund->fresh(),
            oldValues: $before,
            newValues: $this->refundSnapshot($refund->fresh()),
            notes: "Refund {$refund->reference} completed",
            context: [
                'module' => 'finance',
                'source' => 'refund_controller',
                'transition' => 'complete',
            ],
        );

        return back()->with('success', "Refund {$refund->reference} completed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function refundSnapshot(RefundRequest $refund): array
    {
        return [
            'status' => $refund->status?->value ?? (string) $refund->status,
            'amount' => $refund->amount !== null ? (float) $refund->amount : null,
            'approved_by' => $refund->approved_by,
            'processed_by' => $refund->processed_by,
            'admin_notes' => $refund->admin_notes,
            'approved_at' => $refund->approved_at?->toISOString(),
            'completed_at' => $refund->completed_at?->toISOString(),
            'rejected_at' => $refund->rejected_at?->toISOString(),
        ];
    }
}
