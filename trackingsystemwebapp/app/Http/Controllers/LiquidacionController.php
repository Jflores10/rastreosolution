<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\Unidad;
use App\User;
use App\Conductor;
use App\Ruta;
use Validator;
use Auth;
use DateInterval;
use App\Despacho;
use MongoDB\BSON\UTCDateTime;
use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use App\TipoUsuario;

class LiquidacionController extends Controller
{
    public function index()
    {
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_sub($desde, date_interval_create_from_date_string('5 hours'));
        date_sub($hasta, date_interval_create_from_date_string('5 hours'));
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1)
        {
            $cooperativas = Cooperativa::orderBy('descripcion',
            'asc')->get();
        }
        else
        {
            $cooperativas = Cooperativa::orderBy('descripcion',
            'asc')->where('_id', $user->cooperativa_id)->get();
        }
        return view('panel.liquidacion.liquidacion',['tipo' => 'L','cooperativas' => $cooperativas, 
        'desde' => $desde, 'hasta' => $hasta]);
    }
}
