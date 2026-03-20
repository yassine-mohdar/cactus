<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Promotions\Enums\CouponType;
use App\Modules\Promotions\Models\Coupon;
use App\Modules\Promotions\Models\CouponUsage;
use App\Modules\Promotions\Models\AbandonedCart;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            $query->when($status === 'active', fn($q) => $q->active())
                ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
                ->when($status === 'expired', fn($q) => $q->where('ends_at', '<', now()))
                ->when($status === 'exhausted', fn($q) => $q->whereNotNull('usage_limit')->whereColumn('usage_count', '>=', 'usage_limit'));
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $coupons = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::active()->count(),
            'total_usage' => CouponUsage::count(),
            'total_savings' => CouponUsage::sum('discount_amount'),
        ];

        return view('admin.promotions.coupons.index', compact('coupons', 'stats'));
    }

    public function create()
    {
        $coupon = new Coupon();
        $types = CouponType::cases();
        return view('admin.promotions.coupons.form', compact('coupon', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0.01',
            'max_discount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'minimum_cart_total' => 'nullable|numeric|min:0',
            'maximum_cart_total' => 'nullable|numeric|min:0',
            'minimum_items' => 'nullable|integer|min:1',
            'is_stackable' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->has('is_active');
        $validated['is_stackable'] = $request->has('is_stackable');
        $validated['created_by'] = auth()->id();

        // Process targeting arrays
        $validated['product_ids'] = $this->parseCommaSeparated($request->input('product_ids_input'));
        $validated['category_ids'] = $this->parseCommaSeparated($request->input('category_ids_input'));
        $validated['excluded_product_ids'] = $this->parseCommaSeparated($request->input('excluded_product_ids_input'));
        $validated['excluded_category_ids'] = $this->parseCommaSeparated($request->input('excluded_category_ids_input'));

        Coupon::create($validated);

        return redirect()->route('admin.promotions.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        $types = CouponType::cases();
        $coupon->loadCount('usages');
        return view('admin.promotions.coupons.form', compact('coupon', 'types'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0.01',
            'max_discount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'minimum_cart_total' => 'nullable|numeric|min:0',
            'maximum_cart_total' => 'nullable|numeric|min:0',
            'minimum_items' => 'nullable|integer|min:1',
            'is_stackable' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_stackable'] = $request->has('is_stackable');
        $validated['product_ids'] = $this->parseCommaSeparated($request->input('product_ids_input'));
        $validated['category_ids'] = $this->parseCommaSeparated($request->input('category_ids_input'));
        $validated['excluded_product_ids'] = $this->parseCommaSeparated($request->input('excluded_product_ids_input'));
        $validated['excluded_category_ids'] = $this->parseCommaSeparated($request->input('excluded_category_ids_input'));

        $coupon->update($validated);

        return redirect()->route('admin.promotions.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        if ($coupon->usage_count > 0) {
            $coupon->update(['is_active' => false]);
            return redirect()->route('admin.promotions.coupons.index')->with('success', 'Coupon deactivated (has usage history).');
        }
        $coupon->delete();
        return redirect()->route('admin.promotions.coupons.index')->with('success', 'Coupon deleted.');
    }

    /**
     * Coupon usage reports.
     */
    public function reports(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $tab = $request->string('tab')->toString() ?: 'coupons';

        $applyCouponFilters = function ($query) use ($status, $type) {
            $query
                ->when($type !== '', fn ($builder) => $builder->where('type', $type))
                ->when($status === 'active', fn ($builder) => $builder->active())
                ->when($status === 'inactive', fn ($builder) => $builder->where('is_active', false))
                ->when($status === 'expired', fn ($builder) => $builder->where('ends_at', '<', now()))
                ->when($status === 'exhausted', fn ($builder) => $builder->whereNotNull('usage_limit')->whereColumn('usage_count', '>=', 'usage_limit'))
                ->when($status === 'scheduled', fn ($builder) => $builder->whereNotNull('starts_at')->where('starts_at', '>', now()));
        };

        $usageQuery = CouponUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($status !== '' || $type !== '', function ($query) use ($applyCouponFilters) {
                $query->whereHas('coupon', fn ($couponQuery) => $applyCouponFilters($couponQuery));
            });

        $topCoupons = Coupon::query()
            ->when($status !== '' || $type !== '', fn ($query) => $applyCouponFilters($query))
            ->withCount(['usages' => fn ($query) => $query->whereBetween('created_at', [$from, $to])])
            ->withSum(['usages' => fn ($query) => $query->whereBetween('created_at', [$from, $to])], 'discount_amount')
            ->having('usages_count', '>', 0)
            ->orderByDesc('usages_count')
            ->limit(15)
            ->get();

        $recentUsages = (clone $usageQuery)
            ->with(['coupon', 'customer', 'order'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $totalDiscounts = (clone $usageQuery)->sum('discount_amount');
        $totalOrdersBefore = (clone $usageQuery)->sum('order_total_before');
        $avgDiscount = (clone $usageQuery)->avg('discount_amount') ?? 0;

        $abandonedBaseQuery = AbandonedCart::query()
            ->whereBetween('abandoned_at', [$from, $to]);

        $abandonedStats = [
            'total' => (clone $abandonedBaseQuery)->count(),
            'active' => (clone $abandonedBaseQuery)->abandoned()->count(),
            'recovered' => (clone $abandonedBaseQuery)->recovered()->count(),
            'recovery_rate' => (clone $abandonedBaseQuery)->count() > 0
                ? round((clone $abandonedBaseQuery)->recovered()->count() / (clone $abandonedBaseQuery)->count() * 100, 1)
                : 0,
            'lost_revenue' => (clone $abandonedBaseQuery)->abandoned()->sum('cart_total'),
            'recovered_revenue' => (clone $abandonedBaseQuery)->recovered()
                ->with('recoveredOrder')
                ->get()
                ->sum(fn($c) => $c->recoveredOrder?->grand_total ?? 0),
        ];

        $filters = [
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'type' => $type,
            'tab' => $tab,
        ];

        return view('admin.promotions.reports', compact(
            'topCoupons', 'recentUsages', 'totalDiscounts',
            'totalOrdersBefore', 'avgDiscount', 'abandonedStats', 'filters', 'tab'
        ));
    }

    private function parseCommaSeparated(?string $input): ?array
    {
        if (!$input) return null;
        $ids = array_filter(array_map('intval', explode(',', $input)));
        return empty($ids) ? null : array_values($ids);
    }
}
