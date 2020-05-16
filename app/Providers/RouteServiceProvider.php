<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    const API_ROUTES_FOLDER = 'routes/api/';

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'namespace' => $this->namespace
        ], function () {
            
            // Public API
            Route::domain('api.localhost')
                 ->prefix('partner')
                 ->group(
                    base_path(self::API_ROUTES_FOLDER . 'partner.php')
                 );
    
            // Internal API
            Route::domain('api.localhost')
                 ->group(
                    base_path(self::API_ROUTES_FOLDER . 'internal.php')
                 );

            // Admin API
            Route::domain('admin.localhost')
                 ->middleware('apikey.validate')
                 ->group(
                     base_path(self::API_ROUTES_FOLDER . 'admin.php')
                 );
        });

    }
}
