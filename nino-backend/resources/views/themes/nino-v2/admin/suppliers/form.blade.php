<div class="form-main">
    <x-nino.entity-form-section title="Supplier Profile" subtitle="Capture the vendor identity and primary contact channels used by procurement and operations.">
        <div class="entity-section-grid">
            <div>
                <label for="supplier-name" class="filter-label">Supplier Name</label>
                <input type="text" name="name" id="supplier-name" value="{{ old('name', $supplier->name) }}" required class="input-field" placeholder="Atlas Packaging">
                @error('name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="supplier-status" class="filter-label">Status</label>
                <select name="status" id="supplier-status" required class="input-field">
                    <option value="active" {{ old('status', $supplier->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $supplier->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="supplier-email" class="filter-label">Email Address</label>
                <input type="email" name="email" id="supplier-email" value="{{ old('email', $supplier->email) }}" class="input-field" placeholder="supplier@example.com">
                @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="supplier-phone" class="filter-label">Phone Number</label>
                <input type="text" name="phone" id="supplier-phone" value="{{ old('phone', $supplier->phone) }}" class="input-field" placeholder="+212 600 000000">
                @error('phone') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Address and Coverage" subtitle="Store the supplier location used for logistics, invoicing, and fallback contact routing.">
        <div class="entity-section-grid">
            <div class="md:col-span-2">
                <label for="supplier-address" class="filter-label">Street Address</label>
                <textarea name="address" id="supplier-address" rows="3" class="input-field" placeholder="Street, area, and delivery notes">{{ old('address', $supplier->address) }}</textarea>
                @error('address') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="supplier-city" class="filter-label">City</label>
                <input type="text" name="city" id="supplier-city" value="{{ old('city', $supplier->city) }}" class="input-field" placeholder="Tangier">
                @error('city') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="supplier-country" class="filter-label">Country</label>
                <input type="text" name="country" id="supplier-country" value="{{ old('country', $supplier->country) }}" class="input-field" placeholder="Morocco">
                @error('country') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-nino.entity-form-section>
</div>

<div class="form-sidebar">
    <x-nino.entity-form-section title="Operator Notes" subtitle="Keep vendor naming stable and use the contact details the team actually relies on.">
        <div class="space-y-4">
            @if($supplier->exists)
                <x-nino.entity-detail-grid>
                    <div>
                        <p class="detail-kicker">Record ID</p>
                        <p class="detail-value-mono">#{{ $supplier->id }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Last Updated</p>
                        <p class="detail-value">{{ $supplier->updated_at?->diffForHumans() ?? 'Just now' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            @endif

            <div class="form-note">
                Supplier activity telemetry is not yet modeled in persistent purchase orders, so this profile should stay focused on trustworthy vendor contact data.
            </div>
        </div>

        <x-slot:footer>
            <div class="form-actions">
                <x-nino.button href="{{ $cancelUrl }}" variant="outline">Cancel</x-nino.button>
                <x-nino.button type="submit" variant="primary">{{ $submitLabel }}</x-nino.button>
            </div>
        </x-slot:footer>
    </x-nino.entity-form-section>
</div>
