<?php

namespace App;

use Moloquent;
use Auth;
use Storage;
class PuntoControl extends Moloquent
{
    // Lado (px) del cuadrado al que se normaliza la imagen del punto imaginario.
    const TAM_IMAGEN = 64;

    protected $fillable = [
        'descripcion','latitud','longitud','estado',
        'radio','creador_id', 'modificador_id','cooperativa_id','pdi', 'entrada', 'salida',
        'mt', 'estado_exportacion', 'fecha_exportacion','pdi_padre','activo','bloque','tipo_mar','poligono','pto_imaginario',
        'imagen'
    ];
    protected $dates = [
        'fecha_exportacion'
    ];
    protected $appends = [
        'imagen_url'
    ];
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
    public function scopePermitido($query, $cooperativa = null) {
        $user = Auth::user();
        $tipo_usuario = $user->tipo_usuario->valor;
        if ($tipo_usuario != 1)
            $query->where('cooperativa_id', $user->cooperativa_id);
        else if ($cooperativa != null)
            $query->where('cooperativa_id', $cooperativa);
        return $query;
    }

    // Imagen del punto imaginario en el mapa (storage/app/public/puntos-control/imagenes).
    // Se sirve por ruta propia (PuntoControlController@imagen), igual que los íconos de TipoUnidad.
    public function getImagenUrlAttribute()
    {
        $ruta = isset($this->attributes['imagen']) ? $this->attributes['imagen'] : null;
        return empty($ruta) ? null : url('puntos-de-control/imagen/' . pathinfo($ruta, PATHINFO_FILENAME));
    }

    public function eliminarImagen()
    {
        $ruta = isset($this->attributes['imagen']) ? $this->attributes['imagen'] : null;
        if (!empty($ruta) && Storage::disk('public')->exists($ruta))
            Storage::disk('public')->delete($ruta);
        $this->imagen = null;
    }
}
