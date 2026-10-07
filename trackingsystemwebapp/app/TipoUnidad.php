<?php

namespace App;

use Moloquent;
use Storage;

class TipoUnidad extends Moloquent
{
    protected $fillable = [
        'descripcion', 'creador_id', 'modificador_id', 'estado', 'icono_lista', 'icono_mapa'
    ];

    protected $appends = [
        'icono_lista_url', 'icono_mapa_url'
    ];

    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function getIconoListaUrlAttribute()
    {
        return $this->urlIcono('icono_lista');
    }

    public function getIconoMapaUrlAttribute()
    {
        return $this->urlIcono('icono_mapa');
    }

    private function urlIcono($campo)
    {
        $ruta = isset($this->attributes[$campo]) ? $this->attributes[$campo] : null;
        // Se sirve por ruta propia (TipoUnidadController@icono) para no depender del symlink public/storage.
        // La URL va sin extensión: servidores como `php -S` responden 404 a rutas ".png" inexistentes sin pasar por Laravel.
        return empty($ruta) ? null : url('tipos-de-unidades/icono/' . pathinfo($ruta, PATHINFO_FILENAME));
    }

    // $campo: 'icono_lista' o 'icono_mapa'
    public function eliminarIcono($campo)
    {
        $ruta = isset($this->attributes[$campo]) ? $this->attributes[$campo] : null;
        if (!empty($ruta) && Storage::disk('public')->exists($ruta))
            Storage::disk('public')->delete($ruta);
        $this->$campo = null;
    }
}
