<?php

namespace App\Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Support\Models\InternalNote;
use App\Modules\Support\Models\ActivityTimeline;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.any:support.manage_tickets');
    }

    /**
     * Add a note to any notable entity (order, customer, etc.).
     */
    public function store(Request $request)
    {
        $request->validate([
            'notable_type' => 'required|string',
            'notable_id' => 'required|integer',
            'content' => 'required|string',
        ]);

        $modelClass = $request->notable_type;
        $model = $modelClass::findOrFail($request->notable_id);

        $note = InternalNote::addTo($model, $request->content, auth()->id());

        ActivityTimeline::log($model, 'note_added', 'Internal note added', auth()->id());

        return back()->with('success', 'Note added.');
    }

    public function togglePin(InternalNote $note)
    {
        $note->togglePin();
        return back()->with('success', $note->is_pinned ? 'Note pinned.' : 'Note unpinned.');
    }

    public function destroy(InternalNote $note)
    {
        $note->delete();
        return back()->with('success', 'Note deleted.');
    }
}
