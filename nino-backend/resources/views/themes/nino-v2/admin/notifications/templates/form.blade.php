@extends('admin.layouts.app')

@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('header')
    <x-nino.page-header
        title="{{ isset($template) ? 'Edit '.$template->name : 'New Notification Template' }}"
        subtitle="Define subject, body, variables, and enablement rules without changing any delivery contracts.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.notifications.templates.index') }}" variant="secondary" icon="arrow_back">Back to Templates</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if($errors->any())
        <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
            {{ collect($errors->all())->join(' ') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Template action failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <form action="{{ isset($template) ? route('admin.notifications.templates.update', $template) : route('admin.notifications.templates.store') }}" method="POST" class="form-layout">
        @csrf
        @if(isset($template))
            @method('PUT')
        @endif

        <div class="form-main">
            <x-nino.entity-form-section title="Template Identity" subtitle="Bind this template to one event and one outbound channel.">
                <div class="entity-section-grid">
                    <div>
                        <label class="filter-label" for="event-select">Event</label>
                        <select name="event" id="event-select" {{ isset($template) ? 'disabled' : '' }} class="input-field">
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
                    </div>
                    <div>
                        <label class="filter-label" for="template-channel">Channel</label>
                        <select id="template-channel" name="channel" {{ isset($template) ? 'disabled' : '' }} class="input-field">
                            @foreach($channels as $channel)
                                <option value="{{ $channel->value }}" {{ (old('channel', $template?->channel->value ?? '') === $channel->value) ? 'selected' : '' }}>
                                    {{ $channel->label() }}
                                </option>
                            @endforeach
                        </select>
                        @if(isset($template))
                            <input type="hidden" name="channel" value="{{ $template->channel->value }}">
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="filter-label" for="template-name">Template Name</label>
                        <input id="template-name" type="text" name="name" value="{{ old('name', $template?->name ?? '') }}" required class="input-field" placeholder="Order Placed - Email">
                    </div>
                </div>
            </x-nino.entity-form-section>

            <x-nino.entity-form-section title="Message Content" subtitle="Write the rendered subject and body used by the outbound channel.">
                <div class="space-y-4">
                    <div>
                        <label class="filter-label" for="template-subject">Subject</label>
                        <input id="template-subject" type="text" name="subject" value="{{ old('subject', $template?->subject ?? '') }}" class="input-field" placeholder="Your order {{order_reference}} has been confirmed">
                    </div>
                    <div>
                        <label class="filter-label" for="template-body">Message Body</label>
                        <textarea id="template-body" name="body" rows="14" required class="input-field font-mono text-xs" placeholder="Hi {{customer_name}},&#10;&#10;Your order {{order_reference}} has been placed...">{{ old('body', $template?->body ?? '') }}</textarea>
                        <p class="mt-2 text-xs text-[#61706B]">Use <code class="rounded bg-[#F6F2EC] px-1 py-0.5 font-mono">{{ '{' }}{{ '{' }}variable{{ '}' }}{{ '}' }}</code> syntax for placeholders.</p>
                    </div>
                </div>
            </x-nino.entity-form-section>
        </div>

        <div class="form-sidebar">
            <x-nino.entity-form-section title="Template Controls" subtitle="Enable or disable the template and review available variables while writing.">
                <div class="space-y-4">
                    <label for="template-enabled" class="inline-flex items-center gap-3 rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-4 py-3 text-sm font-semibold text-[#17302A]">
                        <input type="checkbox" id="template-enabled" name="is_enabled" value="1" {{ old('is_enabled', $template?->is_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                        Template is enabled for live dispatch
                    </label>

                    <div>
                        <p class="filter-label">Available Variables</p>
                        <div id="variables-list" class="mt-3 space-y-2">
                            @if(isset($template))
                                @foreach($template->event->availableVariables() as $var)
                                    @php $placeholder = '{{' . $var . '}}'; @endphp
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $placeholder }}'); this.innerText='Copied'; setTimeout(() => this.innerText='{{ $placeholder }}', 1000);" class="flex w-full items-center justify-between rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-2 text-left font-mono text-xs text-[#1E2B27] transition hover:bg-[#FCFBF8]">
                                        <span>{{ $placeholder }}</span>
                                        <span class="text-[10px] uppercase tracking-[0.18em] text-[#61706B]">Copy</span>
                                    </button>
                                @endforeach
                            @else
                                <p class="text-sm text-[#61706B]">Select an event to view and copy its available placeholders.</p>
                            @endif
                        </div>
                    </div>

                    @if(isset($template))
                        <div>
                            <p class="filter-label">Rendered Preview</p>
                            <div class="mt-3 rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-4 py-4 font-mono text-xs leading-6 text-[#1E2B27] whitespace-pre-wrap">
                                {{ $template->preview() }}
                            </div>
                        </div>
                    @endif
                </div>

                <x-slot:footer>
                    <div class="form-actions">
                        <x-nino.button href="{{ route('admin.notifications.templates.index') }}" variant="outline">Cancel</x-nino.button>
                        <x-nino.button type="submit" variant="primary">{{ isset($template) ? 'Update Template' : 'Create Template' }}</x-nino.button>
                    </div>
                </x-slot:footer>
            </x-nino.entity-form-section>
        </div>
    </form>

    @push('scripts')
    <script>
    document.getElementById('event-select')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const vars = selected.dataset.variables || '';
        const list = document.getElementById('variables-list');

        if (!vars) {
            list.innerHTML = '<p class="text-sm text-[#61706B]">No variables available for this event.</p>';
            return;
        }

        list.innerHTML = vars.split(', ').map((variable) => {
            const placeholder = `{{${variable}}}`;
            return `<button type="button" onclick="navigator.clipboard.writeText('${placeholder}'); this.querySelector('[data-copy-label]').innerText='Copied'; setTimeout(() => this.querySelector('[data-copy-label]').innerText='Copy', 1000);" class="flex w-full items-center justify-between rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-2 text-left font-mono text-xs text-[#1E2B27] transition hover:bg-[#FCFBF8]"><span>${placeholder}</span><span data-copy-label class="text-[10px] uppercase tracking-[0.18em] text-[#61706B]">Copy</span></button>`;
        }).join('');
    });
    </script>
    @endpush
@endsection
