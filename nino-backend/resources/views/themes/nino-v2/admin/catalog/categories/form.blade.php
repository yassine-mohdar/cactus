@php
    $isEditing = isset($category);
    $currentParent = old('parent_id', $isEditing ? $category->parent_id : null);
@endphp

<div class="form-layout">
    <div class="form-main">
        <x-admin.card title="Category identity">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#245848]">Required</span>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Define how this taxonomy node should appear in the storefront, internal catalog tooling, and search-facing metadata.</p>

                <x-admin.input name="name" label="Category name" :value="old('name', $isEditing ? $category->name : null)" :error="$errors->first('name')" required />

                <x-admin.textarea name="description" label="Description" rows="5" :error="$errors->first('description')">{{ old('description', $isEditing ? $category->description : null) }}</x-admin.textarea>
            </div>
        </x-admin.card>

        <x-admin.card title="Search visibility">
            <div class="space-y-5">
                <p class="form-copy">Use custom SEO fields only when the category needs a search message different from its shopper-facing copy.</p>

                <x-admin.input name="meta_title" label="Meta title" :value="old('meta_title', $isEditing ? $category->meta_title : null)" :error="$errors->first('meta_title')" />

                <x-admin.textarea name="meta_description" label="Meta description" rows="3" :error="$errors->first('meta_description')">{{ old('meta_description', $isEditing ? $category->meta_description : null) }}</x-admin.textarea>
            </div>
        </x-admin.card>
    </div>

    <div class="form-sidebar">
        <x-admin.card title="Structure and visibility">
            <div class="space-y-5">
                <p class="form-copy">Choose where this category lives in the catalog tree and whether it should be available to merchandising teams immediately.</p>

                <x-admin.select name="parent_id" label="Parent category" :error="$errors->first('parent_id')">
                    <option value="">None (top level)</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) $currentParent === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </x-admin.select>

                <x-admin.input type="number" name="sort_order" label="Sort order" :value="old('sort_order', $isEditing ? $category->sort_order : 0)" :error="$errors->first('sort_order')" />

                <label class="surface-selection flex items-start gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEditing ? $category->is_active : true)) class="mt-1 size-4 rounded border-[rgba(145,133,109,0.34)] text-[#245848] focus:ring-[#245848]">
                    <span class="block">
                        <span class="block text-sm font-semibold text-[#17302A]">Active in catalog</span>
                        <span class="mt-1 block text-sm leading-6 text-[#617169]">Allow this category to be used in navigation, filtering, and merchandising placements.</span>
                    </span>
                </label>
            </div>
        </x-admin.card>

        <x-admin.card title="Primary media">
            <div class="space-y-4">
                <p class="form-copy">Add a representative image to improve merchandising previews and visual organization inside the admin.</p>

                @if($isEditing && $category->image_path)
                    <img src="{{ Storage::url($category->image_path) }}" alt="{{ $category->name }}" class="h-48 w-full rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] object-cover shadow-[0_18px_38px_-28px_rgba(23,48,42,0.45)]">
                @endif

                <input type="file" name="image" accept="image/*" class="form-upload">

                @error('image')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-admin.card>

        <x-admin.card title="Ready to save">
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">Mode</span>
                    <span class="meta-value">{{ $isEditing ? 'Updating existing category' : 'Creating new category' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Hierarchy</span>
                    <span class="meta-value">{{ $currentParent ? 'Nested under parent' : 'Top level category' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Visibility</span>
                    <span class="meta-value">{{ old('is_active', $isEditing ? $category->is_active : true) ? 'Active' : 'Hidden' }}</span>
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-admin.button href="{{ route('admin.catalog.categories.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">
                        {{ $isEditing ? 'Update Category' : 'Save Category' }}
                    </x-admin.button>
                </div>
            </x-slot:footer>
        </x-admin.card>
    </div>
</div>
