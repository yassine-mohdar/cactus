<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Community\Services\CommunityOnboardingService;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    /**
     * Display a listing of the customers
     */
    public function index(Request $request)
    {
        $this->authorize('viewAnyCustomers', User::class);

        $query = User::customers()
            ->withCount('orders')
            ->withSum('orders as lifetime_value', 'grand_total');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        $customerBaseQuery = User::customers();

        $summary = (object) [
            'total_customers' => (clone $customerBaseQuery)->count(),
            'active_customers' => (clone $customerBaseQuery)->where('status', 'active')->count(),
            'new_last_30_days' => (clone $customerBaseQuery)->where('created_at', '>=', now()->subDays(30))->count(),
            'total_orders' => Order::query()->whereNotNull('customer_id')->count(),
        ];

        return view('admin.customers.index', compact('customers', 'summary'));
    }

    /**
     * Display a specific customer's details including their timeline/notes.
     */
    public function show(User $customer)
    {
        if (! $customer->isCustomer()) {
            abort(404, 'Customer not found.');
        }

        $this->authorize('viewCustomer', $customer);

        // Load the customer's addresses and recent order history.
        $customer->load([
            'addresses',
            'orders' => fn ($query) => $query->latest()->limit(6),
        ]);
        
        // Use a direct query for notes since we didn't add the Inverse HasMany on the User model yet
        $notes = \App\Modules\Customers\Models\CustomerNote::with('admin')
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        $recentOrders = $customer->orders;

        $customerOrderStats = $customer->orders()
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_spent')
            ->first();

        $totalOrders = (int) ($customerOrderStats?->total_orders ?? 0);
        $totalSpent = (float) ($customerOrderStats?->total_spent ?? 0);

        $metrics = [
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? $totalSpent / $totalOrders : 0,
        ];

        // Ensure a view exists or return a JSON representation for now depending on admin architecture layer
        if (view()->exists('admin.customers.show')) {
            return view('admin.customers.show', compact('customer', 'notes', 'recentOrders', 'metrics'));
        }

        // Fallback for Phase 6 backend scaffolding
        return response()->json([
            'customer' => $customer,
            'notes' => $notes,
            'orders' => $recentOrders,
            'metrics' => $metrics,
        ]);
    }
    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $this->authorize('createCustomer', User::class);

        $customer = new User(['type' => 'customer', 'status' => 'active']);
        return view('admin.customers.create', compact('customer'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(
        Request $request,
        CommunityOnboardingService $communityOnboardingService,
        PasswordPolicyService $passwordPolicy,
    )
    {
        $this->authorize('createCustomer', User::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => $passwordPolicy->requiredRules(),
            'status' => 'required|in:active,suspended',
        ]);

        $customer = User::create([
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'], // Hash is handled by model cast
            'type' => 'customer',
            'status' => $validated['status'],
        ]);

        $communityOnboardingService->inviteNewCustomerToDefaultGroup($customer, 'admin_customer_create');

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(User $customer)
    {
        if (! $customer->isCustomer()) {
            abort(404, 'Customer not found.');
        }

        $this->authorize('updateCustomer', $customer);

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, User $customer)
    {
        if (! $customer->isCustomer()) {
            abort(404, 'Customer not found.');
        }

        $this->authorize('updateCustomer', $customer);

        $passwordPolicy = app(PasswordPolicyService::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'password' => $passwordPolicy->optionalRules(),
            'status' => 'required|in:active,suspended',
        ]);

        $data = [
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $customer->update($data);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }
}
