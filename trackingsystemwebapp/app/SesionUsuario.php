<?php

namespace App;

use Moloquent as Model;

class SesionUsuario extends Model
{
    protected $fillable = [
        'fecha_sesion', 'usuario_id', 'direccion_ip', 'conexion'
    ];
    protected $dates = [
        'fecha_sesion'
    ];
    public function usuario() {
        return $this->belongsTo('App\User');
    }
    public function getConexionAttribute($value) {
        return $value === 'S'?'Conectado':'Desconectado';
    }
}
