<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Models\SavedFilter;
use Illuminate\Http\Request;

class SavedFilterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'module' => 'required|string|max:50',
            'filters' => 'required|array',
        ]);

        $data['created_by'] = auth()->id();

        SavedFilter::create($data);

        return back()->with('success', "View \"{$data['name']}\" saved.");
    }

    public function destroy(SavedFilter $filter)
    {
        $filter->delete();
        return back()->with('success', 'Saved view deleted.');
    }
}
