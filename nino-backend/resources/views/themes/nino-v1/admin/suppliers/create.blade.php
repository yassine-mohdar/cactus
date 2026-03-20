@extends('admin.layouts.app')

@section('title', 'New Supplier')

@section('header')
    <div class="mb-6">
        <a href="{{ route('admin.inventory.suppliers.index') }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Suppliers
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">New Supplier</h1>
        <p class="text-xs text-slate-500 mt-1">Register a new physical product supplier.</p>
    </div>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('admin.inventory.suppliers.store') }}" method="POST">
        @csrf
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-6 p-6">
            @include('admin.suppliers.form')
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-8 py-3 bg-slate-800 text-white rounded-md text-sm font-bold hover:bg-slate-800/90 transition-all shadow-sm shadow-sm flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-sm">save</span>
                Create Supplier
            </button>
            <a href="{{ route('admin.inventory.suppliers.index') }}" class="px-6 py-3 text-sm font-semibold text-outline hover:text-slate-900 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
