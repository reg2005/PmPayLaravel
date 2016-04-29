<?php

namespace reg2005\PmPayLaravel;

use Illuminate\Support\ServiceProvider;
//use reg2005\PmPayLaravel\Http\Middleware\OnlyCli;

class PmServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {

        require __DIR__ . '/Http/routes.php';
        $this->publishes([
            __DIR__.'/migrations/' => base_path('/database/migrations'),
        ], 'migrations');


    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {

    }
}