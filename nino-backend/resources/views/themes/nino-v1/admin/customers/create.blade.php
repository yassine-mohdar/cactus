@extends('admin.layouts.app')

@section('title', 'New Customer')

@section('header')
    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Customers
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">Add New Customer</h1>
        <p class="text-xs text-slate-500 mt-1">Create a new customer account manually for orders and tracking.</p>
    </div>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('admin.customers.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-slate-200 bg-slate-50/10">
                <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">person_add</span> Personal Account Information
                </h2>
            </div>
            
            <div class="p-6">
                @include('admin.customers.form')
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-8 py-3 bg-slate-800 text-white rounded-md text-sm font-bold hover:bg-slate-800/90 transition-all shadow-sm shadow-sm flex items-center gap-2 active:scale-[0.98]">
                <span class="material-symbols-outlined text-sm">save</span>
                Create Customer
            </button>
            <a href="{{ route('admin.customers.index') }}" class="px-6 py-3 text-sm font-semibold text-outline hover:text-slate-900 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
