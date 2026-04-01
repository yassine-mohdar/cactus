@php
    $isSelected = (bool) ($isSelected ?? false);
    $compact = (bool) ($compact ?? false);
    $metaRowMargin = $compact ? 'mt-2.5' : 'mt-3';
    $provider = $method->shippingCarrier?->provider
        ? strtoupper((string) $method->shippingCarrier->provider)
        : null;

    if ($quote['is_api']) {
        $badgeLabel = $quote['has_match']
            ? ($quote['source'] === 'forced' ? 'Forced district price' : 'Live API district price')
            : 'Pending district';
        $compactBadgeLabel = $quote['has_match']
            ? ($quote['source'] === 'forced' ? 'Forced price' : 'API price')
            : 'Pending';
        $badgeClasses = $quote['has_match']
            ? 'bg-[#EFF4F1] text-[#245848]'
            : 'bg-[#F8EFE7] text-[#8C5E2F]';
        $helperLabel = $quote['has_match']
            ? trim(($quote['district'] ?? $quote['city'] ?? '').($quote['updated_at'] ? ' · synced '.$quote['updated_at'] : ''))
            : ($quote['message'] ?? 'Select the destination district to resolve the live rate.');
        $compactHelperLabel = $quote['has_match']
            ? ($quote['district'] ?? $quote['city'] ?? '')
            : 'Choose district to fetch the live API price.';
    } else {
        $badgeLabel = 'Manual rate';
        $compactBadgeLabel = 'Manual';
        $badgeClasses = 'bg-[#F6F2EC] text-[#8C7B65]';
        $helperLabel = $method->free_shipping_threshold
            ? 'Free above '.number_format((float) $method->free_shipping_threshold, 2).' MAD'
            : 'Flat delivery rate for the selected country.';
        $compactHelperLabel = $method->free_shipping_threshold
            ? 'Free above '.number_format((float) $method->free_shipping_threshold, 2).' MAD'
            : 'Flat delivery rate';
    }

    $priceLabel = $quote['is_api'] && ! $quote['has_match']
        ? 'Select district'
        : (($quote['free_applied'] ?? false)
            ? 'Free'
            : number_format((float) ($quote['cost'] ?? 0), 2).' MAD');

    $etaValue = trim((string) ($quote['eta'] ?? ''));

    if ($quote['is_api'] && ! $quote['has_match']) {
        $etaLabel = 'Awaiting district';
    } elseif ($etaValue === '') {
        $etaLabel = 'ETA not set';
    } elseif (preg_match('/^\d+$/', $etaValue)) {
        $etaLabel = $etaValue.' business day'.((int) $etaValue === 1 ? '' : 's');
    } elseif (preg_match('/^\d+\s*-\s*\d+$/', $etaValue)) {
        $etaLabel = $etaValue.' business days';
    } else {
        $etaLabel = $etaValue;
    }
@endphp

<label
    class="block cursor-pointer rounded-[1.05rem] border transition-all"
    @class([
        'px-3.5 py-3' => $compact,
        'px-4 py-3' => ! $compact,
        'border-transparent bg-[#FFFCF8] ring-1 ring-[rgba(36,88,72,0.16)] shadow-[0_16px_28px_-26px_rgba(23,48,42,0.22)]' => $compact && $isSelected,
        'border-transparent bg-[rgba(255,255,255,0.68)] shadow-[0_8px_18px_-20px_rgba(23,48,42,0.16)] hover:bg-[rgba(255,255,255,0.84)]' => $compact && ! $isSelected,
        'border-[rgba(36,88,72,0.34)] bg-[#FEFCF8] shadow-[0_18px_34px_-28px_rgba(23,48,42,0.35)] ring-1 ring-[#245848]/10' => ! $compact && $isSelected,
        'border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] hover:border-[rgba(36,88,72,0.26)]' => ! $compact && ! $isSelected,
    ])
>
    @if($compact)
        <div class="flex items-start gap-2.5">
            <input
                type="radio"
                wire:model.live="shipping_method_id"
                name="shipping_method_id"
                value="{{ $method->id }}"
                class="mt-1 h-4 w-4 border-[rgba(120,112,95,0.32)] text-[#245848] focus:ring-[#245848]/20"
            >

            <div class="min-w-0 flex-1">
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3 gap-y-1">
                    <p class="truncate text-[13px] font-semibold text-[#17302A]">{{ $method->name }}</p>
                    <p class="text-[14px] font-black text-[#17302A]">{{ $priceLabel }}</p>

                    <p class="truncate text-[10px] uppercase tracking-[0.16em] text-[#8C7B65]">
                        {{ $method->carrierLabel() }}
                        @if($provider)
                            <span>· {{ $provider }}</span>
                        @endif
                    </p>
                    <p class="text-right text-[11px] text-[#617169]">{{ $etaLabel }}</p>
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $badgeClasses }}">
                        {{ $compactBadgeLabel }}
                    </span>
                </div>

                @if($compactHelperLabel !== '')
                    <p class="mt-1.5 break-words text-[10px] leading-4 text-[#617169]">
                        {{ $compactHelperLabel }}
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="flex items-start gap-3">
            <input
                type="radio"
                wire:model.live="shipping_method_id"
                name="shipping_method_id"
                value="{{ $method->id }}"
                class="mt-1 h-4 w-4 border-[rgba(120,112,95,0.32)] text-[#245848] focus:ring-[#245848]/20"
            >

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-[#17302A] text-sm">{{ $method->name }}</p>
                        <p class="mt-1 truncate uppercase tracking-[0.16em] text-[#8C7B65] text-[11px]">
                            {{ $method->carrierLabel() }}
                            @if($provider)
                                <span>· {{ $provider }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="font-black text-sm text-[#17302A]">{{ $priceLabel }}</p>
                        <p class="mt-1 text-xs text-[#617169]">{{ $etaLabel }}</p>
                    </div>
                </div>

                <div class="{{ $metaRowMargin }} flex flex-wrap items-center justify-between gap-2.5">
                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $badgeClasses }}">
                        {{ $badgeLabel }}
                    </span>

                    @if($helperLabel !== '')
                        <p class="min-w-0 flex-1 text-right text-[11px] text-[#617169]">
                            {{ $helperLabel }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</label>
