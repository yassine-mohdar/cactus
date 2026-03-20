@extends('admin.layouts.app')

@section('title', 'URL Redirects')

@section('header')
    <x-nino.page-header
        title="URL Redirects"
        subtitle="Maintain SEO-safe path changes and route broken links with a single-screen redirect workspace." />
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Redirect updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Redirects</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">301 Permanent</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['permanent']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Hits</p>
            <p class="stat-value text-[#61706B]">{{ number_format($stats['hits']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <div class="space-y-4">
            <x-nino.tab-strip label="Editorial workspace">
                <a href="{{ route('admin.cms.posts.index') }}" class="tab-pill">Posts</a>
                <a href="{{ route('admin.cms.categories.index') }}" class="tab-pill">Categories</a>
                <a href="{{ route('admin.cms.redirects.index') }}" class="tab-pill tab-pill-active">Redirects</a>
                <a href="{{ route('admin.cms.tags.index') }}" class="tab-pill">Tags</a>
            </x-nino.tab-strip>

            <form method="GET">
                <div class="filter-grid xl:grid-cols-4">
                    <div class="filter-field xl:col-span-3">
                        <label class="filter-label" for="redirect-search">Search</label>
                        <input id="redirect-search" type="text" name="search" value="{{ request('search') }}" placeholder="Source or target path..." class="input-field">
                    </div>
                    <div class="filter-field xl:items-end">
                        <div class="flex flex-wrap gap-3">
                            <x-nino.button type="submit" variant="primary">Search</x-nino.button>
                            <x-nino.button href="{{ route('admin.cms.redirects.index') }}" variant="outline">Reset</x-nino.button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <x-nino.entity-form-section title="New Redirect" subtitle="Add a redirect rule without leaving the main redirect workspace.">
            <form method="POST" action="{{ route('admin.cms.redirects.store') }}">
                @csrf
                <div class="entity-section-grid xl:grid-cols-5">
                    <div>
                        <label class="filter-label" for="redirect-source">Source Path</label>
                        <input id="redirect-source" type="text" name="source_path" required class="input-field font-mono" placeholder="old-page/url">
                    </div>
                    <div>
                        <label class="filter-label" for="redirect-target">Target Path</label>
                        <input id="redirect-target" type="text" name="target_path" required class="input-field font-mono" placeholder="new-page/url">
                    </div>
                    <div>
                        <label class="filter-label" for="redirect-status-code">Type</label>
                        <select id="redirect-status-code" name="status_code" class="input-field">
                            <option value="301">301 Permanent</option>
                            <option value="302">302 Temporary</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label for="redirect-active" class="toggle-row">
                            <input type="checkbox" id="redirect-active" name="is_active" value="1" checked class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                            Active
                        </label>
                    </div>
                    <div class="flex items-end justify-end">
                        <x-nino.button type="submit" variant="primary">Add Redirect</x-nino.button>
                    </div>
                </div>
            </form>
        </x-nino.entity-form-section>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Redirect Rules</h2>
                    <p class="datatable-subtitle">Manage active and historical redirect mappings without leaving the index view.</p>
                </div>
                <span class="datatable-meta">{{ number_format($redirects->total()) }} rules</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Source</th>
                    <th>Target</th>
                    <th>Code</th>
                    <th>Hits</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($redirects as $redirect)
                        <tr>
                            <td class="font-mono text-xs text-[#1E2B27]">/{{ $redirect->source_path }}</td>
                            <td class="font-mono text-xs text-[#1E2B27]">/{{ $redirect->target_path }}</td>
                            <td>
                                <x-nino.status-badge :tone="$redirect->status_code === 301 ? 'info' : 'warning'" size="sm">{{ $redirect->status_code }}</x-nino.status-badge>
                            </td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ number_format((int) $redirect->hit_count) }}</td>
                            <td>
                                <x-nino.status-badge :tone="$redirect->is_active ? 'success' : 'neutral'" size="sm">
                                    {{ $redirect->is_active ? 'Active' : 'Disabled' }}
                                </x-nino.status-badge>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <form action="{{ route('admin.cms.redirects.destroy', $redirect) }}" method="POST" class="inline" onsubmit="return confirm('Delete this redirect?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No redirects created yet"
                                    description="Add the first redirect rule to handle renamed content or legacy inbound URLs."
                                    icon="link" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>

            <div class="datatable-footer">
                {{ $redirects->links() }}
            </div>
        </div>
    </div>
@endsection
