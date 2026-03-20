<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Services\NotificationDispatcher;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationLog::with('template');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Channel filter
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Event filter
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Search (recipient, order ref, external ID)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('recipient', 'like', "%{$s}%")
                    ->orWhere('order_reference', 'like', "%{$s}%")
                    ->orWhere('external_id', 'like', "%{$s}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = [
            'total' => NotificationLog::count(),
            'sent' => NotificationLog::where('status', NotificationStatus::SENT)->count(),
            'failed' => NotificationLog::where('status', NotificationStatus::FAILED)->count(),
            'queued' => NotificationLog::whereIn('status', [NotificationStatus::QUEUED, NotificationStatus::SENDING, NotificationStatus::RETRYING])->count(),
            'retryable' => NotificationLog::retryable()->count(),
            'last_24h' => NotificationLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        $channelCounts = NotificationLog::query()
            ->selectRaw('channel, COUNT(*) as aggregate')
            ->groupBy('channel')
            ->pluck('aggregate', 'channel');

        $channelBreakdown = collect(NotificationChannel::cases())
            ->map(fn (NotificationChannel $channel) => [
                'channel' => $channel,
                'count' => (int) ($channelCounts[$channel->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        $eventCounts = NotificationLog::query()
            ->selectRaw('event, COUNT(*) as aggregate')
            ->groupBy('event')
            ->pluck('aggregate', 'event');

        $eventBreakdown = collect(NotificationEvent::cases())
            ->map(fn (NotificationEvent $event) => [
                'event' => $event,
                'count' => (int) ($eventCounts[$event->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->take(6)
            ->values();

        return view('admin.notifications.logs.index', compact('logs', 'stats', 'channelBreakdown', 'eventBreakdown'));
    }

    public function show(NotificationLog $log)
    {
        $log->load('template');
        return view('admin.notifications.logs.show', compact('log'));
    }

    /**
     * Retry a failed notification.
     */
    public function retry(NotificationLog $log)
    {
        try {
            app(NotificationDispatcher::class)->retry($log);
            return back()->with('success', 'Notification queued for retry.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
