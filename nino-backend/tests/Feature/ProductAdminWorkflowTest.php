<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Catalog\Models\Product;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_bulk_product_actions_support_publish_and_archive_workflows(): void
    {
        $manager = $this->createProductManager();
        $draftA = Product::factory()->draft()->create(['sku' => 'BULK-A']);
        $draftB = Product::factory()->draft()->create(['sku' => 'BULK-B']);

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.bulk'), [
            'action' => 'publish',
            'product_ids' => [$draftA->id, $draftB->id],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $this->assertSame(Product::STATUS_PUBLISHED, $draftA->fresh()->status);
        $this->assertSame(Product::STATUS_PUBLISHED, $draftB->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'catalog.product.bulk_status_changed',
            'auditable_id' => $draftA->id,
        ]);
    }

    public function test_single_product_status_workflow_can_move_between_draft_and_published(): void
    {
        $manager = $this->createProductManager();
        $product = Product::factory()->draft()->create(['sku' => 'STATUS-001']);

        $response = $this->actingAs($manager)->post(route('admin.catalog.products.status', $product), [
            'status' => Product::STATUS_PUBLISHED,
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));
        $this->assertSame(Product::STATUS_PUBLISHED, $product->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'catalog.product.status_changed',
            'auditable_id' => $product->id,
        ]);
    }

    public function test_edit_surface_shows_recent_product_audit_activity(): void
    {
        $manager = $this->createProductManager();
        $product = Product::factory()->published()->create(['sku' => 'AUDIT-001']);

        AuditLog::create([
            'user_id' => $manager->id,
            'actor_type' => $manager::class,
            'actor_name' => $manager->name,
            'actor_email' => $manager->email,
            'action' => 'catalog.product.updated',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'target_label' => 'Product: '.$product->name,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'published'],
            'context' => ['method' => 'PUT'],
            'notes' => 'Product details updated',
        ]);

        $response = $this->actingAs($manager)->get(route('admin.catalog.products.edit', $product));

        $response->assertOk();
        $response->assertSeeText('Recent audit activity');
        $response->assertSeeText('Catalog Product Updated');
        $response->assertSeeText('Product details updated');
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
