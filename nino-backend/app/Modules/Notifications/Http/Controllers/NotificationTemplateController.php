<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::withCount('logs')->orderBy('event')->orderBy('channel')->get();

        $grouped = $templates->groupBy(fn($t) => $t->event->group());

        $stats = [
            'total' => $templates->count(),
            'enabled' => $templates->where('is_enabled', true)->count(),
            'disabled' => $templates->where('is_enabled', false)->count(),
            'events_covered' => $templates->groupBy(fn ($template) => $template->event->value)->count(),
        ];

        $groupStats = $grouped->map(fn ($templates) => [
            'count' => $templates->count(),
            'enabled' => $templates->where('is_enabled', true)->count(),
        ]);

        return view('admin.notifications.templates.index', compact('templates', 'grouped', 'stats', 'groupStats'));
    }

    public function create()
    {
        $events = NotificationEvent::cases();
        $channels = NotificationChannel::cases();
        return view('admin.notifications.templates.form', compact('events', 'channels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event' => 'required|string',
            'channel' => 'required|string',
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_enabled' => 'nullable|boolean',
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');

        // Check uniqueness
        if (NotificationTemplate::where('event', $validated['event'])->where('channel', $validated['channel'])->exists()) {
            return back()->withInput()->with('error', 'A template for this event and channel already exists.');
        }

        NotificationTemplate::create($validated);

        return redirect()->route('admin.notifications.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(NotificationTemplate $template)
    {
        $events = NotificationEvent::cases();
        $channels = NotificationChannel::cases();
        return view('admin.notifications.templates.form', compact('template', 'events', 'channels'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_enabled' => 'nullable|boolean',
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');
        $template->update($validated);

        return redirect()->route('admin.notifications.templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(NotificationTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.notifications.templates.index')
            ->with('success', 'Template deleted.');
    }

    /**
     * Preview a template with sample data.
     */
    public function preview(NotificationTemplate $template)
    {
        $preview = $template->preview();
        $subjectPreview = $template->renderSubject(
            collect($template->event->availableVariables())
                ->mapWithKeys(fn($v) => [$v => '[[' . strtoupper($v) . ']]'])
                ->toArray()
        );

        return response()->json([
            'subject' => $subjectPreview,
            'body' => $preview,
            'variables' => $template->event->availableVariables(),
        ]);
    }

    /**
     * Toggle enabled/disabled status.
     */
    public function toggle(NotificationTemplate $template)
    {
        $template->update(['is_enabled' => !$template->is_enabled]);
        return back()->with('success', $template->is_enabled ? 'Template enabled.' : 'Template disabled.');
    }
}
