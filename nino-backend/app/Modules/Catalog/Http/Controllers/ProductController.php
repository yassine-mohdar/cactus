<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['categories', 'featuredImage'])->orderBy('id', 'desc')->paginate(20);
        return view('admin.catalog.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.catalog.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:simple,variable',
            'status' => 'required|in:draft,published,archived',
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'featured_image' => 'nullable|image|max:2048',
        ]);

        $product = new Product($validated);
        // Explicit boolean cast check
        $product->is_featured = $request->boolean('is_featured', false);
        $product->save();
        
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'is_featured' => true,
                'sort_order' => 0,
            ]);
        }

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.catalog.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:simple,variable',
            'status' => 'required|in:draft,published,archived',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'featured_image' => 'nullable|image|max:2048',
        ]);

        $product->fill($validated);
        $product->is_featured = $request->boolean('is_featured', false);
        if (!$request->has('is_featured')) {
            $product->is_featured = false;
        }

        $product->save();
        
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        } else {
            $product->categories()->detach();
        }

        if ($request->hasFile('featured_image')) {
            // Remove old featured image
            if ($oldImage = $product->featuredImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            $path = $request->file('featured_image')->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'is_featured' => true,
                'sort_order' => 0,
            ]);
        }

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete all images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
        
        $product->delete(); 

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product deleted successfully.');
    }
}
