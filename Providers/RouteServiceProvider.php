<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'Modules\RouteRequest\Http\Controllers';

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
            'as'         => 'routerequest.',
            'prefix'     => 'routerequest',
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
            'as'         => 'routerequest.admin.',
            'prefix'     => 'admin/routerequest',
            'namespace'  => $this->namespace . '\Admin',
            'middleware' => ['web', 'auth', 'ability:admin,admin-access'],
        ];

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__ . '/../Http/Routes/admin.php');
        });
    }
}
