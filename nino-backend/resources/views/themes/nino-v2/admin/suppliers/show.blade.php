@extends('admin.layouts.app')

@section('title', 'Supplier: ' . $supplier->name)

@php
    $statusTone = $supplier->status === 'active' ? 'success' : 'neutral';
@endphp

@section('header')
    <x-nino.page-header
        title="{{ $supplier->name }}"
        subtitle="Keep supplier contact coverage accurate while the purchasing workflow remains intentionally lightweight.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$statusTone">{{ ucfirst($supplier->status) }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.inventory.suppliers.edit', $supplier) }}" variant="secondary" icon="edit">Edit Supplier</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.suppliers.index') }}" variant="secondary" icon="arrow_back">Back to Suppliers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-nino.inline-alert tone="neutral" title="Purchasing telemetry is not persisted yet">
            Supplier profiles are fully standardized in `nino-v2`, but spend and purchase-order history are intentionally omitted until a persistent purchase-order model is introduced.
        </x-nino.inline-alert>

        <div class="detail-grid">
            <div class="detail-main">
                <x-nino.detail-section title="Linked Team Contacts" subtitle="Users connected to this supplier can own procurement communication and vendor escalation.">
                    @if($supplier->users->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($supplier->users as $user)
                                <div class="surface-panel">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-[#1E2B27]">{{ $user->name }}</p>
                                            <p class="mt-1 text-xs text-[#61706B]">{{ $user->email }}</p>
                                        </div>
                                        <x-nino.status-badge :tone="$user->status === 'active' ? 'success' : 'neutral'" size="sm">{{ ucfirst($user->status ?? 'active') }}</x-nino.status-badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-nino.empty-state
                            title="No linked contacts"
                            description="Associate vendor owners or procurement users with this supplier if you want clearer accountability in operations."
                            icon="group" />
                    @endif
                </x-nino.detail-section>

                <x-nino.detail-section title="Operational Readiness" subtitle="Keep supplier records lean and trustworthy while the purchasing workflow remains lightweight.">
                    <div class="space-y-3 text-sm leading-6 text-[#61706B]">
                        <p>Use supplier records for accurate vendor contact information, city/country coverage, and ownership context.</p>
                        <p>A future purchase-order module can build on this profile without forcing another redesign of the admin surface.</p>
                    </div>
                </x-nino.detail-section>
            </div>

            <div class="detail-sidebar">
                <x-nino.detail-section title="Supplier Details" subtitle="Primary vendor contact information used by the internal team.">
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Email</p>
                            <p class="detail-value">{{ $supplier->email ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Phone</p>
                            <p class="detail-value">{{ $supplier->phone ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Linked Users</p>
                            <p class="detail-value-mono">{{ number_format($supplier->users_count ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Updated</p>
                            <p class="detail-value">{{ $supplier->updated_at?->diffForHumans() ?? 'Just now' }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                </x-nino.detail-section>

                <x-nino.detail-section title="Location" subtitle="Supplier address and geographic coverage for logistics handoff.">
                    <div class="space-y-4">
                        <div>
                            <p class="detail-kicker">Address</p>
                            <p class="detail-value">{{ $supplier->address ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">City</p>
                            <p class="detail-value">{{ $supplier->city ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Country</p>
                            <p class="detail-value">{{ $supplier->country ?: 'Not provided' }}</p>
                        </div>
                    </div>
                </x-nino.detail-section>
            </div>
        </div>
    </div>
@endsection
