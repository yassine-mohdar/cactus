@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('header', 'Dashboard')
@section('subheader', 'Welcome to NinoWorld Admin — your operational command center.')

@section('content')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Stat Card: Total Orders --}}
    <div class="rounded-xl border border-border bg-surface px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Total Orders</p>
        <p class="mt-1 text-2xl font-bold text-ink">0</p>
        <p class="mt-1 text-xs text-ink-muted">Today</p>
    </div>

    {{-- Stat Card: Revenue --}}
    <div class="rounded-xl border border-border bg-surface px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Revenue</p>
        <p class="mt-1 text-2xl font-bold text-sage">0.00 MAD</p>
        <p class="mt-1 text-xs text-ink-muted">Today</p>
    </div>

    {{-- Stat Card: Customers --}}
    <div class="rounded-xl border border-border bg-surface px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Customers</p>
        <p class="mt-1 text-2xl font-bold text-ink">0</p>
        <p class="mt-1 text-xs text-ink-muted">Registered</p>
    </div>

    {{-- Stat Card: Low Stock --}}
    <div class="rounded-xl border border-border bg-surface px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Low Stock</p>
        <p class="mt-1 text-2xl font-bold text-warning">0</p>
        <p class="mt-1 text-xs text-ink-muted">Products below threshold</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="mt-8">
    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-ink-muted">Quick Actions</h2>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="#" class="flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-medium text-ink transition-colors hover:border-sage hover:bg-sage/5">
            <span class="text-sage">+</span> New Order
        </a>
        <a href="#" class="flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-medium text-ink transition-colors hover:border-sage hover:bg-sage/5">
            <span class="text-sage">+</span> New Product
        </a>
        <a href="#" class="flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-medium text-ink transition-colors hover:border-sage hover:bg-sage/5">
            <span class="text-sage">+</span> New Customer
        </a>
        <a href="#" class="flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-medium text-ink transition-colors hover:border-sage hover:bg-sage/5">
            <span class="text-sage">+</span> New Coupon
        </a>
    </div>
</div>

{{-- Recent Activity Placeholder --}}
<div class="mt-8">
    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-ink-muted">Recent Activity</h2>
    <div class="rounded-xl border border-border bg-surface">
        <div class="px-5 py-12 text-center">
            <p class="text-sm text-ink-muted">No recent activity yet. Start by creating your first product.</p>
        </div>
    </div>
</div>
@endsection
