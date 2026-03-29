<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Name --}}
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Branch Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}" required
                   class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Status --}}
        <div>
            <label for="status" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Status</label>
            <select name="status" id="status" required
                    class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all">
                <option value="active" {{ old('status', $branch->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $branch->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email', $branch->email) }}"
                   class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all"
                   placeholder="branch@example.com">
            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label for="phone" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Phone Number</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $branch->phone) }}"
                   class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all"
                   placeholder="+212 600 000000">
            @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Address --}}
    <div>
        <label for="address" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Street Address</label>
        <textarea name="address" id="address" rows="2"
                  class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all">{{ old('address', $branch->address) }}</textarea>
        @error('address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- City --}}
        <div>
            <label for="city" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">City</label>
            <input type="text" name="city" id="city" value="{{ old('city', $branch->city) }}"
                   class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all">
            @error('city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Country --}}
        <div>
            <label for="country" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-widest">Country</label>
            <input type="text" name="country" id="country" value="{{ old('country', $branch->country) }}"
                   class="w-full rounded-md border-slate-200 bg-slate-50/5 text-slate-900 focus:border-slate-400 focus:ring-slate-400/20 transition-all">
            @error('country') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
