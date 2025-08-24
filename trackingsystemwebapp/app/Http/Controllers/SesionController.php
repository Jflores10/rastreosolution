<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\SesionUsuario;
use PDF;
use Carbon\Carbon;
use Excel;
use Auth;

class SesionController extends Controller
{
    public function index(Request $request) {
        if (Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2') {
            $this->validate($request, [
                'desde' => 'required_if:tipo,P|date',
                'hasta' => 'required_if:tipo,P|date|after:desde',
                'cooperativa' => 'nullable|exists:cooperativas,_id'
            ]);
            $tipo = $request->input('tipo');
            if (!isset($tipo))
                $tipo = 'H';
            if ($tipo === 'H') {
                $desde = Carbon::today();
                $hasta = Carbon::tomorrow();
            }
            else if ($tipo === 'A') {
                $desde = Carbon::yesterday();
                $hasta = Carbon::today();
            }
            else  {
                $desde = new Carbon($request->input('desde'));
                $hasta = new Carbon($request->input('hasta'));
            }
            $sesiones = SesionUsuario::orderBy('fecha_sesion', 'desc')->where('fecha_sesion', '>=', $desde)->where('fecha_sesion', '<=', 
            $hasta);
            if ($request->input('cooperativa') !== null && $request->input('cooperativa') !== '')
                $sesiones->with(['usuario' => function ($query) use($request) {
                    $query->where('cooperativa_id', $request->input('cooperativa'))->orWhere('cooperativa_id', 
                        'all', [$request->input('cooperativa')]);
                }]);
                else{
                    if(Auth::user()->tipo_usuario->valor !='1')
                        $sesiones->with(['usuario' => function ($query) use($request) {
                            $query->where('cooperativa_id', Auth::user()->cooperativa_id)->orWhere('cooperativa_id', 
                                    'all', [Auth::user()->cooperativa_id]);
                            }]);
                }
            set_time_limit(0);
            if ($request->has('pdf')) {
                return PDF::loadView('panel.usuarios.sesiones-pdf', [
                    'sesiones' => $sesiones->get(),
                    'desde' => $desde,
                    'hasta' => $hasta
                ])->stream();
            }
            else if ($request->has('excel')) {
                Excel::create('Sesiones', function ($excel) use($sesiones, $desde, $hasta){
                    $excel->sheet('Consulta de sesiones', function ($sheet) use($sesiones, $desde, $hasta) {
                        $sheet->loadView('panel.usuarios.sesiones-excel', [
                            'sesiones' => $sesiones->get(),
                            'desde' => $desde,
                            'hasta' => $hasta
                        ]);
                    });
                })->export('xlsx');
            }
            else {
                $user = Auth::user();
                if ($user->tipo_usuario->valor == 1) //Superadmin
                {
                    $cooperativas = Cooperativa::orderBy('descripcion')->activa()->get();
                    return view('panel.usuarios.sesiones', [
                        'cooperativas' => $cooperativas,
                        'sesiones' => $sesiones->get(),
                        'desde' => $desde,
                        'hasta' => $hasta,
                        'tipo' => $tipo,
                        'cooperativaId' => $request->input('cooperativa')
                    ]);
                }
                else if ($user->tipo_usuario->valor == 2)//Administrador
                    {
                        $cooperativas_ = Cooperativa::permitida()->get();
                        return view('panel.usuarios.sesiones', [
                            'cooperativas' => $cooperativas_,
                            'sesiones' => $sesiones->get(),
                            'desde' => $desde,
                            'hasta' => $hasta,
                            'tipo' => $tipo,
                            'cooperativaId' => $request->input('cooperativa')
                        ]);
                    }
                    else
                        return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
            }
                // return view('panel.usuarios.sesiones', [
                //     'cooperativas' => Cooperativa::orderBy('descripcion')->permitida()->activa()->get(),
                //     'sesiones' => $sesiones->get(),
                //     'desde' => $desde,
                //     'hasta' => $hasta,
                //     'tipo' => $tipo,
                //     'cooperativaId' => $request->input('cooperativa')
                // ]);
        } 
        else 
            abort(404);
        
    }
}
