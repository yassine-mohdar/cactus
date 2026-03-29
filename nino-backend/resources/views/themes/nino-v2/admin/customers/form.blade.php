<div class="form-main">
    <x-nino.entity-form-section title="Customer Identity" subtitle="Capture the account owner’s name and the contact channels support and fulfillment rely on.">
        <div class="entity-section-grid">
            <div>
                <label for="customer-first-name" class="filter-label">First Name</label>
                <input type="text" name="first_name" id="customer-first-name" value="{{ old('first_name', $customer->first_name) }}" class="input-field @error('first_name') border-red-300 focus:border-red-400 focus:ring-red-400/20 @enderror" placeholder="John">
                @error('first_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="customer-last-name" class="filter-label">Last Name</label>
                <input type="text" name="last_name" id="customer-last-name" value="{{ old('last_name', $customer->last_name) }}" class="input-field @error('last_name') border-red-300 focus:border-red-400 focus:ring-red-400/20 @enderror" placeholder="Doe">
                @error('last_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="customer-email" class="filter-label">Email Address</label>
                <input type="email" name="email" id="customer-email" value="{{ old('email', $customer->email) }}" class="input-field @error('email') border-red-300 focus:border-red-400 focus:ring-red-400/20 @enderror" placeholder="john.doe@example.com">
                @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="customer-phone" class="filter-label">Phone Number</label>
                <input type="text" name="phone" id="customer-phone" value="{{ old('phone', $customer->phone) }}" class="input-field" placeholder="+212 600 000 000">
                @error('phone') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Credentials" subtitle="Set the initial login credentials or rotate the password when customer access needs to be reset.">
        <div class="entity-section-grid">
            <div>
                <label for="customer-password" class="filter-label">Password</label>
                <input type="password" name="password" id="customer-password" class="input-field @error('password') border-red-300 focus:border-red-400 focus:ring-red-400/20 @enderror" placeholder="••••••••">
                @error('password') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="customer-password-confirmation" class="filter-label">Confirm Password</label>
                <input type="password" name="password_confirmation" id="customer-password-confirmation" class="input-field" placeholder="••••••••">
            </div>
        </div>
    </x-nino.entity-form-section>
</div>

<div class="form-sidebar">
    <x-nino.entity-form-section title="Account Controls" subtitle="Keep account status and operator guidance visible during customer maintenance.">
        <div class="space-y-4">
            <div>
                <label for="customer-status" class="filter-label">Account Status</label>
                <select name="status" id="customer-status" class="input-field">
                    <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ old('status', $customer->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                @error('status') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            @if($customer->exists)
                <x-nino.entity-detail-grid>
                    <div>
                        <p class="detail-kicker">Customer ID</p>
                        <p class="detail-value-mono">#{{ $customer->id }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Joined</p>
                        <p class="detail-value">{{ $customer->created_at?->format('M j, Y') ?? 'Recently' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            @endif

            <div class="form-note">
                {{ $customer->exists ? "Leave the password fields blank if you don't want to change the customer's credentials." : 'New customer passwords are required because this flow provisions an account manually.' }}
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
