@extends('admin.layouts.app')

@section('title', 'Edit Customer: ' . $customer->name)

@section('header')
    <div class="mb-6">
        <a href="{{ route('admin.customers.show', $customer) }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Profile
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">Edit Customer: {{ $customer->name }}</h1>
        <p class="text-xs text-slate-500 mt-1">Update customer personal and account information.</p>
    </div>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('admin.customers.update', $customer) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-slate-200 bg-slate-50/10">
                <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">edit_note</span> Edit Account Information
                </h2>
            </div>
            
            <div class="p-6">
                @include('admin.customers.form')
                <div class="mt-4 p-4 bg-slate-50/20 rounded-md border border-slate-200 text-xs text-slate-500 flex items-start gap-2">
                    <span class="material-symbols-outlined text-sm mt-0.5">info</span>
                    <div>
                        Leave the password field empty if you don't want to change it.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-8 py-3 bg-slate-800 text-white rounded-md text-sm font-bold hover:bg-slate-800/90 transition-all shadow-sm shadow-sm flex items-center gap-2 active:scale-[0.98]">
                <span class="material-symbols-outlined text-sm">save</span>
                Update Customer
            </button>
            <a href="{{ route('admin.customers.show', $customer) }}" class="px-6 py-3 text-sm font-semibold text-outline hover:text-slate-900 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
