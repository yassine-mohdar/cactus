<?php

namespace App\Providers;

use App\Support\AdminThemeManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminThemeManager::class, fn (): AdminThemeManager => new AdminThemeManager());
    }

    public function boot(AdminThemeManager $themeManager): void
    {
        $finder = View::getFinder();
        $themePath = resource_path('views/'.$themeManager->viewPath());
        $paths = array_values(array_unique([$themePath, ...$finder->getPaths()]));

        $finder->setPaths($paths);
        $finder->flush();

        View::share('adminTheme', [
            'key' => $themeManager->active(),
            ...$themeManager->definition(),
        ]);

        View::share('adminThemeViteEntries', $themeManager->viteEntries());
        View::share('adminThemeStylesheet', $themeManager->stylesheetEntry());
        View::share('adminThemeScriptEntries', $themeManager->scriptEntries());
    }
}
