@if($errors->any())
    <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
        {{ collect($errors->all())->join(' ') }}
    </x-nino.inline-alert>
@endif

<form action="{{ isset($method) ? route('admin.shipping.methods.update', $method) : route('admin.shipping.methods.store') }}" method="POST" class="form-layout">
    @csrf
    @if(isset($method))
        @method('PUT')
    @endif

    <div class="form-main">
        <x-nino.entity-form-section title="Service Profile" subtitle="Name the shipping method, map it to a carrier, and describe the customer-facing promise.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="shipping-method-name">Name</label>
                    <input id="shipping-method-name" type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required class="input-field" placeholder="Standard Delivery">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-carrier">Carrier</label>
                    <input id="shipping-method-carrier" type="text" name="carrier" value="{{ old('carrier', $method->carrier ?? '') }}" class="input-field" placeholder="Amana, DHL, Chronopost...">
                </div>
                <div class="md:col-span-2">
                    <label class="filter-label" for="shipping-method-description">Description</label>
                    <textarea id="shipping-method-description" name="description" rows="3" class="input-field" placeholder="Brief description shown to customers">{{ old('description', $method->description ?? '') }}</textarea>
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section title="Pricing and SLA" subtitle="Capture base cost, free-shipping threshold, and delivery expectations.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="shipping-method-base-cost">Base Cost (MAD)</label>
                    <input id="shipping-method-base-cost" type="number" step="0.01" name="base_cost" value="{{ old('base_cost', $method->base_cost ?? '0.00') }}" required class="input-field">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-free-threshold">Free Shipping Threshold</label>
                    <input id="shipping-method-free-threshold" type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $method->free_shipping_threshold ?? '') }}" class="input-field" placeholder="Leave blank for none">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-estimated-days">Estimated Delivery</label>
                    <input id="shipping-method-estimated-days" type="text" name="estimated_days" value="{{ old('estimated_days', $method->estimated_days ?? '') }}" class="input-field" placeholder="2-4 business days">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-sort-order">Sort Order</label>
                    <input id="shipping-method-sort-order" type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="input-field">
                </div>
            </div>
        </x-nino.entity-form-section>
    </div>

    <div class="form-sidebar">
        <x-nino.entity-form-section title="Operational Controls" subtitle="Enable the method and review routing notes before saving.">
            <div class="space-y-4">
                <label for="shipping-method-enabled" class="toggle-row">
                    <input type="checkbox" id="shipping-method-enabled" name="is_enabled" value="1" {{ old('is_enabled', $method->is_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                    Method is enabled for checkout and warehouse routing
                </label>

                @if(isset($method))
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Current Slug</p>
                            <p class="detail-value-mono">{{ $method->slug }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Linked Shipments</p>
                            <p class="detail-value-mono">{{ number_format($method->shipments_count ?? 0) }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                @endif

                <div class="form-note">
                    Keep method names stable once used in operations. Pricing and SLA can change, but abrupt renames make support and warehouse reconciliation harder.
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-nino.button href="{{ route('admin.shipping.methods.index') }}" variant="outline">Cancel</x-nino.button>
                    <x-nino.button type="submit" variant="primary">{{ isset($method) ? 'Update Method' : 'Create Method' }}</x-nino.button>
                </div>
            </x-slot:footer>
        </x-nino.entity-form-section>
    </div>
</form>
