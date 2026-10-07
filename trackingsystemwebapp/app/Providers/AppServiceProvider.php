<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\TipoUnidad;
use View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Íconos personalizados por tipo de unidad para el listado y el mapa de homev2.
        View::composer('homev2', function ($view) {
            $iconos = [];
            foreach (TipoUnidad::all() as $tipo)
            {
                if ($tipo->icono_lista_url || $tipo->icono_mapa_url)
                    $iconos[(string) $tipo->_id] = [
                        'icono_lista_url' => $tipo->icono_lista_url,
                        'icono_mapa_url' => $tipo->icono_mapa_url
                    ];
            }
            $view->with('iconos_tipos_unidad', $iconos);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
