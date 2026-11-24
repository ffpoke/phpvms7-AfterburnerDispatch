<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Providers;

use App\Services\ModuleService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\AfterburnerDispatch\Support\DrmEntitlements;
use Modules\AfterburnerDispatch\Support\FlightAwareParser;
use Modules\AfterburnerDispatch\Support\RealWorldAirlines;
use Modules\AfterburnerDispatch\Support\SchemaHelper;
use Modules\AfterburnerDispatch\Support\UpdateManager;

class AppServiceProvider extends ServiceProvider
{
    protected $moduleSvc;

    public function boot(): void
    {
        foreach (['RealWorldAirlines', 'FlightAwareParser', 'SchemaHelper', 'DrmEntitlements', 'UpdateManager', 'ClientSettings', 'DiscordNotifier', 'SubfleetSyncService', 'RouteEstimatorService', 'DropSchedule', 'CompanionModuleManager', 'TravelDispatchService', 'VatsimOverlayService', 'DispatchDigestService', 'SubfleetCreatorService', 'InstallTelemetry'] as $cls) {
            $fqcn = 'Modules\\AfterburnerDispatch\\Support\\' . $cls;
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

        try {
            $listenerFile = __DIR__ . '/../Listeners/CronAutoMaintenanceListener.php';
            if (is_file($listenerFile)) {
                require_once $listenerFile;
                if (class_exists(\App\Events\CronDaily::class)) {
                    \Illuminate\Support\Facades\Event::listen(\App\Events\CronDaily::class, [\Modules\AfterburnerDispatch\Listeners\CronAutoMaintenanceListener::class, 'handleDaily']);
                }
                if (class_exists(\App\Events\CronHourly::class)) {
                    \Illuminate\Support\Facades\Event::listen(\App\Events\CronHourly::class, [\Modules\AfterburnerDispatch\Listeners\CronAutoMaintenanceListener::class, 'handleHourly']);
                }
            }
        } catch (\Throwable $e) {}

        if ($this->app->runningInConsole()) {
            $cmdFile = __DIR__ . '/../Console/SendRouteDigestCommand.php';
            if (is_file($cmdFile)) {
                require_once $cmdFile;
                $this->commands([\Modules\AfterburnerDispatch\Console\SendRouteDigestCommand::class]);
            }
        }

        \Modules\AfterburnerDispatch\Support\InstallTelemetry::pingIfDue('install_heartbeat', false);
    }

    #[\Override]
    public function register(): void
    {
        foreach (['RealWorldAirlines', 'FlightAwareParser', 'SchemaHelper', 'DrmEntitlements', 'UpdateManager', 'ClientSettings', 'DiscordNotifier', 'SubfleetSyncService', 'RouteEstimatorService', 'DropSchedule', 'CompanionModuleManager', 'TravelDispatchService', 'VatsimOverlayService', 'DispatchDigestService', 'SubfleetCreatorService', 'InstallTelemetry'] as $cls) {
            $fqcn = 'Modules\\AfterburnerDispatch\\Support\\' . $cls;
            if (!class_exists($fqcn, false)) {
                require_once __DIR__ . '/../Support/' . $cls . '.php';
            }
        }
    }

    public function registerLinks(): void
    {
        $this->moduleSvc->addFrontendLink('Afterburner Dispatch', '/afterburner', 'fas fa-route', $logged_in = true);
        $this->moduleSvc->addAdminLink('Afterburner Dispatch', '/admin/afterburner', 'pe-7s-map-2');
    }

    protected function registerWidgets(): void
    {
        Blade::component('afterburner-form', \Modules\AfterburnerDispatch\Widgets\RouteRequestWidget::class);
        Blade::component('routerequest-form', \Modules\AfterburnerDispatch\Widgets\RouteRequestWidget::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('afterburner.php'),
        ], 'afterburner');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'afterburner');
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/afterburner');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/afterburner';
        }, \Config::get('view.paths')), [$sourcePath]), 'afterburner');
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/afterburner');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'afterburner');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'afterburner');
        }
    }
}
