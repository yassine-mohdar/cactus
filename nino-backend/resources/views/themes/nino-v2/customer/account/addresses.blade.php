<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer account</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Manage addresses</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Keep your default billing and shipping addresses accurate so checkout and shipment visibility stay clean.</p>
                </div>
                <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
            </div>

            @if (session('success'))
                <x-nino.inline-alert tone="success" title="Saved" class="mt-6">{{ session('success') }}</x-nino.inline-alert>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
            <section class="surface-panel p-6 sm:p-8">
                <h2 class="text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Saved addresses</h2>
                <div class="mt-6 space-y-5">
                    @forelse($addresses as $address)
                        <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5 space-y-4">
                            <form method="POST" action="{{ route('customer.account.addresses.update', $address) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <select name="type" class="input-field !w-auto">
                                            <option value="billing" @selected($address->type === 'billing')>Billing</option>
                                            <option value="shipping" @selected($address->type === 'shipping')>Shipping</option>
                                        </select>
                                        @if($address->is_default)
                                            <span class="rounded-full bg-[#245848]/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#245848]">Default</span>
                                        @endif
                                    </div>
                                    <label class="toggle-row text-sm">
                                        <input type="hidden" name="is_default" value="0">
                                        <input type="checkbox" name="is_default" value="1" class="h-4 w-4" @checked($address->is_default)>
                                        Default
                                    </label>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <input type="text" name="first_name" value="{{ $address->first_name }}" class="input-field" placeholder="First name">
                                    <input type="text" name="last_name" value="{{ $address->last_name }}" class="input-field" placeholder="Last name">
                                    <input type="text" name="company" value="{{ $address->company }}" class="input-field" placeholder="Company">
                                    <input type="text" name="phone" value="{{ $address->phone }}" class="input-field" placeholder="Phone">
                                </div>
                                <div class="grid gap-4">
                                    <input type="text" name="address_line_1" value="{{ $address->address_line_1 }}" class="input-field" placeholder="Address line 1">
                                    <input type="text" name="address_line_2" value="{{ $address->address_line_2 }}" class="input-field" placeholder="Address line 2">
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <input type="text" name="city" value="{{ $address->city }}" class="input-field" placeholder="City">
                                    <input type="text" name="state" value="{{ $address->state }}" class="input-field" placeholder="State">
                                    <input type="text" name="postal_code" value="{{ $address->postal_code }}" class="input-field" placeholder="Postal code">
                                    <input type="text" name="country" value="{{ $address->country }}" class="input-field" placeholder="Country code">
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="btn-primary">Save changes</button>
                            </form>
                                    <form method="POST" action="{{ route('customer.account.addresses.destroy', $address) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-secondary">Delete</button>
                                    </form>
                                </div>
                            </div>
                    @empty
                        <x-nino.empty-state title="No saved addresses" description="Add a billing or shipping address to speed up future checkouts." icon="home_pin" />
                    @endforelse
                </div>
            </section>

            <section class="surface-panel p-6 sm:p-8">
                <h2 class="text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Add a new address</h2>
                <form method="POST" action="{{ route('customer.account.addresses.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <select name="type" class="input-field">
                            <option value="billing">Billing</option>
                            <option value="shipping">Shipping</option>
                        </select>
                        <label class="toggle-row">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1" class="h-4 w-4">
                            Set as default
                        </label>
                        <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="input-field" placeholder="First name">
                        <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="input-field" placeholder="Last name">
                        <input type="text" name="company" value="{{ old('company') }}" class="input-field" placeholder="Company">
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="input-field" placeholder="Phone">
                    </div>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" class="input-field" placeholder="Address line 1">
                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" class="input-field" placeholder="Address line 2">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <input type="text" name="city" value="{{ old('city') }}" class="input-field" placeholder="City">
                        <input type="text" name="state" value="{{ old('state') }}" class="input-field" placeholder="State">
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="input-field" placeholder="Postal code">
                        <input type="text" name="country" value="{{ old('country', 'MA') }}" class="input-field" placeholder="Country code">
                    </div>
                    <button type="submit" class="btn-primary">Add address</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
