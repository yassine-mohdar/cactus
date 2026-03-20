<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::with('parent')->orderBy('sort_order')->orderBy('name')->paginate(20);
        $summaryBaseQuery = Category::query();

        $summary = [
            'total_categories' => (clone $summaryBaseQuery)->count(),
            'active' => (clone $summaryBaseQuery)->where('is_active', true)->count(),
            'root_categories' => (clone $summaryBaseQuery)->whereNull('parent_id')->count(),
            'child_categories' => (clone $summaryBaseQuery)->whereNotNull('parent_id')->count(),
        ];

        return view('admin.catalog.categories.index', compact('categories', 'summary'));
    }

    public function create()
    {
        $this->authorize('create', Category::class);

        $categories = Category::orderBy('name')->get();
        return view('admin.catalog.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $category = new Category($validated);
        $category->is_active = $request->boolean('is_active', true); // Default to true if not present in request but usually checkbox will be present
        $category->sort_order = $request->input('sort_order', 0);

        // Explicitly handle unchecked checkbox
        if (!$request->has('is_active')) {
             $category->is_active = false;
        }


        if ($request->hasFile('image')) {
            $category->image_path = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        // Prevent setting a category as its own parent
        $categories = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('admin.catalog.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id]), // Cannot be its own parent
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $category->fill($validated);
        $category->is_active = $request->boolean('is_active');
        $category->sort_order = $request->input('sort_order', 0);

        // Explicitly handle unchecked checkbox
        if (!$request->has('is_active')) {
             $category->is_active = false;
        }

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $category->image_path = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        if ($category->children()->count() > 0) {
            return back()->with('error', 'Cannot delete a category that has child categories.');
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Category deleted successfully.');
    }
}
