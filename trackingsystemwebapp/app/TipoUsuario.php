<?php

namespace App;

use Moloquent as Model;
use Auth;
class TipoUsuario extends Model
{
    protected $fillable = [
        'descripcion','valor'
    ];
    public function scopePermitido($query) {
        $user = Auth::user();
        $valor = $user->tipo_usuario->valor;
        if ($valor != 1)
            $query->where('valor', '!=', '1');
        return $query;
    }
}
