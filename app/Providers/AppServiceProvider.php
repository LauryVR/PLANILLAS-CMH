<?php

namespace App\Providers;
use Illuminate\Support\Facades\View; // <-- Agrega este import
use Illuminate\Pagination\Paginator;  // <-- Agrega este si tampoco estaba
use App\Models\Maestro;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        // Carga solo un entero ligero (conteo) en lugar de miles de modelos
    View::share('totalMaestros', Maestro::count()); 
            Paginator::useBootstrap();
    }
}
