<?php

namespace App\Providers;

use App\Application\Estadisticas\CalculadoraEstadistica;
use App\Application\Estadisticas\DuracionOptimaPorRetencion;
use App\Application\Estadisticas\GeneradorReporte;
use App\Application\Estadisticas\MejorSistemaPorMeGusta;
use App\Application\Estadisticas\MejorSistemaPorSeguidores;
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

        // Tagged binding para calculadoras de estadísticas (OCP real).
        // Añadir una nueva calculadora = nueva clase + una línea aquí.
        // DuracionOptimaPorRetencion se instancia explícitamente para
        // inyectar el parámetro por defecto (30s).
        $this->app->instance(
            CalculadoraEstadistica::class.'_duracion',
            new DuracionOptimaPorRetencion,
        );
        $this->app->tag(
            CalculadoraEstadistica::class.'_duracion',
            CalculadoraEstadistica::class,
        );

        $this->app->tag([
            MejorSistemaPorMeGusta::class,
            MejorSistemaPorSeguidores::class,
        ], CalculadoraEstadistica::class);

        $this->app->bind(GeneradorReporte::class, function ($app) {
            return new GeneradorReporte(iterator_to_array($app->tagged(CalculadoraEstadistica::class)));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
