@php
    $compact = (bool) ($compact ?? false);
    $panelClasses = $compact ? 'p-4' : 'p-5';
    $surfaceClasses = $compact
        ? 'border-[rgba(120,112,95,0.10)] bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(252,251,248,0.92)_100%)] shadow-[0_22px_40px_-34px_rgba(23,48,42,0.22)]'
        : '';
    $listClasses = $compact
        ? 'space-y-2.5 max-h-[24rem] overflow-y-auto pr-1'
        : 'space-y-2.5';
@endphp

<section class="page-section {{ $panelClasses }} {{ $surfaceClasses }}">
    <div class="{{ $compact ? 'mb-2.5' : 'mb-3' }}">
        <p class="filter-label">Shipping Method</p>
        <h3 class="mt-1.5 {{ $compact ? 'text-[0.95rem]' : 'text-base' }} font-bold text-[#17302A]">Available Methods</h3>
        <p class="mt-1 {{ $compact ? 'max-w-[28ch] text-[11px] leading-5' : 'text-sm' }} text-[#617169]">Choose the method that matches this destination and basket value.</p>
    </div>

    <div class="{{ $listClasses }}">
        @forelse($shippingMethods as $method)
            @php($quote = $shippingMethodQuotes[$method->id] ?? ['cost' => 0, 'eta' => null, 'is_api' => false, 'has_match' => false, 'source' => 'none', 'message' => null])
            @include('themes.nino-v2.livewire.admin.orders.partials.shipping-method-option', [
                'method' => $method,
                'quote' => $quote,
                'isSelected' => (string) $shipping_method_id === (string) $method->id,
                'compact' => $compact,
            ])
        @empty
            <div class="rounded-[1.15rem] border border-dashed border-[rgba(120,112,95,0.2)] bg-[#FCFBF8] px-4 py-5 text-sm text-[#617169]">
                No enabled shipping methods are configured yet for this destination. Add or expand method coverage from Shipping &gt; Methods.
            </div>
        @endforelse
    </div>

    @error('shipping_method_id')
        <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
    @enderror
</section>
