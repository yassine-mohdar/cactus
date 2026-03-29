<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Services\UserAvatarService;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemSettingsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_system_settings_page_renders_maintenance_media_and_feature_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'system']));

        $response->assertOk();
        $response->assertSee('System &amp; Maintenance', false);
        $response->assertSee('Maintenance Mode');
        $response->assertSee('Media &amp; Storage', false);
        $response->assertSee('Feature Flags');
        $response->assertSee('Enable Maintenance Mode');
        $response->assertSee('Default Media Disk');
        $response->assertSee('Enable Promotions Module');
    }

    public function test_system_settings_persist_and_apply_media_and_feature_runtime_config(): void
    {
        $manager = $this->makeSettingsManager();

        $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'system',
            'maintenance_mode_enabled' => '0',
            'maintenance_message' => 'Scheduled maintenance in progress.',
            'maintenance_bypass_staff' => '1',
            'default_media_disk' => 'public',
            'avatar_media_directory' => 'profile-avatars',
            'catalog_media_directory' => 'catalog-assets',
            'feature_promotions_enabled' => '0',
            'feature_cms_enabled' => '1',
            'feature_community_enabled' => '0',
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'system']));

        Route::middleware('web')->get('/__test/settings/system-runtime', function () {
            return response()->json([
                'media_disk' => config('media.default_disk'),
                'avatar_directory' => config('media.directories.avatars'),
                'catalog_directory' => config('media.directories.catalog'),
                'promotions_enabled' => config('features.promotions'),
                'cms_enabled' => config('features.cms'),
                'community_enabled' => config('features.community'),
            ]);
        });

        $response = $this->get('/__test/settings/system-runtime');

        $response->assertOk();
        $response->assertJson([
            'media_disk' => 'public',
            'avatar_directory' => 'profile-avatars',
            'catalog_directory' => 'catalog-assets',
            'promotions_enabled' => false,
            'cms_enabled' => true,
            'community_enabled' => false,
        ]);
    }

    public function test_maintenance_mode_blocks_guests_and_allows_staff_bypass_when_enabled(): void
    {
        app(SettingsService::class)->setMany('system', [
            ['key' => 'maintenance_mode_enabled', 'value' => true, 'type' => 'boolean'],
            ['key' => 'maintenance_message', 'value' => 'Maintenance window active.', 'type' => 'string'],
            ['key' => 'maintenance_bypass_staff', 'value' => true, 'type' => 'boolean'],
        ]);

        Route::middleware('web')->get('/__test/system/maintenance-window', fn () => 'ok');

        $this->get('/__test/system/maintenance-window')
            ->assertStatus(503)
            ->assertSee('Maintenance window active.');

        $staff = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $this->actingAs($staff)
            ->get('/__test/system/maintenance-window')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_feature_flag_middleware_hides_disabled_features(): void
    {
        app(SettingsService::class)->setMany('system', [
            ['key' => 'feature_promotions_enabled', 'value' => false, 'type' => 'boolean'],
            ['key' => 'feature_cms_enabled', 'value' => true, 'type' => 'boolean'],
        ]);

        Route::middleware(['web', 'feature.enabled:promotions'])->get('/__test/system/promotions-feature', fn () => 'promotions');
        Route::middleware(['web', 'feature.enabled:cms'])->get('/__test/system/cms-feature', fn () => 'cms');

        $this->get('/__test/system/promotions-feature')->assertNotFound();
        $this->get('/__test/system/cms-feature')->assertOk()->assertSee('cms');
    }

    public function test_avatar_uploads_use_configured_media_disk_and_directory(): void
    {
        Storage::fake('public');

        app(SettingsService::class)->setMany('system', [
            ['key' => 'default_media_disk', 'value' => 'public', 'type' => 'string'],
            ['key' => 'avatar_media_directory', 'value' => 'profile-avatars', 'type' => 'string'],
        ]);

        $user = User::factory()->create();
        $path = app(UserAvatarService::class)->replace($user, UploadedFile::fake()->image('avatar.jpg'));

        $this->assertStringStartsWith('profile-avatars/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $user->fresh()->avatar);
    }

    private function makeSettingsManager(): User
    {
        Permission::findOrCreate('settings.manage', 'web');

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);

        $user->givePermissionTo('settings.manage');

        return $user;
    }
}
