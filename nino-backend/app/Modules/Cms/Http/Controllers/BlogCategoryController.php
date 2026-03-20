<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('posts')
            ->roots()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'total' => BlogCategory::count(),
            'active' => BlogCategory::where('is_active', true)->count(),
            'child_categories' => BlogCategory::whereNotNull('parent_id')->count(),
            'assigned_posts' => BlogCategory::withCount('posts')->get()->sum('posts_count'),
        ];

        return view('admin.cms.categories.index', compact('categories', 'stats'));
    }

    public function create()
    {
        $parents = BlogCategory::roots()->orderBy('name')->get();
        return view('admin.cms.categories.form', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:blog_categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        BlogCategory::create($validated);
        return redirect()->route('admin.cms.categories.index')->with('success', 'Category created.');
    }

    public function edit(BlogCategory $category)
    {
        $parents = BlogCategory::roots()->where('id', '!=', $category->id)->orderBy('name')->get();
        return view('admin.cms.categories.form', compact('category', 'parents'));
    }

    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:blog_categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $category->update($validated);
        return redirect()->route('admin.cms.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(BlogCategory $category)
    {
        if ($category->posts()->count() > 0) {
            return back()->with('error', 'Cannot delete category with posts.');
        }
        $category->delete();
        return redirect()->route('admin.cms.categories.index')->with('success', 'Category deleted.');
    }
}
