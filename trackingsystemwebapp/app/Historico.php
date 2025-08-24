<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Historico extends Model
{
    public function tipo_usuario()
    {
        return $this->belongsTo('App\TipoUsuario');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
    public function unidad()
    {
        return $this->belongsTo('App\Unidad');
    }
}
