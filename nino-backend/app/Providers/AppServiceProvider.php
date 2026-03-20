<?php

namespace App\Providers;

use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

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
        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        $this->registerAuditEventListeners();

        $registry = $this->app->make(\App\Modules\Shared\Navigation\Services\MenuRegistry::class);

        (new \App\Modules\IAM\Navigation\IAMMenu())->registerAdminMenu($registry);
        (new \App\Modules\Customers\Navigation\CustomersMenu())->registerAdminMenu($registry);
        (new \App\Modules\Catalog\Navigation\CatalogMenu())->registerAdminMenu($registry);
        (new \App\Modules\Orders\Navigation\OrdersMenu())->registerAdminMenu($registry);
        (new \App\Modules\Payments\Navigation\GatewaysMenu())->registerAdminMenu($registry);
        (new \App\Modules\Inventory\Navigation\InventoryMenu())->registerAdminMenu($registry);
        (new \App\Modules\Shipping\Navigation\ShippingMenu())->registerAdminMenu($registry);
        (new \App\Modules\Notifications\Navigation\NotificationsMenu())->registerAdminMenu($registry);
        (new \App\Modules\Promotions\Navigation\PromotionsMenu())->registerAdminMenu($registry);
        (new \App\Modules\Cms\Navigation\CmsMenu())->registerAdminMenu($registry);
        (new \App\Modules\Finance\Navigation\FinanceMenu())->registerAdminMenu($registry);
        (new \App\Modules\Support\Navigation\SupportMenu())->registerAdminMenu($registry);
        (new \App\Modules\Reports\Navigation\ReportsMenu())->registerAdminMenu($registry);
        (new \App\Modules\Settings\Navigation\SettingsMenu())->registerAdminMenu($registry);

        // Share the built menu tree with the admin sidebar view
        View::composer('admin.partials.sidebar', function ($view) {
            $builder = app(\App\Modules\Shared\Navigation\Services\MenuBuilder::class);
            $view->with('adminMenu', $builder->build());
        });
    }

    private function registerAuditEventListeners(): void
    {
        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event): void {
            app(AuditLogger::class)->log(
                action: 'impersonation.started',
                target: $event->impersonated instanceof \Illuminate\Database\Eloquent\Model ? $event->impersonated : null,
                newValues: [
                    'impersonator_id' => method_exists($event->impersonator, 'getAuthIdentifier') ? $event->impersonator->getAuthIdentifier() : null,
                    'impersonated_id' => method_exists($event->impersonated, 'getAuthIdentifier') ? $event->impersonated->getAuthIdentifier() : null,
                ],
                notes: 'Staff impersonation started',
                context: [
                    'source' => 'impersonation',
                    'event' => 'take',
                ],
                actor: $event->impersonator,
            );
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event): void {
            app(AuditLogger::class)->log(
                action: 'impersonation.ended',
                target: $event->impersonated instanceof \Illuminate\Database\Eloquent\Model ? $event->impersonated : null,
                newValues: [
                    'impersonator_id' => method_exists($event->impersonator, 'getAuthIdentifier') ? $event->impersonator->getAuthIdentifier() : null,
                    'impersonated_id' => method_exists($event->impersonated, 'getAuthIdentifier') ? $event->impersonated->getAuthIdentifier() : null,
                ],
                notes: 'Staff impersonation ended',
                context: [
                    'source' => 'impersonation',
                    'event' => 'leave',
                ],
                actor: $event->impersonator,
            );
        });
    }
}
