@extends('admin.layouts.app')

@section('title', 'Shipping Carriers')

@section('header')
    <x-nino.page-header
        title="Shipping Carriers"
        subtitle="Manage the authoritative registry of fulfillment partners, API carriers, and tracking templates.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.shipping.carriers.create') }}" variant="primary" icon="add">Add Carrier</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Carrier updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Carrier action failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)]">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Carrier Directory</h2>
                    <p class="datatable-subtitle">Linked shipping methods inherit these carrier records instead of relying on free-text labels.</p>
                </div>
                <span class="datatable-meta">{{ number_format($carriers->count()) }} carriers</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Name</th>
                    <th>Provider</th>
                    <th>Methods</th>
                    <th>Districts</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($carriers as $carrier)
                        <tr>
                            <td>
                                <p class="font-semibold text-[#1E2B27]">{{ $carrier->name }}</p>
                                <p class="mt-1 text-xs font-mono text-[#7A8681]">{{ $carrier->code }}</p>
                            </td>
                            <td class="table-muted">{{ strtoupper($carrier->provider) }}</td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($carrier->shipping_methods_count) }}</td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($carrier->districts_count) }}</td>
                            <td>
                                <x-nino.status-badge :tone="$carrier->is_enabled ? 'success' : 'neutral'" size="sm">
                                    {{ $carrier->is_enabled ? 'Enabled' : 'Disabled' }}
                                </x-nino.status-badge>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.shipping.carriers.edit', $carrier) }}" class="table-action-link">Edit</a>
                                    <form action="{{ route('admin.shipping.carriers.destroy', $carrier) }}" method="POST" class="inline" onsubmit="return confirm('Delete this carrier?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No carriers configured"
                                    description="Create your first carrier registry entry before linking methods and shipments."
                                    icon="local_shipping" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>
        </div>

        <x-nino.detail-section title="Provider Notes" subtitle="Operational defaults for the current release scope.">
            <div class="space-y-4 text-sm text-[#61706B]">
                <div>
                    <p class="font-semibold text-[#1E2B27]">Manual carriers</p>
                    <p class="mt-1">Use this for Amana, Aramex, and FedEx when you only need registry, tracking, and method assignment.</p>
                </div>
                <div>
                    <p class="font-semibold text-[#1E2B27]">Sendit</p>
                    <p class="mt-1">Sendit supports delivery creation, label printing, district sync, and shipment status sync from the ops panel.</p>
                </div>
            </div>
        </x-nino.detail-section>
    </div>
@endsection
