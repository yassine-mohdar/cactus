<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer account</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Manage addresses</h1>
                </div>
                <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
            </div>
            @if(session('success'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <div class="space-y-4">
                    @forelse($addresses as $address)
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                            <form method="POST" action="{{ route('customer.account.addresses.update', $address) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <select name="type" class="input-field">
                                    <option value="billing" @selected($address->type === 'billing')>Billing</option>
                                    <option value="shipping" @selected($address->type === 'shipping')>Shipping</option>
                                </select>
                                <input type="hidden" name="is_default" value="0">
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_default" value="1" @checked($address->is_default)> Default</label>
                                <input type="text" name="first_name" value="{{ $address->first_name }}" class="input-field" placeholder="First name">
                                <input type="text" name="last_name" value="{{ $address->last_name }}" class="input-field" placeholder="Last name">
                                <input type="text" name="company" value="{{ $address->company }}" class="input-field" placeholder="Company">
                                <input type="text" name="phone" value="{{ $address->phone }}" class="input-field" placeholder="Phone">
                                <input type="text" name="address_line_1" value="{{ $address->address_line_1 }}" class="input-field" placeholder="Address line 1">
                                <input type="text" name="address_line_2" value="{{ $address->address_line_2 }}" class="input-field" placeholder="Address line 2">
                                <input type="text" name="city" value="{{ $address->city }}" class="input-field" placeholder="City">
                                <input type="text" name="state" value="{{ $address->state }}" class="input-field" placeholder="State">
                                <input type="text" name="postal_code" value="{{ $address->postal_code }}" class="input-field" placeholder="Postal code">
                                <input type="text" name="country" value="{{ $address->country }}" class="input-field" placeholder="Country code">
                                <div class="flex gap-3">
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
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600">No saved addresses yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <form method="POST" action="{{ route('customer.account.addresses.store') }}" class="space-y-4">
                    @csrf
                    <select name="type" class="input-field">
                        <option value="billing">Billing</option>
                        <option value="shipping">Shipping</option>
                    </select>
                    <input type="hidden" name="is_default" value="0">
                    <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_default" value="1"> Set as default</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="input-field" placeholder="First name">
                    <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="input-field" placeholder="Last name">
                    <input type="text" name="company" value="{{ old('company') }}" class="input-field" placeholder="Company">
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="input-field" placeholder="Phone">
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" class="input-field" placeholder="Address line 1">
                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" class="input-field" placeholder="Address line 2">
                    <input type="text" name="city" value="{{ old('city') }}" class="input-field" placeholder="City">
                    <input type="text" name="state" value="{{ old('state') }}" class="input-field" placeholder="State">
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="input-field" placeholder="Postal code">
                    <input type="text" name="country" value="{{ old('country', 'MA') }}" class="input-field" placeholder="Country code">
                    <button type="submit" class="btn-primary">Add address</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
