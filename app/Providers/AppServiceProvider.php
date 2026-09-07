<?php

namespace App\Providers;

use App\Domain\Publicacion\PublicacionRepositorioInterface;
use App\Infrastructure\Repositorios\PublicacionRepositorioEloquent;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // DIP: los controladores y casos de uso dependen de la interfaz;
        // el contenedor resuelve la implementación Eloquent.
        $this->app->bind(
            PublicacionRepositorioInterface::class,
            PublicacionRepositorioEloquent::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
