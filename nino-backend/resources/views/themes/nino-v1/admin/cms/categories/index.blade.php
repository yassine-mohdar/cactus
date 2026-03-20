@extends('admin.layouts.app')
@section('title', 'Blog Categories')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Blog Categories</h1>
        <p class="text-sm text-slate-500 mt-1">Organize blog content into categories.</p>
    </div>
    <a href="{{ route('admin.cms.categories.create') }}" class="btn-primary">+ New Category</a>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>@endif
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Slug</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Posts</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($categories as $cat)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3 font-medium text-slate-900">{{ $cat->name }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs font-mono">{{ $cat->slug }}</td>
                <td class="px-4 py-3 text-center text-slate-900">{{ $cat->posts_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cat->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('admin.cms.categories.edit', $cat) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                    <form action="{{ route('admin.cms.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button></form>
                </td>
            </tr>
            @if($cat->children->count())
                @foreach($cat->children as $child)
                <tr class="hover:bg-white-dim/50 transition-colors bg-white-dim/20">
                    <td class="px-4 py-3 font-medium text-slate-900 pl-8">↳ {{ $child->name }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs font-mono">{{ $child->slug }}</td>
                    <td class="px-4 py-3 text-center text-slate-900">—</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $child->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $child->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.cms.categories.edit', $child) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.cms.categories.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button></form>
                    </td>
                </tr>
                @endforeach
            @endif
            @empty
            <tr><td colspan="5" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No categories yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
