@extends('admin.layouts.app')

@section('title', 'Shipping Methods')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Shipping Methods</h1>
        <p class="text-sm text-slate-500 mt-1">Configure available delivery options for customers.</p>
    </div>
    <a href="{{ route('admin.shipping.methods.create') }}" class="btn-primary">
        + Add Method
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Carrier</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Base Cost</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Free Above</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Est. Days</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($methods as $method)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3 font-medium text-slate-900">{{ $method->name }}</td>
                <td class="px-4 py-3 text-slate-500">
                    <div>{{ $method->carrierLabel() }}</div>
                    <div class="mt-1 text-[11px] font-mono uppercase tracking-[0.18em] text-slate-400">{{ $method->shippingCarrier?->provider ?? 'manual' }}</div>
                </td>
                <td class="px-4 py-3 text-slate-900">{{ number_format($method->base_cost, 2) }} MAD</td>
                <td class="px-4 py-3 text-slate-500">{{ $method->free_shipping_threshold ? number_format($method->free_shipping_threshold, 2) . ' MAD' : '—' }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $method->estimated_days ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $method->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ $method->is_enabled ? 'Active' : 'Disabled' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.shipping.methods.edit', $method) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                    <form action="{{ route('admin.shipping.methods.destroy', $method) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Delete this shipping method?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No shipping methods configured yet.
                        </div>
                    </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
