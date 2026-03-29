@extends('admin.layouts.app')

@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.notifications.templates.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Templates</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($template) ? 'Edit: ' . $template->name : 'New Notification Template' }}</h1>
</div>

@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form action="{{ isset($template) ? route('admin.notifications.templates.update', $template) : route('admin.notifications.templates.store') }}" method="POST" class="lg:col-span-2 bg-white border border-slate-200 rounded-md shadow-sm p-6 space-y-5">
        @csrf
        @if(isset($template)) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Event <span class="text-red-600">*</span></label>
                <select name="event" id="event-select" {{ isset($template) ? 'disabled' : '' }} class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <option value="">Select event...</option>
                    @foreach($events as $event)
                        <option value="{{ $event->value }}" data-variables="{{ implode(', ', $event->availableVariables()) }}" {{ (old('event', $template?->event->value ?? '') === $event->value) ? 'selected' : '' }}>
                            {{ $event->label() }}
                        </option>
                    @endforeach
                </select>
                @if(isset($template))
                    <input type="hidden" name="event" value="{{ $template->event->value }}">
                @endif
                @error('event') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Channel <span class="text-red-600">*</span></label>
                <select name="channel" {{ isset($template) ? 'disabled' : '' }} class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    @foreach($channels as $channel)
                        <option value="{{ $channel->value }}" {{ (old('channel', $template?->channel->value ?? '') === $channel->value) ? 'selected' : '' }}>
                            {{ $channel->icon() }} {{ $channel->label() }}
                        </option>
                    @endforeach
                </select>
                @if(isset($template))
                    <input type="hidden" name="channel" value="{{ $template->channel->value }}">
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Template Name <span class="text-red-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $template?->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Order Placed - Email">
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Subject <span class="text-slate-500 text-xs">(Email only)</span></label>
            <input type="text" name="subject" value="{{ old('subject', $template?->subject ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Your order @{{order_reference}} has been confirmed!">
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Message Body <span class="text-red-600">*</span></label>
            <textarea name="body" rows="10" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 font-mono text-xs" placeholder="Hi @{{customer_name}},&#10;&#10;Your order @{{order_reference}} has been placed...">{{ old('body', $template?->body ?? '') }}</textarea>
            <p class="text-[10px] text-slate-500 mt-1">Use <code class="bg-white-dim px-1 rounded">@{{variable_name}}</code> syntax for placeholders.</p>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_enabled" value="1" id="is_enabled" {{ old('is_enabled', $template?->is_enabled ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
            <label for="is_enabled" class="text-sm font-medium text-slate-900">Enabled</label>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">
                {{ isset($template) ? 'Update Template' : 'Create Template' }}
            </button>
        </div>
    </form>

    {{-- Variables Reference Panel --}}
    <div>
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5 sticky top-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Available Variables</h2>
            <p class="text-xs text-slate-500 mb-3">Click to copy. Use <code class="bg-white-dim px-1 rounded text-[10px]">@{{var}}</code> in body.</p>
            <div id="variables-list" class="space-y-1">
                @if(isset($template))
                    @foreach($template->event->availableVariables() as $var)
                        @php $placeholder = '{{' . $var . '}}'; @endphp
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $placeholder }}'); this.innerText='Copied!'; setTimeout(() => this.innerText='{{ $placeholder }}', 1000);" class="block w-full text-left px-2 py-1 text-xs font-mono text-slate-900 bg-slate-100 rounded hover:bg-slate-100 transition-colors">
                            @{{ $var }}
                        </button>
                    @endforeach
                @else
                    <p class="text-xs text-slate-500 italic">Select an event to see available variables.</p>
                @endif
            </div>

            @if(isset($template))
            <div class="mt-4 pt-3 border-t border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Preview</h3>
                <div class="bg-background rounded-lg p-3 text-xs text-slate-900 max-h-60 overflow-y-auto whitespace-pre-wrap">{{ $template->preview() }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('event-select')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const vars = selected.dataset.variables || '';
    const list = document.getElementById('variables-list');
    if (!vars) { list.innerHTML = '<p class="text-xs text-slate-500 italic">No variables.</p>'; return; }
    list.innerHTML = vars.split(', ').map(v => {
        const ph = '{' + '{' + v + '}' + '}';
        return `<button type="button" onclick="navigator.clipboard.writeText('${ph}'); this.innerText='Copied!'; setTimeout(() => this.innerText='${ph}', 1000);" class="block w-full text-left px-2 py-1 text-xs font-mono text-slate-900 bg-slate-100 rounded hover:bg-slate-100 transition-colors">${ph}</button>`;
    }).join('');
});
</script>
@endpush
@endsection
