<div class="form-main">
    <x-nino.entity-form-section title="Branch Profile" subtitle="Set the branch name and core contact channels used by warehouse and support teams.">
        <div class="entity-section-grid">
            <div>
                <label for="branch-name" class="filter-label">Branch Name</label>
                <input type="text" name="name" id="branch-name" value="{{ old('name', $branch->name) }}" required class="input-field" placeholder="Casablanca Warehouse">
                @error('name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="branch-email" class="filter-label">Email Address</label>
                <input type="email" name="email" id="branch-email" value="{{ old('email', $branch->email) }}" class="input-field" placeholder="branch@example.com">
                @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="branch-phone" class="filter-label">Phone Number</label>
                <input type="text" name="phone" id="branch-phone" value="{{ old('phone', $branch->phone) }}" class="input-field" placeholder="+212 600 000000">
                @error('phone') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="branch-status" class="filter-label">Operational Status</label>
                <select name="status" id="branch-status" required class="input-field">
                    <option value="active" {{ old('status', $branch->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $branch->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Address and Coverage" subtitle="Use the physical location that should own stock, fulfillment, and local escalation.">
        <div class="entity-section-grid">
            <div class="md:col-span-2">
                <label for="branch-address" class="filter-label">Street Address</label>
                <textarea name="address" id="branch-address" rows="3" class="input-field" placeholder="Street, district, and delivery instructions">{{ old('address', $branch->address) }}</textarea>
                @error('address') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="branch-city" class="filter-label">City</label>
                <input type="text" name="city" id="branch-city" value="{{ old('city', $branch->city) }}" class="input-field" placeholder="Casablanca">
                @error('city') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="branch-country" class="filter-label">Country</label>
                <input type="text" name="country" id="branch-country" value="{{ old('country', $branch->country) }}" class="input-field" placeholder="Morocco">
                @error('country') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-nino.entity-form-section>
</div>

<div class="form-sidebar">
    <x-nino.entity-form-section title="Operator Notes" subtitle="Keep branch naming, coverage, and contact ownership stable once stock is routed here.">
        <div class="space-y-4">
            @if($branch->exists)
                <x-nino.entity-detail-grid>
                    <div>
                        <p class="detail-kicker">Record ID</p>
                        <p class="detail-value-mono">#{{ $branch->id }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Last Updated</p>
                        <p class="detail-value">{{ $branch->updated_at?->diffForHumans() ?? 'Just now' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            @endif

            <div class="form-note">
                Branches power stock visibility and routing. Use a location name the warehouse and support teams already recognize.
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
