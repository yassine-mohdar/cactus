<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
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
}
