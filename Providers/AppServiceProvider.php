<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Providers;

use App\Services\ModuleService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\RouteRequest\Support\DrmEntitlements;
use Modules\RouteRequest\Support\FlightAwareParser;
use Modules\RouteRequest\Support\RealWorldAirlines;
use Modules\RouteRequest\Support\SchemaHelper;
use Modules\RouteRequest\Support\UpdateManager;

class AppServiceProvider extends ServiceProvider
{
    protected $moduleSvc;

    public function boot(): void
    {
        foreach (['RealWorldAirlines', 'FlightAwareParser', 'SchemaHelper', 'DrmEntitlements', 'UpdateManager', 'ClientSettings', 'DiscordNotifier', 'SubfleetSyncService', 'RouteEstimatorService', 'DropSchedule', 'CompanionModuleManager', 'TravelDispatchService', 'VatsimOverlayService', 'InstallTelemetry'] as $cls) {
            $fqcn = 'Modules\\RouteRequest\\Support\\' . $cls;
            if (!class_exists($fqcn, false)) {
                require_once __DIR__ . '/../Support/' . $cls . '.php';
            }
        }

        $this->moduleSvc = app(ModuleService::class);

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLinks();
        $this->registerWidgets();

        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        \Modules\RouteRequest\Support\InstallTelemetry::pingIfDue('install_heartbeat', false);
    }

    #[\Override]
    public function register(): void
    {
        foreach (['RealWorldAirlines', 'FlightAwareParser', 'SchemaHelper', 'DrmEntitlements', 'UpdateManager', 'ClientSettings', 'DiscordNotifier', 'SubfleetSyncService', 'RouteEstimatorService', 'DropSchedule', 'CompanionModuleManager', 'TravelDispatchService', 'VatsimOverlayService', 'InstallTelemetry'] as $cls) {
            $fqcn = 'Modules\\RouteRequest\\Support\\' . $cls;
            if (!class_exists($fqcn, false)) {
                require_once __DIR__ . '/../Support/' . $cls . '.php';
            }
        }
    }

    public function registerLinks(): void
    {
        $this->moduleSvc->addFrontendLink('Route Request', '/routerequest', 'fas fa-route', $logged_in = true);
        $this->moduleSvc->addAdminLink('Route Requests', '/admin/routerequest', 'pe-7s-map-2');
    }

    protected function registerWidgets(): void
    {
        Blade::component('routerequest-form', \Modules\RouteRequest\Widgets\RouteRequestWidget::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('routerequest.php'),
        ], 'routerequest');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'routerequest');
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/routerequest');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/routerequest';
        }, \Config::get('view.paths')), [$sourcePath]), 'routerequest');
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/routerequest');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'routerequest');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'routerequest');
        }
    }
}
