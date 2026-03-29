@extends('admin.layouts.app')

@section('title', 'Branch: ' . $branch->name)

@section('header')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.inventory.branches.index') }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Branches
            </a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">{{ $branch->name }}</h1>
            <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-semibold">{{ $branch->code ?? 'NO-CODE' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.branches.edit', $branch) }}" class="px-5 py-2.5 bg-slate-50 text-slate-900 rounded-md text-sm font-bold hover:bg-slate-50/80 transition-all flex items-center gap-2 active:scale-95 border border-slate-200">
                <span class="material-symbols-outlined text-sm">edit</span>
                Edit Branch
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left Column: Info --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden p-6 text-center">
            <div class="w-24 h-24 bg-slate-50/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl text-slate-800">store</span>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-1">{{ $branch->name }}</h2>
            <span class="px-2 py-1 {{ $branch->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }} rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $branch->status === 'active' ? 'border-green-100' : 'border-red-100' }}">
                {{ $branch->status }}
            </span>

            <div class="mt-8 space-y-4 text-left border-t border-slate-200 pt-6">
                <div>
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Email</div>
                    <div class="text-sm text-slate-900 font-medium">{{ $branch->email ?? 'No email provided' }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Phone</div>
                    <div class="text-sm text-slate-900 font-medium">{{ $branch->phone ?? 'No phone provided' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden p-6">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4">Location Details</h3>
            <div class="space-y-4">
                <div>
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 font-serif">Address</div>
                    <div class="text-sm text-slate-900 font-medium leading-relaxed">{{ $branch->address ?? 'N/A' }}</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">City</div>
                        <div class="text-sm text-slate-900 font-medium">{{ $branch->city ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Country</div>
                        <div class="text-sm text-slate-900 font-medium">{{ $branch->country ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Activity --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Current Inventory Items</div>
                <div class="text-2xl font-bold text-slate-900">0</div>
            </div>
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Recent Movements</div>
                <div class="text-2xl font-bold text-slate-800">0</div>
            </div>
        </div>

        {{-- Inventory Preview --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/5 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Branch Stock</h3>
            </div>
            <div class="p-6 text-center">
                <div class="flex flex-col items-center gap-2 py-8">
                    <span class="material-symbols-outlined text-4xl text-slate-300">inventory</span>
                    <div class="text-slate-500 text-sm font-medium italic">No inventory records found for this branch.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
