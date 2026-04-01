@extends('admin.layouts.app')

@section('title', 'Shipping Carriers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Shipping Carriers</h1>
        <p class="text-sm text-slate-500 mt-1">Registry of carrier partners and Sendit API providers used by shipping methods.</p>
    </div>
    <a href="{{ route('admin.shipping.carriers.create') }}" class="btn-primary">
        + Add Carrier
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
                <th class="px-4 py-3 font-semibold text-slate-900">Provider</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Methods</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Districts</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($carriers as $carrier)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ $carrier->name }}</div>
                    <div class="mt-1 text-[11px] font-mono uppercase tracking-[0.18em] text-slate-400">{{ $carrier->code }}</div>
                </td>
                <td class="px-4 py-3 text-slate-500">{{ strtoupper($carrier->provider) }}</td>
                <td class="px-4 py-3 text-slate-900">{{ number_format($carrier->shipping_methods_count) }}</td>
                <td class="px-4 py-3 text-slate-900">{{ number_format($carrier->districts_count) }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $carrier->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ $carrier->is_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.shipping.carriers.edit', $carrier) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                    <form action="{{ route('admin.shipping.carriers.destroy', $carrier) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Delete this carrier?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-8 text-center text-slate-500">
                    <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                        No carriers configured yet.
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
