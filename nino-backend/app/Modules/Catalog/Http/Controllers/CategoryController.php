<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->ordered()
            ->paginate(20);
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

        $parentOptions = $this->buildParentOptions();

        return view('admin.catalog.categories.create', compact('parentOptions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url|max:2048',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:2048',
            'noindex' => 'nullable|boolean',
        ]);

        $category = new Category($validated);
        $category->is_active = $request->boolean('is_active', true);
        $category->noindex = $request->boolean('noindex', false);
        $category->sort_order = $request->filled('sort_order')
            ? (int) $request->input('sort_order')
            : $this->nextSortOrderForParent($category->parent_id);


        if ($request->hasFile('image')) {
            $category->image_path = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        $parentOptions = $this->buildParentOptions($category);

        return view('admin.catalog.categories.edit', compact('category', 'parentOptions'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $descendantIds = $category->descendants()->pluck('id')->all();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id]), // Cannot be its own parent
                Rule::notIn($descendantIds), // Cannot be nested under its own descendants
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url|max:2048',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:2048',
            'noindex' => 'nullable|boolean',
        ]);

        $previousParentId = $category->parent_id;

        $category->fill($validated);
        $category->is_active = $request->boolean('is_active', false);
        $category->noindex = $request->boolean('noindex', false);
        $category->sort_order = $request->filled('sort_order')
            ? (int) $request->input('sort_order')
            : ($previousParentId !== $category->parent_id
                ? $this->nextSortOrderForParent($category->parent_id, $category->id)
                : $category->sort_order);

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

        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete a category that has child categories.');
        }

        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category assigned to products.');
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Category deleted successfully.');
    }

    /**
     * @return Collection<int, array{id:int,label:string}>
     */
    private function buildParentOptions(?Category $editing = null): Collection
    {
        $blockedIds = collect();

        if ($editing !== null) {
            $blockedIds = $editing->descendants()
                ->pluck('id')
                ->push($editing->id)
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        $roots = Category::query()
            ->roots()
            ->ordered()
            ->with('childrenRecursive')
            ->get();

        $options = collect();

        foreach ($roots as $root) {
            $this->appendCategoryOption($options, $root, $blockedIds, 0);
        }

        return $options;
    }

    /**
     * @param  Collection<int, array{id:int,label:string}>  $options
     * @param  Collection<int, int>  $blockedIds
     */
    private function appendCategoryOption(Collection $options, Category $category, Collection $blockedIds, int $depth): void
    {
        if (! $blockedIds->contains((int) $category->id)) {
            $options->push([
                'id' => (int) $category->id,
                'label' => str_repeat('— ', $depth).$category->name,
            ]);
        }

        foreach ($category->childrenRecursive as $child) {
            $this->appendCategoryOption($options, $child, $blockedIds, $depth + 1);
        }
    }

    private function nextSortOrderForParent(?int $parentId, ?int $exceptCategoryId = null): int
    {
        $maxSortOrder = Category::query()
            ->where('parent_id', $parentId)
            ->when($exceptCategoryId !== null, fn ($query) => $query->whereKeyNot($exceptCategoryId))
            ->max('sort_order');

        return (int) $maxSortOrder + 10;
    }
}
