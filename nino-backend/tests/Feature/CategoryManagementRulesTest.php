<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryManagementRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_category_create_supports_parent_assignment_slug_normalization_and_sort_order(): void
    {
        $manager = $this->createCategoryManager();

        $parent = Category::query()->create([
            'name' => 'Root Catalog',
            'slug' => 'root-catalog',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($manager)->post(route('admin.catalog.categories.store'), [
            'name' => 'Kids & Toys',
            'slug' => 'Kids & Toys',
            'parent_id' => $parent->id,
            'sort_order' => 30,
            'is_active' => '1',
            'description' => 'Children catalog node',
        ]);

        $response->assertRedirect(route('admin.catalog.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Kids & Toys',
            'slug' => 'kids-toys',
            'parent_id' => $parent->id,
            'sort_order' => 30,
            'is_active' => true,
        ]);
    }

    public function test_category_index_orders_rows_by_parent_then_sort_order_then_name(): void
    {
        $manager = $this->createCategoryManager();

        Category::query()->create([
            'name' => 'Late Root',
            'slug' => 'late-root',
            'is_active' => true,
            'sort_order' => 50,
        ]);
        $earlyRoot = Category::query()->create([
            'name' => 'Early Root',
            'slug' => 'early-root',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Category::query()->create([
            'name' => 'Child B',
            'slug' => 'child-b',
            'parent_id' => $earlyRoot->id,
            'is_active' => true,
            'sort_order' => 20,
        ]);
        Category::query()->create([
            'name' => 'Child A',
            'slug' => 'child-a',
            'parent_id' => $earlyRoot->id,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($manager)->get(route('admin.catalog.categories.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Early Root',
            'Late Root',
            'Child A',
            'Child B',
        ]);
    }

    public function test_category_update_rejects_parent_cycle_assignment(): void
    {
        $manager = $this->createCategoryManager();

        $root = Category::query()->create([
            'name' => 'Root',
            'slug' => 'root',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $child = Category::query()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $root->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $grandchild = Category::query()->create([
            'name' => 'Grandchild',
            'slug' => 'grandchild',
            'parent_id' => $child->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($manager)->put(route('admin.catalog.categories.update', $root), [
            'name' => $root->name,
            'slug' => $root->slug,
            'parent_id' => $grandchild->id,
            'sort_order' => 0,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_category_delete_is_blocked_when_category_has_children(): void
    {
        $manager = $this->createCategoryManager();

        $root = Category::query()->create([
            'name' => 'Root',
            'slug' => 'root-to-delete',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        Category::query()->create([
            'name' => 'Child',
            'slug' => 'child-to-delete',
            'parent_id' => $root->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($manager)
            ->from(route('admin.catalog.categories.index'))
            ->delete(route('admin.catalog.categories.destroy', $root));

        $response->assertRedirect(route('admin.catalog.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $root->id]);
    }

    public function test_category_delete_is_blocked_when_assigned_to_products(): void
    {
        $manager = $this->createCategoryManager();

        $category = Category::query()->create([
            'name' => 'Assigned Category',
            'slug' => 'assigned-category',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $product = Product::query()->create([
            'name' => 'Attached Product',
            'slug' => 'attached-product',
            'type' => 'simple',
            'status' => 'published',
        ]);
        $product->categories()->attach($category->id);

        $response = $this->actingAs($manager)
            ->from(route('admin.catalog.categories.index'))
            ->delete(route('admin.catalog.categories.destroy', $category));

        $response->assertRedirect(route('admin.catalog.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_slug_management_generates_unique_slugs_for_duplicate_names(): void
    {
        $manager = $this->createCategoryManager();

        $this->actingAs($manager)->post(route('admin.catalog.categories.store'), [
            'name' => 'Summer Deals',
            'slug' => '',
            'is_active' => '1',
        ])->assertRedirect(route('admin.catalog.categories.index'));

        $this->actingAs($manager)->post(route('admin.catalog.categories.store'), [
            'name' => 'Summer Deals',
            'slug' => '',
            'is_active' => '1',
        ])->assertRedirect(route('admin.catalog.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Summer Deals',
            'slug' => 'summer-deals',
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'Summer Deals',
            'slug' => 'summer-deals-2',
        ]);
    }

    public function test_category_store_persists_image_description_status_and_seo_fields(): void
    {
        Storage::fake('public');

        $manager = $this->createCategoryManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.categories.store'), [
            'name' => 'Editorial Picks',
            'description' => 'Curated assortment for the homepage and campaigns.',
            'meta_title' => 'Editorial Picks | NinoWorld',
            'meta_description' => 'Shop the curated NinoWorld editorial selection.',
            'canonical_url' => 'https://www.ninoworld.com/categories/editorial-picks',
            'og_title' => 'Editorial Picks Social Title',
            'og_description' => 'Editorial social description.',
            'og_image' => 'https://cdn.ninoworld.com/catalog/categories/editorial-picks.jpg',
            'noindex' => '1',
            'sort_order' => 40,
            'is_active' => '0',
            'image' => UploadedFile::fake()->image('editorial-picks.jpg'),
        ]);

        $response->assertRedirect(route('admin.catalog.categories.index'));

        $category = Category::query()->where('name', 'Editorial Picks')->firstOrFail();

        $this->assertSame('Curated assortment for the homepage and campaigns.', $category->description);
        $this->assertSame('Editorial Picks | NinoWorld', $category->meta_title);
        $this->assertSame('Shop the curated NinoWorld editorial selection.', $category->meta_description);
        $this->assertSame('https://www.ninoworld.com/categories/editorial-picks', $category->canonicalUrl());
        $this->assertSame('Editorial Picks Social Title', $category->ogTitle());
        $this->assertSame('Editorial social description.', $category->ogDescription());
        $this->assertSame('https://cdn.ninoworld.com/catalog/categories/editorial-picks.jpg', $category->ogImage());
        $this->assertTrue($category->noindex);
        $this->assertFalse($category->is_active);
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_nino_v2_category_surfaces_render_media_status_and_seo_sections_and_replace_images(): void
    {
        Storage::fake('public');

        $this->activateAdminTheme('nino-v2');

        $manager = $this->createCategoryManager();

        $oldImage = UploadedFile::fake()->image('legacy-category.jpg')->store('categories', 'public');

        $category = Category::query()->create([
            'name' => 'Launches',
            'slug' => 'launches',
            'description' => 'Launch-focused category description.',
            'image_path' => $oldImage,
            'is_active' => true,
            'sort_order' => 20,
            'meta_title' => 'Launches',
            'meta_description' => 'Launch campaign category',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.catalog.categories.edit', $category))
            ->assertOk()
            ->assertSeeText('Primary media')
            ->assertSeeText('Search visibility')
            ->assertSeeText('Active in catalog');

        $this->actingAs($manager)->put(route('admin.catalog.categories.update', $category), [
            'name' => 'Launches',
            'slug' => 'launches',
            'description' => 'Updated launch category description.',
            'meta_title' => 'Launch Category',
            'meta_description' => 'Updated SEO description',
            'canonical_url' => 'https://www.ninoworld.com/categories/launches',
            'og_title' => 'Launch Social Title',
            'og_description' => 'Launch social description',
            'og_image' => 'https://cdn.ninoworld.com/catalog/categories/launches.jpg',
            'noindex' => '1',
            'sort_order' => 25,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('launch-category-new.jpg'),
        ])->assertRedirect(route('admin.catalog.categories.index'));

        $category->refresh();

        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->image_path);
        $this->assertSame('Updated launch category description.', $category->description);
        $this->assertSame('Launch Category', $category->meta_title);
        $this->assertSame('Updated SEO description', $category->meta_description);
        $this->assertSame('https://www.ninoworld.com/categories/launches', $category->canonicalUrl());
        $this->assertSame('Launch Social Title', $category->ogTitle());
        $this->assertSame('Launch social description', $category->ogDescription());
        $this->assertTrue($category->noindex);
        $this->assertTrue($category->is_active);

        $this->actingAs($manager)
            ->get(route('admin.catalog.categories.index'))
            ->assertOk()
            ->assertSeeText('Updated launch category description.')
            ->assertSeeText('SEO Ready')
            ->assertSeeText('Noindex');
    }

    private function createCategoryManager(): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $user->givePermissionTo([
            'categories.viewAny',
            'categories.create',
            'categories.update',
            'categories.delete',
        ]);

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $finder = app('view.finder');
        $finder->flush();

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
