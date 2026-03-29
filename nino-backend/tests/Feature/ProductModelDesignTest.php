<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductTag;
use App\Modules\Settings\Services\SettingsService;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_product_model_supports_simple_and_variable_types(): void
    {
        $simple = Product::factory()->simple()->create();
        $variable = Product::factory()->variable()->create();

        $this->assertTrue($simple->isSimple());
        $this->assertFalse($simple->isVariable());
        $this->assertTrue($variable->isVariable());
        $this->assertFalse($variable->isSimple());
        $this->assertSame([$simple->id], Product::query()->simple()->pluck('id')->all());
        $this->assertSame([$variable->id], Product::query()->variable()->pluck('id')->all());
    }

    public function test_product_model_supports_draft_published_and_archived_statuses(): void
    {
        $draft = Product::factory()->create(['status' => Product::STATUS_DRAFT]);
        $published = Product::factory()->published()->create();
        $archived = Product::factory()->archived()->create();

        $this->assertTrue($draft->isDraft());
        $this->assertTrue($published->isPublished());
        $this->assertTrue($archived->isArchived());
        $this->assertSame('Draft', $draft->statusLabel());
        $this->assertSame('Published', $published->statusLabel());
        $this->assertSame('Archived', $archived->statusLabel());
        $this->assertSame([$draft->id], Product::query()->draft()->pluck('id')->all());
        $this->assertSame([$published->id], Product::query()->published()->pluck('id')->all());
        $this->assertSame([$archived->id], Product::query()->archived()->pluck('id')->all());
    }

    public function test_product_slug_management_generates_unique_slugs_for_duplicate_names(): void
    {
        $first = Product::factory()->create([
            'name' => 'Starter Plush',
            'slug' => null,
        ]);
        $second = Product::factory()->create([
            'name' => 'Starter Plush',
            'slug' => null,
            'sku' => 'SKU-DUP-0002',
        ]);

        $this->assertSame('starter-plush', $first->slug);
        $this->assertSame('starter-plush-2', $second->slug);
    }

    public function test_product_store_persists_simple_product_sku_and_slug(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Simple Plush',
            'type' => 'simple',
            'status' => 'draft',
            'sku' => 'PLUSH-001',
            'slug' => 'Simple Plush',
            'price' => '99.90',
            'quantity' => 12,
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Simple Plush',
            'type' => 'simple',
            'sku' => 'PLUSH-001',
            'slug' => 'simple-plush',
        ]);
    }

    public function test_product_store_persists_variable_product_support_without_base_price(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Variable Hoodie',
            'type' => 'variable',
            'status' => 'draft',
            'sku' => 'HOODIE-VAR-01',
            'slug' => '',
            'short_description' => 'Variant driven hoodie',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()->where('name', 'Variable Hoodie')->firstOrFail();

        $this->assertSame('variable', $product->type);
        $this->assertSame('HOODIE-VAR-01', $product->sku);
        $this->assertSame('variable-hoodie', $product->slug);
        $this->assertNull($product->price);
    }

    public function test_product_store_syncs_category_relationships_and_tags(): void
    {
        $manager = $this->createProductManager();
        $primaryCategory = Category::factory()->create(['name' => 'Plush']);
        $secondaryCategory = Category::factory()->create(['name' => 'Limited']);

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Tagged Plush',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'TAGGED-001',
            'slug' => 'tagged-plush',
            'price' => '149.90',
            'quantity' => 7,
            'categories' => [$primaryCategory->id, $secondaryCategory->id],
            'tags' => 'giftable, limited edition, giftable',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()
            ->with(['categories', 'tags'])
            ->where('sku', 'TAGGED-001')
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$primaryCategory->id, $secondaryCategory->id],
            $product->categories->pluck('id')->all()
        );
        $this->assertSame(
            ['giftable', 'limited-edition'],
            $product->tags->pluck('slug')->all()
        );
        $this->assertDatabaseCount('product_tags', 2);
    }

    public function test_product_store_persists_dimensions_featured_flag_and_sale_pricing(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Premium Carrier Box',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'BOX-001',
            'price' => '249.90',
            'sale_price' => '199.90',
            'quantity' => 5,
            'weight' => '1.25',
            'length' => '30',
            'width' => '20',
            'height' => '10',
            'is_featured' => '1',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()->where('sku', 'BOX-001')->firstOrFail();

        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->hasSalePrice());
        $this->assertSame(199.90, $product->effectivePrice());
        $this->assertTrue($product->hasPhysicalProfile());
        $this->assertSame('30 x 20 x 10 cm', $product->dimensionsSummary());

        $this->assertDatabaseHas('products', [
            'sku' => 'BOX-001',
            'price' => '249.90',
            'sale_price' => '199.90',
            'weight' => '1.25',
            'length' => '30.00',
            'width' => '20.00',
            'height' => '10.00',
            'is_featured' => true,
        ]);
    }

    public function test_product_store_persists_internal_cost_and_margin_support(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Margin Plush',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'MARGIN-001',
            'price' => '120.00',
            'sale_price' => '100.00',
            'cost_price' => '40.00',
            'quantity' => 3,
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()->where('sku', 'MARGIN-001')->firstOrFail();

        $this->assertTrue($product->hasCostPrice());
        $this->assertSame(60.0, $product->marginAmount());
        $this->assertSame(60.0, $product->marginPercent());
        $this->assertDatabaseHas('products', [
            'sku' => 'MARGIN-001',
            'cost_price' => '40.00',
        ]);
    }

    public function test_product_store_persists_full_seo_payload_and_fallback_helpers(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'SEO Plush',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'SEO-001',
            'slug' => 'seo-plush',
            'price' => '89.90',
            'meta_title' => 'SEO Plush Meta Title',
            'meta_description' => 'SEO Plush meta description used for search previews.',
            'canonical_url' => 'https://www.ninoworld.com/products/seo-plush',
            'og_title' => 'SEO Plush Social Title',
            'og_description' => 'SEO Plush social description.',
            'og_image' => 'https://cdn.ninoworld.com/catalog/seo-plush-og.jpg',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()->where('sku', 'SEO-001')->firstOrFail();

        $this->assertSame('SEO Plush Meta Title', $product->seoTitle());
        $this->assertSame('SEO Plush meta description used for search previews.', $product->seoDescription());
        $this->assertSame('https://www.ninoworld.com/products/seo-plush', $product->canonicalUrl());
        $this->assertSame('SEO Plush Social Title', $product->ogTitle());
        $this->assertSame('SEO Plush social description.', $product->ogDescription());
        $this->assertSame('https://cdn.ninoworld.com/catalog/seo-plush-og.jpg', $product->ogImage());

        $this->assertDatabaseHas('products', [
            'sku' => 'SEO-001',
            'meta_title' => 'SEO Plush Meta Title',
            'canonical_url' => 'https://www.ninoworld.com/products/seo-plush',
            'og_title' => 'SEO Plush Social Title',
            'og_image' => 'https://cdn.ninoworld.com/catalog/seo-plush-og.jpg',
        ]);
    }

    public function test_product_seo_helpers_fall_back_to_core_product_content(): void
    {
        config(['app.url' => 'https://app.ninoworld.test']);

        $product = Product::factory()->create([
            'name' => 'Fallback SEO Product',
            'slug' => 'fallback-seo-product',
            'short_description' => 'Fallback summary for search previews.',
            'description' => '<p>Fallback long description.</p>',
            'meta_title' => null,
            'meta_description' => null,
            'canonical_url' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
        ]);

        $this->assertSame('Fallback SEO Product', $product->seoTitle());
        $this->assertSame('Fallback summary for search previews.', $product->seoDescription());
        $this->assertSame('https://app.ninoworld.test/products/fallback-seo-product', $product->canonicalUrl());
        $this->assertSame('Fallback SEO Product', $product->ogTitle());
        $this->assertSame('Fallback summary for search previews.', $product->ogDescription());
        $this->assertNull($product->ogImage());
    }

    public function test_product_store_supports_noindex_flag(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Noindex Product',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'NOINDEX-001',
            'price' => '59.90',
            'noindex' => '1',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'NOINDEX-001',
            'noindex' => true,
        ]);
    }

    public function test_product_index_supports_search_and_operational_filters(): void
    {
        $manager = $this->createProductManager();
        $apparel = Category::factory()->create(['name' => 'Apparel']);
        $toys = Category::factory()->create(['name' => 'Toys']);

        $draftLow = Product::factory()->create([
            'name' => 'Alpha Jacket',
            'sku' => 'ALPHA-001',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_DRAFT,
            'quantity' => 4,
        ]);
        $draftLow->categories()->sync([$apparel->id]);

        $publishedOut = Product::factory()->create([
            'name' => 'Beta Plush',
            'sku' => 'BETA-002',
            'type' => Product::TYPE_VARIABLE,
            'status' => Product::STATUS_PUBLISHED,
            'quantity' => 0,
        ]);
        $publishedOut->categories()->sync([$toys->id]);

        Product::factory()->create([
            'name' => 'Gamma Mug',
            'sku' => 'GAMMA-003',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_ARCHIVED,
            'quantity' => 18,
        ])->categories()->sync([$apparel->id]);

        $response = $this->actingAs($manager)->get(route('admin.catalog.products.index', [
            'search' => 'Alpha',
            'status' => Product::STATUS_DRAFT,
            'category' => $apparel->id,
            'type' => Product::TYPE_SIMPLE,
            'stock' => 'low',
        ]));

        $response->assertOk();
        $response->assertSeeText('Apply Filters');
        $response->assertSeeText('Alpha Jacket');
        $response->assertDontSeeText('Beta Plush');
        $response->assertDontSeeText('Gamma Mug');
    }

    public function test_product_description_supports_short_full_and_safe_rich_content_rendering(): void
    {
        $product = Product::factory()->create([
            'short_description' => 'Portable plush for fast merchandising cards.',
            'description' => "<p><strong>Soft plush</strong> body.</p><ul><li>Gift-ready</li></ul><script>alert('x')</script>",
        ]);

        $this->assertSame('Portable plush for fast merchandising cards.', $product->excerpt());

        $rendered = $product->renderedDescriptionHtml()->toHtml();

        $this->assertStringContainsString('<strong>Soft plush</strong>', $rendered);
        $this->assertStringContainsString('<ul><li>Gift-ready</li></ul>', $rendered);
        $this->assertStringNotContainsString('<script>', $rendered);
    }

    public function test_sale_price_must_not_exceed_regular_price(): void
    {
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->from(route('admin.catalog.products.create'))
            ->post(route('admin.catalog.products.store'), [
                'name' => 'Broken Discount',
                'type' => Product::TYPE_SIMPLE,
                'status' => Product::STATUS_DRAFT,
                'sku' => 'BROKEN-001',
                'price' => '99.90',
                'sale_price' => '129.90',
            ]);

        $response->assertRedirect(route('admin.catalog.products.create'));
        $response->assertSessionHasErrors(['sale_price']);
        $this->assertDatabaseMissing('products', [
            'sku' => 'BROKEN-001',
        ]);
    }

    public function test_nino_v2_product_surfaces_render_status_category_tag_and_pricing_controls(): void
    {
        $this->activateAdminTheme('nino-v2');
        app(SettingsService::class)->set('finance', 'base_currency', 'EUR', 'string');
        app(SettingsService::class)->set('finance', 'multi_currency_enabled', true, 'boolean');
        app(SettingsService::class)->set('finance', 'supported_currencies', 'EUR, MAD, USD', 'string');

        $manager = $this->createProductManager();

        $createResponse = $this->actingAs($manager)->get(route('admin.catalog.products.create'));
        $createResponse->assertOk();
        $createResponse->assertSeeText('Product type');
        $createResponse->assertSeeText('Slug');
        $createResponse->assertSeeText('Simple product');
        $createResponse->assertSeeText('Variable product');
        $createResponse->assertSeeText('Draft');
        $createResponse->assertSeeText('Published');
        $createResponse->assertSeeText('Archived');
        $createResponse->assertSeeText('Tags');
        $createResponse->assertSeeText('Regular price');
        $createResponse->assertSeeText('Sale price');
        $createResponse->assertSeeText('Cost price');
        $createResponse->assertSeeText('Weight (kg)');
        $createResponse->assertSeeText('Feature this product');
        $createResponse->assertSeeText('Canonical URL');
        $createResponse->assertSeeText('OG title');
        $createResponse->assertSeeText('OG image URL');
        $createResponse->assertSeeText('Noindex this product');
        $createResponse->assertSeeText('Base currency for catalog pricing is EUR');
        $createResponse->assertSeeText('Full description preview');

        $tag = ProductTag::query()->create([
            'name' => 'Giftable',
            'slug' => 'giftable',
        ]);

        $category = Category::factory()->create([
            'name' => 'Accessories',
        ]);

        $product = Product::factory()->variable()->featured()->create([
            'name' => 'V2 Index Product',
            'slug' => 'v2-index-product',
            'sku' => 'V2-INDEX-01',
            'status' => Product::STATUS_ARCHIVED,
            'weight' => 0.75,
            'length' => 25,
            'width' => 15,
            'height' => 5,
            'price' => 299.90,
            'sale_price' => 249.90,
            'cost_price' => 120.00,
            'short_description' => 'Operational preview text.',
            'description' => '<p><strong>Rich content</strong> block.</p>',
            'meta_title' => 'V2 Product SEO Title',
            'meta_description' => 'V2 product SEO description.',
            'canonical_url' => 'https://www.ninoworld.com/products/v2-index-product',
            'og_title' => 'V2 Social Title',
        ]);
        $product->categories()->sync([$category->id]);
        $product->tags()->sync([$tag->id]);

        $indexResponse = $this->actingAs($manager)->get(route('admin.catalog.products.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSeeText('V2 Index Product');
        $indexResponse->assertSeeText('V2-INDEX-01');
        $indexResponse->assertSeeText('v2-index-product');
        $indexResponse->assertSeeText('Variable');
        $indexResponse->assertSeeText('Archived');
        $indexResponse->assertSeeText('Accessories');
        $indexResponse->assertSeeText('#Giftable');
        $indexResponse->assertSeeText('Featured');
        $indexResponse->assertSeeText('249.90 EUR');
        $indexResponse->assertSeeText('299.90 EUR');
        $indexResponse->assertSeeText('Cost 120.00 EUR');
        $indexResponse->assertSeeText('Margin 51.98%');
        $indexResponse->assertSeeText('0.75 kg');
        $indexResponse->assertSeeText('25 x 15 x 5 cm');
        $indexResponse->assertSeeText('Operational preview text.');
        $indexResponse->assertSeeText('SEO');
        $indexResponse->assertSeeText('canonical');
        $indexResponse->assertSeeText('og');
        $indexResponse->assertSeeText('Apply Filters');
        $indexResponse->assertSeeText('Search');
        $indexResponse->assertSeeText('Status');
        $indexResponse->assertSeeText('Category');
        $indexResponse->assertSeeText('Stock');
    }

    private function createProductManager(): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $user->givePermissionTo([
            'products.viewAny',
            'products.create',
            'products.update',
            'products.delete',
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
