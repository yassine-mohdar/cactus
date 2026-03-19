@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-500 hover:text-sage mb-1 block">&larr; Back to Staff</a>
            <h1 class="text-2xl font-bold text-ink">Add Staff Member</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm max-w-3xl">
        <form action="{{ route('admin.staff.store') }}" method="POST" class="p-6 md:p-8">
            @csrf

            <div class="space-y-6">
                <!-- Profile Base -->
                <div>
                    <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Profile Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="input-field" placeholder="Jane Doe">
                            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="input-field" placeholder="jane@ninoworld.com">
                            @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="input-field" placeholder="+212 6... ">
                            @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Status *</label>
                            <select name="status" required class="input-field">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive (Needs Activation)</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Access & Roles -->
                <div class="pt-4 border-t border-gray-100">
                    <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Access & Roles</h3>
                    
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign Roles *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($roles as $role)
                                <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 border border-transparent hover:border-gray-200 transition cursor-pointer">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded text-sage focus:ring-sage" {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                    <span class="text-sm font-medium text-ink">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('roles') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if(auth()->user()->organization_scope === 'platform' || auth()->user()->isSuperAdmin())
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Scope</label>
                            <select name="organization_scope" class="input-field">
                                <option value="platform" {{ old('organization_scope') == 'platform' ? 'selected' : '' }}>Platform (Global)</option>
                                <option value="franchise" {{ old('organization_scope') == 'franchise' ? 'selected' : '' }}>Franchise View</option>
                                <option value="branch" {{ old('organization_scope') == 'branch' ? 'selected' : '' }}>Branch View</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Limits what data this user can see and edit.</p>
                            @error('organization_scope') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="p-3 bg-sage/5 rounded-lg border border-sage/20 mb-5">
                            <p class="text-sm text-sage">This user will automatically inherit your organization scope ({{ ucfirst(auth()->user()->organization_scope) }}).</p>
                        </div>
                    @endif
                </div>

                <!-- Security -->
                <div class="pt-4 border-t border-gray-100">
                    <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Security</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Password *</label>
                            <input type="password" name="password" required class="input-field">
                            @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required class="input-field">
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition border border-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        Create Staff Member
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
