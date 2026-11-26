<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'Modules\AfterburnerDispatch\Http\Controllers';

    public function before(Router $router)
    {
        //
    }

    public function map(Router $router)
    {
        $this->registerWebRoutes();
        $this->registerAdminRoutes();
    }

    protected function registerWebRoutes(): void
    {
        $config = [
            'as'         => 'afterburner.',
            'prefix'     => 'afterburner',
            'namespace'  => $this->namespace . '\Frontend',
            'middleware' => ['web', 'auth'],
        ];

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__ . '/../Http/Routes/web.php');
        });
    }

    protected function registerAdminRoutes(): void
    {
        $config = [
            'as'         => 'afterburner.admin.',
            'prefix'     => 'admin/afterburner',
            'namespace'  => $this->namespace . '\Admin',
            'middleware' => ['web', 'auth', 'ability:admin,admin-access'],
        ];

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__ . '/../Http/Routes/admin.php');
        });
    }
}
