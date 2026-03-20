<?php

namespace Tests\Feature;

use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_theme_manager_resolves_nino_v1(): void
    {
        config(['admin_theme.active' => 'nino-v1']);

        $manager = $this->app->make(AdminThemeManager::class);

        $this->assertSame('nino-v1', $manager->active());
        $this->assertSame('themes/nino-v1', $manager->viewPath());
        $this->assertSame('resources/themes/nino-v1/app.css', $manager->stylesheetEntry());
        $this->assertSame(['resources/js/app.js'], $manager->scriptEntries());
        $this->assertSame(
            ['resources/themes/nino-v1/app.css', 'resources/js/app.js'],
            $manager->viteEntries()
        );
    }

    public function test_theme_manager_resolves_nino_v2(): void
    {
        config(['admin_theme.active' => 'nino-v2']);

        $manager = $this->app->make(AdminThemeManager::class);

        $this->assertSame('nino-v2', $manager->active());
        $this->assertSame('themes/nino-v2', $manager->viewPath());
        $this->assertSame('resources/themes/nino-v2/app.css', $manager->stylesheetEntry());
        $this->assertSame(['resources/js/app.js'], $manager->scriptEntries());
        $this->assertSame(
            ['resources/themes/nino-v2/app.css', 'resources/js/app.js'],
            $manager->viteEntries()
        );
    }

    public function test_invalid_theme_falls_back_to_nino_v1(): void
    {
        config(['admin_theme.active' => 'invalid-theme']);

        $manager = $this->app->make(AdminThemeManager::class);

        $this->assertSame('nino-v1', $manager->active());
        $this->assertSame('themes/nino-v1', $manager->viewPath());
        $this->assertSame('resources/themes/nino-v1/app.css', $manager->stylesheetEntry());
        $this->assertSame(['resources/js/app.js'], $manager->scriptEntries());
    }

    public function test_layout_and_auth_views_render_from_the_active_theme(): void
    {
        $this->assertThemeRendersExpectedViews('nino-v1', 'Sign in to your admin account');
        $this->assertThemeRendersExpectedViews('nino-v2', 'Sign in to Operations Hub');
    }

    public function test_order_create_livewire_view_resolves_through_the_active_theme_layout(): void
    {
        $this->assertOrderCreateResolvesForTheme('nino-v1');
        $this->assertOrderCreateResolvesForTheme('nino-v2');
    }

    public function test_nino_v2_layout_includes_mobile_sidebar_accessibility_bindings(): void
    {
        $this->activateAdminTheme('nino-v2');

        $layoutHtml = Blade::render(<<<'BLADE'
            @extends('admin.layouts.app')

            @section('title', 'Accessibility Probe')
            @section('content')
                <div>Accessibility probe body</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('id="admin-sidebar"', $layoutHtml);
        $this->assertStringContainsString('x-bind:aria-hidden="sidebarVisible() ? \'false\' : \'true\'"', $layoutHtml);
        $this->assertStringContainsString('x-bind:inert="sidebarVisible() ? null : \'inert\'"', $layoutHtml);
        $this->assertStringContainsString('aria-controls="admin-sidebar"', $layoutHtml);
        $this->assertStringContainsString('x-bind:aria-expanded="sidebarOpen ? \'true\' : \'false\'"', $layoutHtml);
        $this->assertStringContainsString('x-on:keydown.escape.window="closeSidebar()"', $layoutHtml);
    }

    private function assertThemeRendersExpectedViews(string $theme, string $authCopy): void
    {
        $this->activateAdminTheme($theme);

        $layoutHtml = Blade::render(<<<'BLADE'
            @extends('admin.layouts.app')

            @section('title', 'Theme Probe')
            @section('header')
                <h1>Theme Probe</h1>
            @endsection
            @section('subheader', 'Theme system check.')
            @section('content')
                <div>Layout body</div>
            @endsection
        BLADE);

        $authHtml = view('admin.auth.login', ['errors' => new ViewErrorBag()])->render();

        $this->assertStringContainsString('data-theme="'.$theme.'"', $layoutHtml);
        $this->assertStringContainsString('data-theme="'.$theme.'"', $authHtml);
        $this->assertStringContainsString($authCopy, $authHtml);
    }

    private function assertOrderCreateResolvesForTheme(string $theme): void
    {
        $this->activateAdminTheme($theme);

        $viewPath = view()->make('livewire.admin.orders.order-create')->getPath();
        $componentSource = file_get_contents(app_path('Livewire/Admin/Orders/OrderCreate.php'));

        $this->assertStringContainsString("/resources/views/themes/{$theme}/livewire/admin/orders/order-create.blade.php", $viewPath);
        $this->assertIsString($componentSource);
        $this->assertStringContainsString("->extends('admin.layouts.app')", $componentSource);
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
