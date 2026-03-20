<?php

namespace App\Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Support\Enums\IssuePriority;
use App\Modules\Support\Enums\IssueStatus;
use App\Modules\Support\Enums\IssueType;
use App\Modules\Support\Models\ActivityTimeline;
use App\Modules\Support\Models\InternalNote;
use App\Modules\Support\Models\SupportIssue;
use Illuminate\Http\Request;

class SupportIssueController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportIssue::with(['order', 'assignee']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('reference', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('customer_email', 'like', "%{$s}%")
            );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $issues = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'open' => SupportIssue::open()->count(),
            'active' => SupportIssue::active()->count(),
            'resolved_today' => SupportIssue::where('status', IssueStatus::RESOLVED)
                ->whereDate('resolved_at', today())->count(),
            'total' => SupportIssue::count(),
        ];

        $staff = \App\Models\User::orderBy('first_name')->get();

        return view('admin.support.issues.index', compact('issues', 'stats', 'staff'));
    }

    public function create()
    {
        $staff = \App\Models\User::orderBy('first_name')->get();
        return view('admin.support.issues.form', [
            'issue' => null,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'priority' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $data['created_by'] = auth()->id();
        $issue = SupportIssue::create($data);

        ActivityTimeline::log($issue, 'created', 'Issue created', auth()->id());

        return redirect()->route('admin.support.issues.index')
            ->with('success', "Issue {$issue->reference} created.");
    }

    public function show(SupportIssue $issue)
    {
        $issue->load(['order', 'assignee', 'creator']);

        $notes = $issue->notes()->with('author')->recent()->get();
        $timeline = $issue->timeline()->with('performer')->recent()->get();

        $staff = \App\Models\User::orderBy('first_name')->get();

        return view('admin.support.issues.show', compact('issue', 'notes', 'timeline', 'staff'));
    }

    public function update(Request $request, SupportIssue $issue)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'priority' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $issue->update($data);

        return back()->with('success', 'Issue updated.');
    }

    // ── Status Actions ─────────────────────────────────────
    public function progress(SupportIssue $issue)
    {
        $issue->markInProgress();
        return back()->with('success', 'Issue marked in progress.');
    }

    public function resolve(SupportIssue $issue)
    {
        $issue->resolve();
        return back()->with('success', 'Issue resolved.');
    }

    public function close(SupportIssue $issue)
    {
        $issue->close();
        return back()->with('success', 'Issue closed.');
    }

    public function reopen(SupportIssue $issue)
    {
        $issue->reopen();
        return back()->with('success', 'Issue reopened.');
    }

    // ── Notes ──────────────────────────────────────────────
    public function addNote(Request $request, SupportIssue $issue)
    {
        $request->validate(['content' => 'required|string']);

        InternalNote::addTo($issue, $request->content, auth()->id());
        ActivityTimeline::log($issue, 'note_added', 'Note added to issue', auth()->id());

        return back()->with('success', 'Note added.');
    }
}
