<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PuntoVirtual;
use App\Cooperativa;
class PuntoVirtualController extends Controller
{
    public function index()
    {
        return view('panel.puntos-virtuales.lista-puntos-virtuales', ['puntosVirtuales' => PuntoVirtual::permitido()->orderBy('descripcion', 
        'asc')->where('estado', 'A')->get(), 'cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 
        'asc')->where('estado', 'A')->get()]);
    }
    public function search(Request $request) 
    {
        $this->validate($request, [
            'cooperativa_id' => 'required|exists:cooperativas,_id',
            'consulta' => 'nullable|max:255'
        ]);
        $cooperativa = $request->input('cooperativa_id');
        $puntosVirtuales = PuntoVirtual::permitido()->orderBy('descripcion')->where('estado', 'A')->where('cooperativa_id', 
        $cooperativa);
        $consulta = $request->input('consulta');
        if (isset($consulta) && $consulta != '')
            $puntosVirtuales->where('descripcion', 'like', "%$consulta%");
        return view('panel.puntos-virtuales.lista-puntos-virtuales', ['puntosVirtuales' => $puntosVirtuales->get(), 
        'cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 'asc')->where('estado', 'A')->get(),
        'cooperativa_id' => $cooperativa, 'consulta' => $consulta]);
    }
    public function create()
    {
        return view('panel.puntos-virtuales.crear-editar-punto-virtual', ['cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 
        'asc')->where('estado', 'A')->get()]);
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'descripcion' => 'required|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'radio' => 'required|numeric',
            'cooperativa_id' => 'required|exists:cooperativas,_id',
            'tipo_posicion' => 'required|max:1',
            'clave_equipo' => 'required|max:255',
            'pista' => 'required|max:255'
        ]);
        $puntoVirtual = PuntoVirtual::create([
            'descripcion' => $request->input('descripcion'),
            'latitud' => $request->input('latitud'),
            'longitud' => $request->input('longitud'),
            'radio' => $request->input('radio'),
            'estado' => 'A',
            'cooperativa_id' => $request->input('cooperativa_id'),
            'tipo_posicion' => $request->input('tipo_posicion'),
            'clave_equipo' => $request->input('clave_equipo'),
            'pista' => $request->input('pista')
        ]);
        return redirect(route('puntos-virtuales.index'));
    }
    public function edit($id)
    {
        return view('panel.puntos-virtuales.crear-editar-punto-virtual', ['puntoVirtual' => PuntoVirtual::permitido()->findOrFail($id),
        'cooperativas' => Cooperativa::permitida()->orderBy('descripcion', 
        'asc')->where('estado', 'A')->get()]);
    }
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'descripcion' => 'required|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'radio' => 'required|numeric',
            'cooperativa_id' => 'required|exists:cooperativas,_id',
            'clave_equipo' => 'required|max:255',
            'pista' => 'required|max:255'
        ]);
        $puntoVirtual = PuntoVirtual::permitido()->findOrFail($id);
        $puntoVirtual->descripcion = $request->input('descripcion');
        $puntoVirtual->latitud = $request->input('latitud');
        $puntoVirtual->longitud = $request->input('longitud');
        $puntoVirtual->radio = $request->input('radio');
        $puntoVirtual->cooperativa_id = $request->input('cooperativa_id');
        $puntoVirtual->tipo_posicion = $request->input('tipo_posicion');
        $puntoVirtual->pista = $request->input('pista');
        $puntoVirtual->clave_equipo = $request->input('clave_equipo');
        $puntoVirtual->save();
        return redirect(route('puntos-virtuales.index'));
    }
    public function destroy($id)
    {
        $puntoVirtual = PuntoVirtual::permitido()->findOrFail($id);
        $puntoVirtual->estado = 'I';
        $puntoVirtual->save();
        return redirect(route('puntos-virtuales.index'));
    }
}
