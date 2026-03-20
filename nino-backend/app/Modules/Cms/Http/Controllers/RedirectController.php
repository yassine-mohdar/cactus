<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index(Request $request)
    {
        $query = Redirect::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('source_path', 'like', "%{$s}%")->orWhere('target_path', 'like', "%{$s}%"));
        }

        $redirects = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $stats = [
            'total' => Redirect::count(),
            'active' => Redirect::where('is_active', true)->count(),
            'permanent' => Redirect::where('status_code', 301)->count(),
            'hits' => Redirect::sum('hit_count'),
        ];

        return view('admin.cms.redirects.index', compact('redirects', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_path' => 'required|string|max:500|unique:redirects,source_path',
            'target_path' => 'required|string|max:500',
            'status_code' => 'required|in:301,302',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['source_path'] = ltrim($validated['source_path'], '/');
        $validated['target_path'] = ltrim($validated['target_path'], '/');
        $validated['is_active'] = $request->has('is_active');

        Redirect::create($validated);
        return redirect()->route('admin.cms.redirects.index')->with('success', 'Redirect created.');
    }

    public function update(Request $request, Redirect $redirect)
    {
        $validated = $request->validate([
            'source_path' => 'required|string|max:500|unique:redirects,source_path,' . $redirect->id,
            'target_path' => 'required|string|max:500',
            'status_code' => 'required|in:301,302',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['source_path'] = ltrim($validated['source_path'], '/');
        $validated['target_path'] = ltrim($validated['target_path'], '/');
        $validated['is_active'] = $request->has('is_active');

        $redirect->update($validated);
        return redirect()->route('admin.cms.redirects.index')->with('success', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();
        return redirect()->route('admin.cms.redirects.index')->with('success', 'Redirect deleted.');
    }
}
