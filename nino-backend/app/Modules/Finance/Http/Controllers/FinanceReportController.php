<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Finance\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function index()
    {
        // ── Revenue Overview ──────────────────────────────────
        $revenue = [
            'gross' => PaymentTransaction::completed()->payments()->sum('amount'),
            'fees' => PaymentTransaction::completed()->sum('fee_amount'),
            'refunds' => PaymentTransaction::completed()->refunds()->sum('amount'),
            'discounts' => PaymentTransaction::completed()->payments()->sum('order_discount'),
        ];
        $revenue['net'] = $revenue['gross'] - $revenue['fees'] - $revenue['refunds'];

        // ── Payment Method Breakdown ──────────────────────────
        $byMethod = PaymentTransaction::completed()->payments()
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // ── Gateway Success/Failure ───────────────────────────
        $gatewayStats = PaymentTransaction::whereNotNull('gateway')
            ->select('gateway', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('gateway', 'status')
            ->orderBy('gateway')
            ->get()
            ->groupBy('gateway');

        // ── COD Reconciliation ────────────────────────────────
        $codStats = [
            'pending' => PaymentTransaction::cod()->where('cod_status', CodStatus::PENDING)->count(),
            'collected' => PaymentTransaction::cod()->where('cod_status', CodStatus::COLLECTED)->count(),
            'deposited' => PaymentTransaction::cod()->where('cod_status', CodStatus::DEPOSITED)->count(),
            'reconciled' => PaymentTransaction::cod()->where('cod_status', CodStatus::RECONCILED)->count(),
            'discrepancies' => PaymentTransaction::cod()->where('cod_status', CodStatus::DISCREPANCY)->count(),
            'pending_amount' => PaymentTransaction::codPending()->sum('amount'),
            'unreconciled_amount' => PaymentTransaction::codUnreconciled()->sum('amount'),
        ];

        $codTransactions = PaymentTransaction::codUnreconciled()
            ->with('order')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        // ── Refund Summary ────────────────────────────────────
        $refundStats = [
            'pending' => RefundRequest::pending()->count(),
            'total_refunded' => RefundRequest::completed()->sum('amount'),
            'avg_refund' => RefundRequest::completed()->avg('amount') ?: 0,
            'rejection_rate' => RefundRequest::count() > 0
                ? round(RefundRequest::where('status', 'rejected')->count() / RefundRequest::count() * 100, 1)
                : 0,
        ];

        // ── Discount & Fee Visibility ─────────────────────────
        $discountStats = [
            'total_discounts' => PaymentTransaction::completed()->payments()->sum('order_discount'),
            'total_fees' => PaymentTransaction::completed()->sum('fee_amount'),
            'avg_discount' => PaymentTransaction::completed()->payments()->whereNotNull('order_discount')->avg('order_discount') ?: 0,
            'avg_fee' => PaymentTransaction::completed()->where('fee_amount', '>', 0)->avg('fee_amount') ?: 0,
        ];

        return view('admin.finance.reports.index', compact(
            'revenue', 'byMethod', 'gatewayStats', 'codStats', 'codTransactions', 'refundStats', 'discountStats'
        ));
    }

    /**
     * COD reconciliation actions.
     */
    public function codCollect(Request $request, PaymentTransaction $transaction)
    {
        $request->validate([
            'collected_by' => 'required|string|max:100',
            'collected_amount' => 'nullable|numeric|min:0',
        ]);

        $transaction->markCodCollected($request->collected_by, $request->collected_amount);

        if ($transaction->hasCodDiscrepancy()) {
            $transaction->update(['cod_status' => CodStatus::DISCREPANCY]);
            return back()->with('warning', "COD collected with discrepancy of {$transaction->codDiscrepancyAmount()} {$transaction->currency}.");
        }

        return back()->with('success', 'COD marked as collected.');
    }

    public function codDeposit(PaymentTransaction $transaction)
    {
        $transaction->markCodDeposited();
        return back()->with('success', 'COD marked as deposited.');
    }

    public function codReconcile(PaymentTransaction $transaction)
    {
        $transaction->markCodReconciled();
        return back()->with('success', 'COD reconciled.');
    }
}
