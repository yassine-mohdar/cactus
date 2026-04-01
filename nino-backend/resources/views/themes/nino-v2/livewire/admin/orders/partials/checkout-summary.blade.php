@php
    $mode = $mode ?? 'panel';
    $isSidebar = $mode === 'sidebar';
    $statusTone = $checkoutStatus['tone'] ?? 'pending';
    $statusClasses = match ($statusTone) {
        'ready' => 'border-[#CFE3D8] bg-[#F3FAF6] text-[#245848]',
        'attention' => 'border-[#EFD7B6] bg-[#FBF6EA] text-[#8C5E2F]',
        default => 'border-[rgba(145,133,109,0.18)] bg-[#F8F4EC] text-[#6E6252]',
    };
    $isCompact = $mode === 'compact';
    $panelPadding = $isSidebar ? 'p-4' : 'p-5 md:p-5';
    $sectionSurfaceClasses = $isSidebar
        ? 'border-[rgba(120,112,95,0.10)] bg-[linear-gradient(180deg,rgba(255,255,255,0.98)_0%,rgba(252,251,248,0.94)_100%)] shadow-[0_22px_40px_-34px_rgba(23,48,42,0.22)]'
        : '';
    $fieldSuffix = $isSidebar ? 'sidebar' : 'panel';
    $paymentMethodFieldId = 'payment-method-'.$fieldSuffix;
    $couponFieldId = 'coupon-code-'.$fieldSuffix;
    $adminNotesFieldId = 'admin-notes-'.$fieldSuffix;
    $appliedCoupon = $appliedCoupon ?? [];
    $discountTotal = (float) ($discountTotal ?? 0);
    $couponFeedback = $couponFeedback ?? null;
@endphp

