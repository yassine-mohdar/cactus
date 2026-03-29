<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Settings\Services\SettingsService;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_variable_product_store_persists_options_variants_and_assignments(): void
    {
        Storage::fake('public');
        $this->configureCatalogMedia();

        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Variant Hoodie',
            'type' => Product::TYPE_VARIABLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'PARENT-HOODIE-001',
            'short_description' => 'Built from a variant matrix.',
            'options' => [
                ['name' => 'Size', 'values' => 'Small, Medium'],
                ['name' => 'Color', 'values' => 'Black, Blue'],
            ],
            'variants' => [
                [
                    'sku' => 'HOODIE-S-BLK',
                    'price' => '219.90',
                    'sale_price' => '199.90',
                    'quantity' => 6,
                    'assignments' => [
                        ['option' => 'Size', 'value' => 'Small'],
                        ['option' => 'Color', 'value' => 'Black'],
                    ],
                ],
                [
                    'sku' => 'HOODIE-M-BLU',
                    'price' => '229.90',
                    'sale_price' => '',
                    'quantity' => 3,
                    'assignments' => [
                        ['option' => 'Size', 'value' => 'Medium'],
                        ['option' => 'Color', 'value' => 'Blue'],
                    ],
                ],
            ],
            'variant_images' => [
                1 => UploadedFile::fake()->image('medium-blue.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()
            ->with(['options.values', 'variants.optionValues.option'])
            ->where('sku', 'PARENT-HOODIE-001')
            ->firstOrFail();

        $this->assertCount(2, $product->options);
        $this->assertCount(2, $product->variants);
        $this->assertSame(['Size', 'Color'], $product->options->pluck('name')->values()->all());

        $firstVariant = $product->variants->firstWhere('sku', 'HOODIE-S-BLK');
        $this->assertNotNull($firstVariant);
        $this->assertSame('Size: Small · Color: Black', $firstVariant->optionSummary());
        $this->assertSame(199.9, $firstVariant->effectivePrice());

        $secondVariant = $product->variants->firstWhere('sku', 'HOODIE-M-BLU');
        $this->assertNotNull($secondVariant);
        $this->assertNotNull($secondVariant->image_path);
        Storage::disk('public')->assertExists($secondVariant->image_path);
    }

    public function test_variable_product_update_replaces_variant_matrix_and_purges_old_media(): void
    {
        Storage::fake('public');
        $this->configureCatalogMedia();

        $manager = $this->createProductManager();
        $product = Product::factory()->variable()->create([
            'sku' => 'PARENT-VAR-002',
        ]);

        $oldVariant = $product->variants()->create([
            'sku' => 'OLD-VAR-001',
            'price' => 100,
            'sale_price' => 90,
            'quantity' => 2,
            'image_path' => 'catalog-assets/products/'.$product->id.'/variants/old.jpg',
        ]);
        Storage::disk('public')->put($oldVariant->image_path, 'old-image');

        $response = $this->actingAs($manager)->put(route('admin.catalog.products.update', $product), [
            'name' => $product->name,
            'type' => Product::TYPE_VARIABLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'options' => [
                ['name' => 'Material', 'values' => 'Cotton, Wool'],
            ],
            'variants' => [
                [
                    'sku' => 'MAT-COT',
                    'price' => '139.90',
                    'sale_price' => '129.90',
                    'quantity' => 8,
                    'assignments' => [
                        ['option' => 'Material', 'value' => 'Cotton'],
                    ],
                ],
            ],
            'variant_images' => [
                0 => UploadedFile::fake()->image('cotton.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product->refresh()->load(['options.values', 'variants.optionValues.option']);
        $this->assertCount(1, $product->options);
        $this->assertCount(1, $product->variants);
        $this->assertDatabaseMissing('product_variants', ['sku' => 'OLD-VAR-001']);
        Storage::disk('public')->assertMissing('catalog-assets/products/'.$product->id.'/variants/old.jpg');
    }

    public function test_variant_sku_must_be_unique_and_sale_price_cannot_exceed_regular_price(): void
    {
        $manager = $this->createProductManager();
        ProductVariant::query()->create([
            'product_id' => Product::factory()->variable()->create()->id,
            'sku' => 'DUP-VAR-001',
            'price' => 100,
            'sale_price' => 90,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($manager)
            ->from(route('admin.catalog.products.create'))
            ->post(route('admin.catalog.products.store'), [
                'name' => 'Broken Variant Product',
                'type' => Product::TYPE_VARIABLE,
                'status' => Product::STATUS_DRAFT,
                'sku' => 'BROKEN-PARENT-001',
                'options' => [
                    ['name' => 'Size', 'values' => 'Small'],
                ],
                'variants' => [
                    [
                        'sku' => 'DUP-VAR-001',
                        'price' => '100.00',
                        'sale_price' => '110.00',
                        'quantity' => 1,
                        'assignments' => [
                            ['option' => 'Size', 'value' => 'Small'],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.catalog.products.create'));
        $response->assertSessionHasErrors(['variants']);
    }

    public function test_nino_v2_product_surfaces_render_variant_controls(): void
    {
        $this->activateAdminTheme('nino-v2');
        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->get(route('admin.catalog.products.create'));

        $response->assertOk();
        $response->assertSeeText('Variant matrix');
        $response->assertSeeText('Option / attribute foundation');
        $response->assertSeeText('Variant SKUs, pricing, stock, and media');
        $response->assertSeeText('Add option');
        $response->assertSeeText('Add variant');
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

    private function configureCatalogMedia(): void
    {
        app(SettingsService::class)->setMany('system', [
            ['key' => 'default_media_disk', 'value' => 'public', 'type' => 'string'],
            ['key' => 'catalog_media_directory', 'value' => 'catalog-assets', 'type' => 'string'],
        ]);
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
