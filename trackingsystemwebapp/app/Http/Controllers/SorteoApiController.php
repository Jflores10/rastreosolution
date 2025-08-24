<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Unidad;
use Carbon\Carbon;
use App\Sorteo;
use App\DetalleSorteo;
use Excel;
use App\Cooperativa;

class SorteoApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->tipo_usuario->valor == 1 || (($user->tipo_usuario->valor == 2 || $user->tipo_usuario->valor == 3 || $user->tipo_usuario->valor == 4
            || $user->tipo_usuario->valor == 5))) {
            if ($request->isMethod('GET'))
                return view('panel.sorteos.lista-sorteos', [
                    'unidades' => Unidad::orderBy('descripcion')->activa()->select([
                        '_id', 'placa', 'descripcion', 'cooperativa_id'
                    ])->get(),
                    'tipo_usuario' => $user->tipo_usuario->valor,
                    'cooperativas' => Cooperativa::orderBy('descripcion')->activa()->select([
                        '_id', 'descripcion'
                    ])->permitida()->get()
                ]);
            else {
                $this->validate($request, [
                    'fecha' => [
                        'required', 'date'
                    ],
                    'cantidad_sorteos' => 'required|integer',
                    'sorteos.*.intervalo' => 'required|integer',
                    'sorteos.*.hora_inicio' => 'required|date_format:H:i',
                    'cabecera' => 'required|max:255',
                    'cooperativa_id' => 'required|exists:cooperativas,_id'
                ]);
                $s = Sorteo::create([
                    'fecha' => new Carbon($request->input('fecha')),
                    'cantidad_sorteos' => $request->input('cantidad_sorteos'),
                    'cooperativa_id' => $request->input('cooperativa_id'),
                    'creador_id' => $user->_id,
                    'estado' => 'A',
                    'cabecera' => $request->input('cabecera')
                ]);
                $sorteos = $request->input('sorteos');
                foreach ($sorteos as $sorteo) {
                    $unidadesArray = array();
                    $unidades = $sorteo['unidades'];
                    foreach ($unidades as $unidad)
                        array_push($unidadesArray, [
                            'id' => $unidad['id'],
                            'descripcion' => $unidad['descripcion'],
                            'hora' => $unidad['hora']
                        ]);
                    DetalleSorteo::create([
                        'intervalo' => $sorteo['intervalo'],
                        'hora_inicio' => $sorteo['hora_inicio'],
                        'numero_unidades' => count($unidadesArray),
                        'unidades' => $unidadesArray,
                        'sorteo_id' => $s->_id
                    ]);
                }
                return $s;
            }
        } else
            return view('panel.error', ['mensaje_acceso' => 'Usted no tiene acceso a este módulo.']);
    }
    public function cargarSorteo(Request $request)
    {
        $this->validate($request, [
            'fecha' => 'required|date',
            'cooperativa_id' => 'required'
        ]);
        $fecha = new Carbon($request->input('fecha'));
        $sorteo = Sorteo::with('sorteos')->where('cooperativa_id', $request->input('cooperativa_id'))->where('fecha', $fecha)->where('estado', 'A')->first();
        return response()->json([
            'sorteo' => $sorteo
        ]);
    }
    public function eliminarSorteo(Request $request, $id)
    {
        $sorteo = Sorteo::findOrFail($id);
        $sorteo->estado = 'I';
        $user = $request->user();
        $sorteo->eliminador_id = $user->_id;
        $sorteo->save();
        return $sorteo;
    }
    public function imprimir(Request $request)
    {
        $this->validate($request, [
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'cabecera' => 'nullable'
        ]);
        $desde = new Carbon($request->input('desde'));
        $hasta = new Carbon($request->input('hasta'));
        $sorteos = Sorteo::where('fecha', '>=', $desde)
            ->where('fecha', '<=', $hasta)
            ->where('estado', 'A')
            ->get();
        if ($sorteos->count() > 0)
            $cooperativa = $sorteos[0]->cooperativa;
        else
            $cooperativa = null;
        $cabecera = $request->input('cabecera');
        return Excel::create('Reporte de sorteos ' . date('YmdHis'), function ($excel) use ($cabecera, $sorteos, $desde, $hasta, $cooperativa) {
            $excel->sheet('Reporte', function ($sheet) use ($cabecera, $sorteos, $desde, $hasta, $cooperativa) {
                $sheet->loadView('panel.sorteos.sorteos-excel', [
                    'sorteos' => $sorteos,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'cabecera' => $cabecera,
                    'cooperativa' => $cooperativa
                ]);
            });
        })->download();
    }
}
