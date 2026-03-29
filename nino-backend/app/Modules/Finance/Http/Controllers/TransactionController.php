<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentTransaction::with(['order', 'customer']);

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('reference', 'like', "%{$s}%")
                ->orWhere('gateway_transaction_id', 'like', "%{$s}%")
                ->orWhereHas('order', fn($oq) => $oq->where('reference_number', 'like', "%{$s}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Stats
        $stats = [
            'total_volume' => PaymentTransaction::completed()->payments()->sum('amount'),
            'total_fees' => PaymentTransaction::completed()->sum('fee_amount'),
            'total_refunds' => PaymentTransaction::completed()->refunds()->sum('amount'),
            'pending_count' => PaymentTransaction::where('status', TransactionStatus::PENDING)->count(),
        ];

        return view('admin.finance.transactions.index', compact('transactions', 'stats'));
    }

    public function show(PaymentTransaction $transaction)
    {
        $transaction->load(['order', 'customer', 'processor', 'refundRequests']);
        return view('admin.finance.transactions.show', compact('transaction'));
    }

    public function verifyOffline(Request $request, PaymentTransaction $transaction)
    {
        $payload = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($this->isVerifiableOfflineTransaction($transaction), 404);

        $notes = trim((string) ($payload['notes'] ?? ''));

        $transaction->update([
            'status' => TransactionStatus::COMPLETED,
            'processed_by' => $request->user()?->id,
            'failure_reason' => null,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'offline_review' => [
                    'decision' => 'verified',
                    'notes' => $notes !== '' ? $notes : null,
                        'reviewed_at' => now()->toIso8601String(),
                    'reviewed_by' => $request->user()?->id,
                ],
            ]),
        ]);

        if ($transaction->order && $transaction->order->status === OrderStatus::AWAITING_PAYMENT) {
            $transaction->order->update(['status' => OrderStatus::PAID]);
        }

        return redirect()
            ->route('admin.finance.transactions.show', $transaction)
            ->with('success', 'Offline payment verified and marked as completed.');
    }

    public function failOffline(Request $request, PaymentTransaction $transaction)
    {
        $payload = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        abort_unless($this->isVerifiableOfflineTransaction($transaction), 404);

        $notes = trim((string) $payload['notes']);

        $transaction->update([
            'status' => TransactionStatus::FAILED,
            'processed_by' => $request->user()?->id,
            'failure_reason' => $notes,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'offline_review' => [
                    'decision' => 'failed',
                    'notes' => $notes,
                    'reviewed_at' => now()->toIso8601String(),
                    'reviewed_by' => $request->user()?->id,
                ],
            ]),
        ]);

        if ($transaction->order && $transaction->order->status === OrderStatus::AWAITING_PAYMENT) {
            $transaction->order->update(['status' => OrderStatus::FAILED]);
        }

        return redirect()
            ->route('admin.finance.transactions.show', $transaction)
            ->with('success', 'Offline payment marked as failed.');
    }

    private function isVerifiableOfflineTransaction(PaymentTransaction $transaction): bool
    {
        return $transaction->gateway === 'offline_transfer'
            && $transaction->payment_method === PaymentMethod::BANK_TRANSFER
            && $transaction->type === TransactionType::PAYMENT
            && $transaction->status === TransactionStatus::PENDING;
    }
}
