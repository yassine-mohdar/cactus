<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="first_name" class="block text-sm font-bold text-slate-900 mb-2">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $customer->first_name) }}" 
                   class="w-full px-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all @error('first_name') border-red-500 @enderror" 
                   placeholder="John">
            @error('first_name') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="last_name" class="block text-sm font-bold text-slate-900 mb-2">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $customer->last_name) }}" 
                   class="w-full px-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all @error('last_name') border-red-500 @enderror" 
                   placeholder="Doe">
            @error('last_name') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-bold text-slate-900 mb-2">Email Address</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-outline">
                <span class="material-symbols-outlined text-base">mail</span>
            </span>
            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" 
                   class="w-full pl-11 pr-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all @error('email') border-red-500 @enderror" 
                   placeholder="john.doe@example.com">
        </div>
        @error('email') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-bold text-slate-900 mb-2">Phone Number</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-outline">
                <span class="material-symbols-outlined text-base">call</span>
            </span>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" 
                   class="w-full pl-11 pr-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all" 
                   placeholder="+212 600 000 000">
        </div>
        @error('phone') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="password" class="block text-sm font-bold text-slate-900 mb-2">Password</label>
            <input type="password" name="password" id="password" 
                   class="w-full px-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all @error('password') border-red-500 @enderror" 
                   placeholder="••••••••">
            @error('password') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-bold text-slate-900 mb-2">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" 
                   class="w-full px-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all" 
                   placeholder="••••••••">
        </div>
    </div>

    <div>
        <label for="status" class="block text-sm font-bold text-slate-900 mb-2">Account Status</label>
        <select name="status" id="status" class="w-full px-4 py-2.5 bg-canvas border border-slate-200 rounded-md text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 outline-none transition-all">
            <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ old('status', $customer->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        @error('status') <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p> @enderror
    </div>
</div>
