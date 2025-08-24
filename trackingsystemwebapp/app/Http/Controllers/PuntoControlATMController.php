<?php

namespace App\Http\Controllers;

use App\RutaAtmOficial;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use Validator;
use App\PuntoControlAtmOficial;
use App\Cooperativa;
use App\TipoUsuario;
use Auth;

class PuntoControlATMController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('panel.lista-puntos-control-atm',
        [
            'puntos_control'=> PuntoControlAtmOficial::permitido()->orderBy('descripcion', 'asc')
                ->paginate(10),
            'id_cooperativa' =>$user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }

    public function show($id)
    {
        $punto_control = PuntoControlAtmOficial::with('cooperativa')->findOrFail($id);
        return response()->json($punto_control);
    }

    public function search(Request $request)
    {
        $user = $request->user();
        $id_cooperativa = $request->input('cooperativa');
        $search = $request->input('search');
        $puntosControl = PuntoControlAtmOficial::permitido($id_cooperativa)->orderBy('descripcion')->where(function ($query) use($search){
                if (isset($search) && $search != '')
                    $query->where('descripcion', 'like', "%$search%");
            }
        );
        $puntosControl = $puntosControl->paginate(10);
        $puntosControl->setPath($request->fullUrl());
        return view('panel.lista-puntos-control-atm',
        [
            'puntos_control'=> $puntosControl,
            'id_cooperativa' =>$user->cooperativa_id,
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'cooperativas' => Cooperativa::permitida()->orderBy('descripcion')->where('estado', 'A')->get(),
            'search' => $search,
            'coop' => $id_cooperativa
        ]);
    }
}
