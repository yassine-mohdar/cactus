<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogTagController extends Controller
{
    public function index()
    {
        $tags = BlogTag::withCount('posts')->orderBy('name')->paginate(30);

        $stats = [
            'total' => BlogTag::count(),
            'used' => BlogTag::has('posts')->count(),
            'unused' => BlogTag::doesntHave('posts')->count(),
            'linked_posts' => BlogTag::withCount('posts')->get()->sum('posts_count'),
        ];

        return view('admin.cms.tags.index', compact('tags', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_tags,name',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        BlogTag::create($validated);
        return redirect()->route('admin.cms.tags.index')->with('success', 'Tag created.');
    }

    public function update(Request $request, BlogTag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_tags,name,' . $tag->id,
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $tag->update($validated);
        return redirect()->route('admin.cms.tags.index')->with('success', 'Tag updated.');
    }

    public function destroy(BlogTag $tag)
    {
        $tag->posts()->detach();
        $tag->delete();
        return redirect()->route('admin.cms.tags.index')->with('success', 'Tag deleted.');
    }
}
