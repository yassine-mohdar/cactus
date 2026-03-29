<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Jobs\ProcessProductImageJob;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use App\Modules\Settings\Services\SettingsService;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_product_store_persists_featured_and_gallery_images_using_configured_media_storage(): void
    {
        Storage::fake('public');
        Queue::fake();
        $this->configureCatalogMedia();

        $manager = $this->createProductManager();

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Media Plush',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'MEDIA-001',
            'price' => '89.90',
            'quantity' => 4,
            'featured_image' => UploadedFile::fake()->image('featured.jpg'),
            'featured_image_alt' => 'Hero plush product image',
            'gallery_images' => [
                UploadedFile::fake()->image('detail-1.jpg'),
                UploadedFile::fake()->image('detail-2.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()->with(['images', 'featuredImage'])->where('sku', 'MEDIA-001')->firstOrFail();

        $this->assertCount(3, $product->images);
        $this->assertNotNull($product->featuredImage);
        $this->assertSame('Hero plush product image', $product->featuredImage->alt_text);

        foreach ($product->images as $image) {
            $this->assertStringStartsWith('catalog-assets/products/'.$product->id.'/', $image->path);
            Storage::disk('public')->assertExists($image->path);
        }

        Queue::assertPushed(ProcessProductImageJob::class, 3);
    }

    public function test_product_update_supports_gallery_order_alt_text_removal_and_featured_selection(): void
    {
        Storage::fake('public');
        $this->configureCatalogMedia();

        $manager = $this->createProductManager();
        $product = Product::factory()->create([
            'sku' => 'MEDIA-UPDATE-001',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'price' => 55,
        ]);

        $firstPath = 'catalog-assets/products/'.$product->id.'/first.jpg';
        $secondPath = 'catalog-assets/products/'.$product->id.'/second.jpg';
        $thirdPath = 'catalog-assets/products/'.$product->id.'/third.jpg';

        Storage::disk('public')->put($firstPath, 'first');
        Storage::disk('public')->put($secondPath, 'second');
        Storage::disk('public')->put($thirdPath, 'third');

        $first = $product->images()->create([
            'path' => $firstPath,
            'is_featured' => true,
            'sort_order' => 0,
            'alt_text' => 'Original first image',
        ]);
        $second = $product->images()->create([
            'path' => $secondPath,
            'is_featured' => false,
            'sort_order' => 1,
            'alt_text' => 'Original second image',
        ]);
        $third = $product->images()->create([
            'path' => $thirdPath,
            'is_featured' => false,
            'sort_order' => 2,
            'alt_text' => null,
        ]);

        $response = $this->actingAs($manager)->put(route('admin.catalog.products.update', $product), [
            'name' => $product->name,
            'type' => $product->type,
            'status' => $product->status,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'featured_image_id' => $second->id,
            'media_meta' => [
                $second->id => ['alt_text' => 'New featured side view', 'sort_order' => 4],
                $third->id => ['alt_text' => 'Back detail image', 'sort_order' => 1],
            ],
            'remove_image_ids' => [$first->id],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product->refresh()->load(['images', 'featuredImage']);

        $this->assertCount(2, $product->images);
        $this->assertSame($second->id, $product->featuredImage?->id);
        $this->assertSame('New featured side view', $product->featuredImage?->alt_text);
        $this->assertSame(
            [0, 1],
            $product->images->sortBy('sort_order')->pluck('sort_order')->values()->all()
        );
        $this->assertSame('Back detail image', $product->images->firstWhere('id', $third->id)?->alt_text);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_product_image_processing_job_backfills_missing_alt_text(): void
    {
        Storage::fake('public');
        $this->configureCatalogMedia();

        $product = Product::factory()->create([
            'name' => 'Queue Plush',
        ]);

        $path = 'catalog-assets/products/'.$product->id.'/queued-image.jpg';
        Storage::disk('public')->put($path, 'queued-image');

        $image = $product->images()->create([
            'path' => $path,
            'is_featured' => false,
            'sort_order' => 1,
            'alt_text' => null,
        ]);

        $job = new ProcessProductImageJob($image->id);
        $job->handle(app(MediaStorageSettingsService::class));

        $this->assertSame('Queue Plush gallery image', $image->fresh()->alt_text);
    }

    public function test_nino_v2_product_surfaces_render_media_controls(): void
    {
        Storage::fake('public');
        $this->configureCatalogMedia();
        $this->activateAdminTheme('nino-v2');

        $manager = $this->createProductManager();

        $createResponse = $this->actingAs($manager)->get(route('admin.catalog.products.create'));
        $createResponse->assertOk();
        $createResponse->assertSeeText('Product media');
        $createResponse->assertSeeText('Featured image upload');
        $createResponse->assertSeeText('Featured image alt text');
        $createResponse->assertSeeText('Gallery uploads');

        $product = Product::factory()->create([
            'sku' => 'MEDIA-VIEW-001',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'price' => 40,
        ]);

        $path = 'catalog-assets/products/'.$product->id.'/featured.jpg';
        Storage::disk('public')->put($path, 'featured');
        $product->images()->create([
            'path' => $path,
            'is_featured' => true,
            'sort_order' => 0,
            'alt_text' => 'Front image',
        ]);

        $editResponse = $this->actingAs($manager)->get(route('admin.catalog.products.edit', $product));
        $editResponse->assertOk();
        $editResponse->assertSeeText('Existing gallery');
        $editResponse->assertSeeText('Set as featured');
        $editResponse->assertSeeText('Display order');
        $editResponse->assertSeeText('Remove this image on save');

        $indexResponse = $this->actingAs($manager)->get(route('admin.catalog.products.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSeeText('1 media asset');
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
