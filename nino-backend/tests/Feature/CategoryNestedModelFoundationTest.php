<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryNestedModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_model_supports_nested_tree_relationships_and_paths(): void
    {
        $root = Category::create([
            'name' => 'Root',
            'slug' => 'root',
            'description' => 'Root category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $child = Category::create([
            'parent_id' => $root->id,
            'name' => 'Child',
            'slug' => 'child',
            'description' => 'Child category',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $grandchild = Category::create([
            'parent_id' => $child->id,
            'name' => 'Grandchild',
            'slug' => 'grandchild',
            'description' => 'Grandchild category',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->assertTrue($root->isRoot());
        $this->assertFalse($child->isRoot());
        $this->assertSame([$root->id], $child->ancestors()->pluck('id')->all());
        $this->assertSame([$root->id, $child->id], $grandchild->ancestors()->pluck('id')->all());
        $this->assertSame(0, $root->depth());
        $this->assertSame(2, $grandchild->depth());
        $this->assertSame('Root > Child > Grandchild', $grandchild->treePath());
        $this->assertSame([$child->id, $grandchild->id], $root->descendants()->pluck('id')->all());
        $this->assertSame([$grandchild->id], $child->descendants()->pluck('id')->all());
    }

    public function test_category_root_and_recursive_children_scopes_return_ordered_tree(): void
    {
        $root = Category::create([
            'name' => 'Root',
            'slug' => 'root-sorted',
            'description' => 'Root sorted',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $otherRoot = Category::create([
            'name' => 'Alpha Root',
            'slug' => 'alpha-root',
            'description' => 'Alpha root',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $childB = Category::create([
            'parent_id' => $root->id,
            'name' => 'Beta Child',
            'slug' => 'beta-child',
            'description' => 'Beta child',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $childA = Category::create([
            'parent_id' => $root->id,
            'name' => 'Alpha Child',
            'slug' => 'alpha-child',
            'description' => 'Alpha child',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $roots = Category::query()->roots()->ordered()->pluck('id')->all();
        $recursiveChildren = $root->fresh()->childrenRecursive()->get()->pluck('id')->all();

        $this->assertSame([$otherRoot->id, $root->id], $roots);
        $this->assertSame([$childA->id, $childB->id], $recursiveChildren);
    }
}
