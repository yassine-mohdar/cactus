<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Modules\Shared\Navigation\Services\MenuRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $registry = $this->app->make(\App\Modules\Shared\Navigation\Services\MenuRegistry::class);

        // Core Modules Navigation Registration
        (new \App\Modules\IAM\Navigation\IAMMenu())->registerAdminMenu($registry);
        (new \App\Modules\Catalog\Navigation\CatalogMenu())->registerAdminMenu($registry);

        // Share the built menu tree with the admin sidebar view
        \Illuminate\Support\Facades\View::composer('admin.partials.sidebar', function ($view) {
            $builder = app(\App\Modules\Shared\Navigation\Services\MenuBuilder::class);
            $view->with('adminMenu', $builder->build());
        });
    }
}