<section class="page-section {{ $panelPadding }} {{ $sectionSurfaceClasses }}">
    @if(! $isCompact)
        <div class="{{ $isSidebar ? 'mb-3' : 'mb-4' }}">
            <p class="filter-label">Checkout</p>
            <h2 class="mt-1.5 {{ $isSidebar ? 'text-base' : 'text-lg' }} font-bold tracking-tight text-[#17302A]">Order Summary</h2>
            <p class="mt-1 {{ $isSidebar ? 'max-w-[28ch] text-[11px] leading-5' : 'text-sm' }} text-[#617169]">Review totals, payment settings, and the final operator note before placing the order.</p>
        </div>
    @endif

    <div class="rounded-[1.15rem] border {{ $isSidebar ? 'px-3.5 py-3' : 'px-4 py-3' }} {{ $statusClasses }}">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] opacity-80">Status</p>
                <p class="mt-1 text-sm font-semibold">{{ $checkoutStatus['title'] }}</p>
            </div>
            @if(($checkoutStatus['tone'] ?? 'pending') === 'ready')
                <span class="material-symbols-outlined text-[1.2rem]">task_alt</span>
            @else
                <span class="material-symbols-outlined text-[1.2rem]">schedule</span>
            @endif
        </div>
        <p class="mt-2 text-xs leading-5 opacity-90">{{ $checkoutStatus['message'] }}</p>
    </div>

    @if($isCompact)
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-[1rem] border border-[rgba(120,112,95,0.12)] bg-[#FCFBF8] px-4 py-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Subtotal</p>
                <p class="mt-2 text-sm font-semibold text-[#17302A]">{{ number_format($this->subtotal, 2) }} MAD</p>
            </div>
            <div class="rounded-[1rem] border border-[rgba(120,112,95,0.12)] bg-[#FCFBF8] px-4 py-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Shipping</p>
                <p class="mt-2 text-sm font-semibold text-[#17302A]">{{ $checkoutStatus['shipping_display'] }}</p>
            </div>
            <div class="rounded-[1rem] border border-[rgba(120,112,95,0.12)] bg-[#FCFBF8] px-4 py-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Grand Total</p>
                <p class="mt-2 text-sm font-black text-[#17302A]">{{ $checkoutStatus['grand_total_display'] }}</p>
            </div>
        </div>
    @else
        <div class="mt-3.5 rounded-[1.15rem] border border-[rgba(120,112,95,0.10)] bg-[rgba(255,255,255,0.84)] {{ $isSidebar ? 'p-3.5' : 'p-4' }}">
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-[#617169]">Subtotal</span>
                    <span class="font-semibold text-[#17302A]">{{ number_format($this->subtotal, 2) }} MAD</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-[#617169]">Tax</span>
                    <span class="font-semibold text-[#17302A]">0.00 MAD</span>
                </div>
                @if($discountTotal > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-[#617169]">Discount</span>
                        <span class="font-semibold text-[#C45143]">-{{ number_format($discountTotal, 2) }} MAD</span>
                    </div>
                @endif
                <div class="flex justify-between border-b border-[rgba(145,133,109,0.16)] pb-3 text-sm">
                    <span class="text-[#617169]">Shipping</span>
                    <span class="font-semibold text-[#17302A]">{{ $checkoutStatus['shipping_display'] }}</span>
                </div>
                <div class="flex justify-between text-base">
                    <span class="font-bold uppercase tracking-[0.08em] text-[#17302A]">Grand Total</span>
                    <span class="font-black text-[#17302A]">{{ $checkoutStatus['grand_total_display'] }}</span>
                </div>
            </div>
        </div>

        <div class="{{ $isSidebar ? 'mt-3.5 space-y-3.5' : 'mt-4 space-y-4' }}">
            <div>
                <label class="filter-label" for="{{ $paymentMethodFieldId }}">Payment Method</label>
                <select wire:model.live="payment_method" id="{{ $paymentMethodFieldId }}" class="input-field">
                    @foreach($availablePaymentMethods as $paymentOption)
                        <option value="{{ $paymentOption['value'] }}" @selected($this->payment_method === $paymentOption['value'])>{{ $paymentOption['label'] }}</option>
                    @endforeach
                </select>
                @php
                    $selectedPaymentMethodOption = collect($availablePaymentMethods)
                        ->first(fn (array $paymentOption) => (string) ($paymentOption['value'] ?? '') === (string) $this->payment_method);
                    $selectedPaymentMethodMeta = data_get($selectedPaymentMethodOption, 'meta');
                @endphp
                @if(filled($selectedPaymentMethodMeta))
                    <p wire:key="payment-method-meta-{{ $fieldSuffix }}-{{ $this->payment_method }}" class="mt-2 text-[11px] text-[#617169]">{{ $selectedPaymentMethodMeta }}</p>
                @endif
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="filter-label" for="{{ $couponFieldId }}">Coupon</label>
                    @if(($appliedCoupon['valid'] ?? false) && filled($this->applied_coupon_code))
                        <span class="inline-flex items-center rounded-full bg-[#EFF4F1] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#245848]">
                            {{ $this->applied_coupon_code }}
                        </span>
                    @endif
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                    <input wire:model.defer="coupon_code" id="{{ $couponFieldId }}" type="text" class="input-field" placeholder="Enter coupon code">
                    @if(($appliedCoupon['valid'] ?? false) && filled($this->applied_coupon_code))
                        <button type="button" wire:click="removeCoupon" class="inline-flex shrink-0 items-center rounded-[0.95rem] border border-[rgba(120,112,95,0.16)] px-3 py-2 text-xs font-semibold text-[#6E6252] transition hover:border-[rgba(120,112,95,0.28)] hover:text-[#17302A]">
                            Remove
                        </button>
                    @else
                        <button type="button" wire:click="applyCoupon" class="inline-flex shrink-0 items-center rounded-[0.95rem] border border-[#245848]/18 bg-[#EFF4F1] px-3 py-2 text-xs font-semibold text-[#245848] transition hover:border-[#245848]/28 hover:bg-[#E7F0EB]">
                            Apply
                        </button>
                    @endif
                </div>
                @if(($appliedCoupon['valid'] ?? false) && $discountTotal > 0)
                    <p class="mt-2 text-[11px] text-[#245848]">
                        {{ $appliedCoupon['formatted_discount'] ?? 'Coupon applied' }}.
                        Discount: {{ number_format($discountTotal, 2) }} MAD.
                    </p>
                @elseif(filled($this->applied_coupon_code) && ! ($appliedCoupon['valid'] ?? false))
                    <p class="mt-2 text-[11px] text-[#C45143]">
                        {{ $appliedCoupon['error'] ?? 'The applied coupon is no longer valid.' }}
                    </p>
                @elseif(filled(data_get($couponFeedback, 'message')))
                    <p class="mt-2 text-[11px] {{ data_get($couponFeedback, 'tone') === 'success' ? 'text-[#245848]' : 'text-[#C45143]' }}">
                        {{ data_get($couponFeedback, 'message') }}
                    </p>
                @endif
            </div>

            <div>
                <label class="filter-label" for="{{ $adminNotesFieldId }}">Internal Admin Notes</label>
                <textarea wire:model="admin_notes" id="{{ $adminNotesFieldId }}" rows="4" class="input-field" placeholder="Notes for the team..."></textarea>
            </div>

            @error('save')
                <x-nino.inline-alert tone="danger" title="Order could not be created">
                    {{ $message }}
                </x-nino.inline-alert>
            @enderror

            <button
                type="submit"
                @disabled(! $canSubmit)
                class="btn-primary w-full justify-center gap-2 {{ $isSidebar ? 'py-2.5' : 'py-3' }} disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading wire:target="save" class="material-symbols-outlined animate-spin text-[1.1rem]">refresh</span>
                Place Manual Order
            </button>

            <p class="text-center text-xs leading-5 text-[#617169]">
                @if($canSubmit)
                    Creating this order will generate the order record, line items, and address snapshots immediately.
                @else
                    {{ $checkoutStatus['message'] }}
                @endif
            </p>
        </div>
    @endif
</section>
