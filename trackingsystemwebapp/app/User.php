<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Auth;
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password','tipo_usuario_id','correo', 'operadora', 'telefono',
        'creador_id', 'modificador_id','estado', 'cooperativa_id', 'unidades_id',
        'unidades_pertenecientes','ip'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

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

    public function creador()
    {
        return $this->belongsTo('App\User');
    }

    public function modificador()
    {
        return $this->belongsTo('App\User');
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
}
