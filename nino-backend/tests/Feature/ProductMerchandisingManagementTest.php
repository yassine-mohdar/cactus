<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMerchandisingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_product_store_persists_related_upsell_cross_sell_and_badges(): void
    {
        $manager = $this->createProductManager();
        $related = Product::factory()->create(['sku' => 'REL-001']);
        $upsell = Product::factory()->create(['sku' => 'UP-001']);
        $crossSell = Product::factory()->create(['sku' => 'CS-001']);

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.store'), [
            'name' => 'Merch Product',
            'type' => Product::TYPE_SIMPLE,
            'status' => Product::STATUS_PUBLISHED,
            'sku' => 'MERCH-001',
            'price' => '99.90',
            'quantity' => 9,
            'related_products' => [$related->id],
            'upsell_products' => [$upsell->id],
            'cross_sell_products' => [$crossSell->id],
            'badge_labels' => 'Limited drop, Bestseller, Limited drop',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::query()
            ->with(['relatedProducts', 'upsellProducts', 'crossSellProducts'])
            ->where('sku', 'MERCH-001')
            ->firstOrFail();

        $this->assertSame([$related->id], $product->relatedProducts->pluck('id')->all());
        $this->assertSame([$upsell->id], $product->upsellProducts->pluck('id')->all());
        $this->assertSame([$crossSell->id], $product->crossSellProducts->pluck('id')->all());
        $this->assertSame(['Limited drop', 'Bestseller'], $product->badges());
    }

    public function test_product_update_resyncs_merchandising_links_and_excludes_self_reference(): void
    {
        $manager = $this->createProductManager();
        $product = Product::factory()->create(['sku' => 'MERCH-UPDATE-001']);
        $oldRelated = Product::factory()->create(['sku' => 'OLD-REL-001']);
        $newRelated = Product::factory()->create(['sku' => 'NEW-REL-001']);
        $newUpsell = Product::factory()->create(['sku' => 'NEW-UP-001']);

        $product->relatedProducts()->sync([$oldRelated->id]);

        $response = $this->actingAs($manager)->put(route('admin.catalog.products.update', $product), [
            'name' => $product->name,
            'type' => $product->type,
            'status' => $product->status,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'related_products' => [$newRelated->id, $product->id],
            'upsell_products' => [$newUpsell->id],
            'cross_sell_products' => [$product->id],
            'badge_labels' => 'Gift ready, Staff pick',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product->refresh()->load(['relatedProducts', 'upsellProducts', 'crossSellProducts']);

        $this->assertSame([$newRelated->id], $product->relatedProducts->pluck('id')->all());
        $this->assertSame([$newUpsell->id], $product->upsellProducts->pluck('id')->all());
        $this->assertSame([], $product->crossSellProducts->pluck('id')->all());
        $this->assertSame(['Gift ready', 'Staff pick'], $product->badges());
    }

    public function test_nino_v2_product_surfaces_render_merchandising_controls(): void
    {
        $this->activateAdminTheme('nino-v2');

        $manager = $this->createProductManager();
        Product::factory()->create(['sku' => 'LOOKUP-001']);

        $response = $this->actingAs($manager)->get(route('admin.catalog.products.create'));

        $response->assertOk();
        $response->assertSeeText('Merchandising links');
        $response->assertSeeText('Related products');
        $response->assertSeeText('Upsells');
        $response->assertSeeText('Cross-sells');
        $response->assertSeeText('Badge labels');
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
