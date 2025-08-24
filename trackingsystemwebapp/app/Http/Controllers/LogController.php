<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LOGATMDESPACHOS;
use App\LogErrorATMWS;
use App\LogPuntoVirtual;
use Carbon\Carbon;
use PDF;
class LogController extends Controller
{
    public function index() {
        return view('panel.logs.lista-logs');
    }
    public function search(Request $request) {
        $this->validate($request, [
            'tipo' => 'required|max:1',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        $desde = new Carbon($request->input('desde'));
        $hasta = new Carbon($request->input('hasta'));
        $tipo = $request->input('tipo');
        switch($tipo) {
            case 'D':
                $logs = LOGATMDESPACHOS::where('fecha', '>=', $desde)->where('fecha', '<=', $hasta);
                break;
            case 'T':
                $logs = LogErrorATMWS::where('fecha_error', '>=', $desde)->where('fecha_error', '<=', $hasta);
                break;
            case 'V':
                $logs = LogPuntoVirtual::where('fecha_error', '>=', $desde)->where('fecha_error', '<=', $hasta);
                break;
            default:
                abort(404);
        }
        if ($request->input('exportar') != null) 
            return PDF::loadView('panel.logs.pdf-logs', [
                'logs' => $logs->get(),
                'desde' => $request->input('desde'),
                'hasta' => $request->input('hasta'),
                'tipo' => $tipo
            ])->stream();
        else {
            $logs = $logs->paginate(15);
            $logs->setPath($request->fullUrl());
            return view('panel.logs.lista-logs', ['desde' => $request->input('desde'), 'hasta' => $request->input('hasta'), 'tipo' => $tipo, 'logs' => $logs]);
        }
    }
}
