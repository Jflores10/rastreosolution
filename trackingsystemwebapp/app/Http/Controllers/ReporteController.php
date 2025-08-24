<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\User;
use App\Ruta;
use App\Unidad;
use App\Despacho;
use Auth;
use MongoDB\BSON\UTCDateTime;
use Carbon\Carbon;
use App\PuntoControl;
use Excel;

class ReporteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1) {
            $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->get();
        } else
            $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->where(
                '_id',
                $user->cooperativa_id
            )->get();
        return view('panel.reportes', ['cooperativas' => $cooperativas]);
    }

    public function cargar($id)
    {
        $rutas = Ruta::where('cooperativa_id', $id)->where('tipo_ruta', '!=', 'P')->get(); //->where('estado','A')
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 4 || $user->tipo_usuario->valor == 5) {
            $unidades_pertenecientes = Auth::user()->unidades_pertenecientes;
            $unidades = Unidad::orderBy('descripcion', 'asc')->whereIn('_id', $unidades_pertenecientes)->get();
        } else {
            $unidades = Unidad::where('cooperativa_id', $id)->orderBy('descripcion', 'asc')->get();
        }
        return response()->json(['rutas' => $rutas, 'unidades' => $unidades]);
    }

    public function search(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|exists:rutas,_id',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        //$d = new Carbon($request->input('desde') . ' 00:00:00');
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        //$d = new UTCDateTime(($desde->getTimestamp()) * 1000);
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        //$h = new UTCDateTime(($hasta->getTimestamp()) * 1000);
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $reportes = array();
        foreach ($unidad_id as $id) {
            foreach ($rutas as $r) {
                $ruta = Ruta::findOrFail($r);
                $unidad = Unidad::findOrFail($id);
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('ruta_id', $ruta->_id)->where('unidad_id', $id)->get();
                if ($despachos->count() > 0) {
                    array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
                }
            }
        }
        $user = Auth::user();
        if ($user->tipo_usuario->valor == 1) {
            $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->get();
        } else
            $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where(
                '_id',
                $user->cooperativa_id
            )->get();
        return view('panel.table-report', [
            'cooperativas' => $cooperativas, 'reportes' => $reportes, 'unidades' => $unidad_id,
            'rutas' => $rutas, 'cooperativa_id' => $request->input('cooperativa_id'),
            'desde' => $request->input('desde'), 'hasta' => $request->input('hasta')
        ]);
    }

    public function generarReporteDiario(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        Excel::create('Reportes por rutas', function ($excel) use ($unidad_id, $rutas, $d, $h) {
            foreach ($rutas as $ruta) {
                $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)->where(
                    'ruta_id',
                    $ruta
                )->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                $r = Ruta::find($ruta);
                if (isset($r)) {
                    $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']', '-');
                    $title = $r->descripcion;
                    $title = str_replace($invalidCharacters, '', $title);
                    $max = 31;
                    if (strlen($title) > $max)
                        $title = substr($title, 0, $max);
                    $excel->sheet($title, function ($sheet) use ($r, $title, $despachos, $unidad_id) {
                        $sheet->setTitle($title);
                        $puntos_control = array();
                        foreach ($r->puntos_control as $punto) {
                            $p = PuntoControl::find($punto['id']);
                            if (isset($p))
                                array_push($puntos_control, $p);
                        }
                        foreach ($unidad_id as $id) {
                            $vuelta = 0;
                            foreach ($despachos as &$despacho) {
                                if ($id == $despacho->unidad_id)
                                    $despacho->vuelta = $vuelta++;
                            }
                        }
                        $sheet->loadView('panel.reporte-diario', [
                            'ruta' => $r, 'despachos' => $despachos,
                            'puntos_control' => $puntos_control,
                            'unidades' => $unidad_id, 'title' => $title
                        ]);
                    });
                }
            }
            $excel->download('xls');
        });
    }

    public function generarReportePorUnidad(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $un = Unidad::whereIn('_id', $unidad_id)->first();
        $cooperativa = null;
        if (isset($un))
            $cooperativa = $un->cooperativa;
        $reportes = array();
        foreach ($unidad_id as $id) {
            foreach ($rutas as $r) {
                $ruta = Ruta::findOrFail($r);
                $unidad = Unidad::with('user')->findOrFail($id);
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('ruta_id', $ruta->_id)->where('unidad_id', $id)->get();
                if ($despachos->count() > 0) {
                    array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
                }
            }
        }
        Excel::create('Multas', function ($excel) use ($reportes, $cooperativa) {
            foreach ($reportes as $reporte) {
                $array = array($reporte);
                $excel->sheet($reporte["unidad"]["descripcion"], function ($sheet) use ($array, $cooperativa) {
                    $sheet->setTitle($array[0]["unidad"]["descripcion"]);
                    $sheet->loadView('panel.reporte-multas', ['reportes' => $array, 'cooperativa' => $cooperativa]);
                });
            }
            $excel->download('xls');
        });
    }
    public function generarReporteUnaHoja(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = Ruta::findOrFail($request->input('ruta_id'));
        $reportes = array();
        foreach ($unidad_id as $id) {
            foreach ($rutas as $ruta) {
                $unidad = Unidad::findOrFail($id);
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('ruta_id', $ruta->_id)->where('unidad_id', $id)->get();
                if ($despachos->count() > 0) {
                    array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
                }
            }
        }
        Excel::create('Reporte', function ($excel) use ($reportes) {
            $excel->sheet('Reporte', function ($sheet) use ($reportes) {
                $sheet->loadView('panel.reporte-hoja-excel', ['reportes' => $reportes]);
            });
            $excel->download('xls');
        });
    }

    public function reporteGeneral(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)->whereIn(
            'ruta_id',
            $rutas
        )->where('fecha', '>=', $d)->where('fecha', '<=', $h)->get();
        Excel::create('Reporte', function ($excel) use ($despachos) {
            $excel->sheet('Despachos', function ($sheet) use ($despachos) {
                $sheet->setTitle('Reportes');
                $sheet->loadView('panel.despacho-una-hoja', ['despachos' => $despachos]);
            });
            $excel->download('xls');
        });
    }

    public function reportePorRutas(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $array_ruta = array();

        foreach ($rutas as $ruta) {
            $r = Ruta::find($ruta);
            if (isset($r)) {
                if ($r->tipo_ruta == 'I') {
                    array_push($array_ruta, $ruta);
                } else {
                    if (!in_array($r->ruta_padre, $array_ruta)) {
                        array_push($array_ruta, $r->ruta_padre);
                    }
                }
            }
        }

        Excel::create('Reportes por rutas', function ($excel) use ($unidad_id, $array_ruta, $d, $h) {
            foreach ($array_ruta as $ruta) {

                $r = Ruta::find($ruta);
                if (isset($r)) {
                    if ($r->tipo_ruta == 'I') {
                        $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)->where(
                            'ruta_id',
                            $ruta
                        )->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                    } else {
                        $rutas_hijas = Ruta::where('ruta_padre', $ruta)->get();
                        $ruta_array = $rutas_hijas->pluck('_id')->all();

                        if (isset($rutas_hijas)) {
                            $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)
                                ->whereIn('ruta_id', $ruta_array)
                                ->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                        }
                    }
                }



                if (isset($r)) {
                    $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']', '-');
                    $title = $r->descripcion;
                    $title = str_replace($invalidCharacters, '', $title);
                    $max = 31;
                    if (strlen($title) > $max)
                        $title = substr($title, 0, $max);

                    $excel->sheet($title, function ($sheet) use ($r, $title, $despachos, $unidad_id) {
                        $sheet->setTitle($title);
                        $fechas = array();
                        foreach ($despachos as $despacho) {
                            $fec = $despacho->fecha;
                            date_add($fec, date_interval_create_from_date_string('5 hours'));
                            if (!in_array($fec->format('Y-m-d'), $fechas))
                                array_push($fechas, $fec->format('Y-m-d'));
                        }
                        $puntos_control = array();
                        foreach ($r->puntos_control as $punto) {
                            $p = PuntoControl::select('descripcion')->find($punto['id']);
                            if (isset($p))
                                array_push($puntos_control, $p);
                        }
                        foreach ($unidad_id as $id) {
                            $vuelta = 0;
                            $currentDate = null;
                            foreach ($despachos as &$despacho) {
                                if ($id == $despacho->unidad_id)
                                {
                                    if ($currentDate == null) {
                                        $currentDate = $despacho->fecha;
                                    }
                                    if ($currentDate->format('Y-m-d') == $despacho->fecha->format('Y-m-d')) {
                                        $despacho->vuelta = $vuelta++;
                                    }
                                    else {
                                        $currentDate = $despacho->fecha;
                                        $vuelta = 0;
                                        $despacho->vuelta = $vuelta++;
                                    }
                                }
                            }
                        }
                        $vueltas = 0;
                        foreach ($despachos as $despacho)
                            if ($despacho->vuelta > $vueltas)
                                $vueltas = $despacho->vuelta;
                        $sheet->loadView('panel.reporte-rutas', [
                            'ruta' => $r, 'despachos' => $despachos,
                            'fechas' => $fechas, 'puntos_control' => $puntos_control,
                            'unidades' => $unidad_id, 'vueltas' => $vueltas, 'title' => $title
                        ]);
                    });
                }
            }
            $excel->download('xls');
        });
    }

    public function reportePorRutasNoVueltas(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $array_ruta = array();

        foreach ($rutas as $ruta) {
            $r = Ruta::find($ruta);
            if (isset($r)) {
                if ($r->tipo_ruta == 'I') {
                    array_push($array_ruta, $ruta);
                } else {
                    if (!in_array($r->ruta_padre, $array_ruta)) {
                        array_push($array_ruta, $r->ruta_padre);
                    }
                }
            }
        }

        Excel::create('Reportes por rutas', function ($excel) use ($unidad_id, $array_ruta, $d, $h) {
            foreach ($array_ruta as $ruta) {

                $r = Ruta::find($ruta);
                if (isset($r)) {
                    if ($r->tipo_ruta == 'I') {
                        $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)->where(
                            'ruta_id',
                            $ruta
                        )->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                    } else {
                        $rutas_hijas = Ruta::where('ruta_padre', $ruta)->get();
                        $ruta_array = $rutas_hijas->pluck('_id')->all();

                        if (isset($rutas_hijas)) {
                            $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)
                                ->whereIn('ruta_id', $ruta_array)
                                ->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                        }
                    }
                }



                if (isset($r)) {
                    $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']', '-');
                    $title = $r->descripcion;
                    $title = str_replace($invalidCharacters, '', $title);
                    $max = 31;
                    if (strlen($title) > $max)
                        $title = substr($title, 0, $max);

                    $excel->sheet($title, function ($sheet) use ($r, $title, $despachos, $unidad_id) {
                        $sheet->setTitle($title);
                        $fechas = array();
                        foreach ($despachos as $despacho) {
                            $fec = $despacho->fecha;
                            date_add($fec, date_interval_create_from_date_string('5 hours'));
                            if (!in_array($fec->format('Y-m-d'), $fechas))
                                array_push($fechas, $fec->format('Y-m-d'));
                        }
                        $puntos_control = array();
                        foreach ($r->puntos_control as $punto) {
                            $p = PuntoControl::select('descripcion')->find($punto['id']);
                            if (isset($p))
                                array_push($puntos_control, $p);
                        }

                        /*dd(
                            [
                                'ruta' => $r, 'despachos' => $despachos,
                                'fechas' => $fechas, 'puntos_control' => $puntos_control,
                                'unidades' => $unidad_id, 'title' => $title
                            ]
                        );*/

                        $sheet->loadView('panel.reporte-rutas-novueltas', [
                            'ruta' => $r, 'despachos' => $despachos,
                            'fechas' => $fechas, 'puntos_control' => $puntos_control,
                            'unidades' => $unidad_id, 'title' => $title
                        ]);
                    });
                }
            }
            $excel->download('xls');
        });
    }

    public function reportePorRutasCobros(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $array_ruta = array();

        foreach ($rutas as $ruta) {
            $r = Ruta::find($ruta);
            if (isset($r)) {
                if ($r->tipo_ruta == 'I') {
                    array_push($array_ruta, $ruta);
                } else {
                    if (!in_array($r->ruta_padre, $array_ruta)) {
                        array_push($array_ruta, $r->ruta_padre);
                    }
                }
            }
        }

        $array_multas = array();
        $item = ["id" => null, "unidad" => null, "salida" => null, "multa" => null, "conductor" => null];

        $array_rutas_multas = array();
        $item_rutas = ["id" => null, "inicio" => null, "fin" => null, "ruta" => null, "multas" => null];

        foreach ($array_ruta as $ruta) {

            $r = Ruta::find($ruta);
            if (isset($r)) {
                if ($r->tipo_ruta == 'I') {
                    $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)->where(
                        'ruta_id',
                        $ruta
                    )->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                } else {
                    $rutas_hijas = Ruta::where('ruta_padre', $ruta)->get();
                    $ruta_array = $rutas_hijas->pluck('_id')->all();

                    if (isset($rutas_hijas)) {
                        $despachos = Despacho::orderBy('fecha', 'asc')->whereIn('unidad_id', $unidad_id)
                            ->whereIn('ruta_id', $ruta_array)
                            ->where('fecha', '>=', $d)->where('fecha', '<=', $h)->where('estado', 'C')->get();
                    }
                }
                $item_rutas["id"] = $r->_id;
                $item_rutas["inicio"] = $request->input('desde');
                $item_rutas["fin"] = $request->input('hasta');
                $item_rutas["ruta"] = $r->descripcion;

                $array_multas = array();
                foreach ($despachos as $despacho) {
                    $fec = $despacho->fecha;
                    date_add($fec, date_interval_create_from_date_string('5 hours'));
                    $item["id"] = $despacho->_id;
                    $item["salida"] = $fec->format('H:i');
                    $item["unidad"] = $despacho->unidad->descripcion;
                    $item["conductor"] = $despacho->conductor->nombre;
                    $item["multa"] = (isset($despacho->multa) ? round($despacho->multa, 2) : 0.0);

                    array_push($array_multas, $item);
                }

                $item_rutas["multas"] = $array_multas;

                array_push($array_rutas_multas, $item_rutas);
            }
        }

        return response()->json($array_rutas_multas);
    }

    public function generarReporte(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'filtros' => 'required|array'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('5 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas = $request->input('ruta_id');
        $filtros = $request->input('filtros');
        $reportes = array();
        foreach ($unidad_id as $id) {
            foreach ($rutas as $r) {
                $ruta = Ruta::findOrFail($r);
                $unidad = Unidad::findOrFail($id);
                $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                    'fecha',
                    '>=',
                    $d
                )->where('fecha', '<=', $h)->where('ruta_id', $ruta->_id)->where('unidad_id', $id)->get();
                if ($despachos->count() > 0) {
                    array_push($reportes, [
                        'unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos,
                        'filtros' => $filtros
                    ]);
                }
            }
        }
        Excel::create('Reporte', function ($excel) use ($reportes) {
            foreach ($reportes as $reporte) {
                $array = array($reporte);
                $excel->sheet($reporte["unidad"]["descripcion"], function ($sheet) use ($array) {
                    $sheet->setTitle($array[0]["unidad"]["descripcion"]);
                    $sheet->loadView('panel.reporte-hoja-excel', ['reportes' => $array]);
                });
            }
            $excel->download('xls');
        });
    }

    public function generarReportePorUnidad_DosUnidades(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required|array',
            'ruta_id' => 'required|array',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        // date_sub($d, date_interval_create_from_date_string('2 hours'));
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        // date_sub($h, date_interval_create_from_date_string('5 hours'));
        $d = new Carbon($request->input('desde'));
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        $unidad_id = $request->input('unidad_id');
        $rutas_depur = $request->input('ruta_id');

        $rutas = array();
        foreach ($rutas_depur as $ruta) {
            $r = Ruta::find($ruta);
            if ($r->tipo_ruta != 'P') {
                array_push($rutas, $ruta);
            } else {
                $ruta_hijas = Ruta::where('ruta_padre', $r->_id)->get();
                foreach ($ruta_hijas as $hijas)
                    array_push($rutas, $hijas->_id);
            }
        }

        $un = Unidad::whereIn('_id', $unidad_id)->first();
        $unidad_id = Unidad::orderBy('descripcion')->whereIn('_id', $unidad_id)->get()->pluck('_id');
        $cooperativa = null;
        if (isset($un))
            $cooperativa = $un->cooperativa;
        $reportes = array();
        foreach ($unidad_id as $id) {
            $rutasOcupadas = array();
            foreach ($rutas as $r) {
                $ocupada = false;
                foreach ($rutasOcupadas as $rutaOcupada) {
                    if ($rutaOcupada == $r) {
                        $ocupada = true;
                        break;
                    }
                }
                if (!$ocupada) {
                    $ruta = Ruta::findOrFail($r);
                    $unidad = Unidad::with('user')->findOrFail($id);
                    if (isset($ruta)) {
                        $rutasId = array();
                        array_push($rutasId, $ruta->id);
                        foreach ($rutas as $rutaAux) {
                            $rAux = Ruta::find($rutaAux);
                            if (isset($rAux)) {
                                if ($ruta->_id != $rAux->_id && is_array($ruta->puntos_control) && is_array($rAux->puntos_control) && count($ruta->puntos_control) === count($rAux->puntos_control)) {
                                    $rutasCoinciden = true;
                                    for ($i = 0; $i < count($ruta->puntos_control); $i++) {
                                        if ($ruta->puntos_control[$i]['id'] != $rAux->puntos_control[$i]['id']) {
                                            $rutasCoinciden = false;
                                            break;
                                        }
                                    }
                                    if ($rutasCoinciden) {
                                        array_push($rutasId, $rAux->_id);
                                        array_push($rutasOcupadas, $rAux->_id);
                                    }
                                }
                            }
                        }
                        $user = Auth::user();
                        if ($user->tipo_usuario->valor == 5) {
                            $unidades_pertenecientes = $user->unidades_pertenecientes;
                            $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                                'fecha',
                                '>=',
                                $d
                            )->where('fecha', '<=', $h)->whereIn('unidad_id', $unidades_pertenecientes)
                                ->whereIn('ruta_id', $rutasId)->where('unidad_id', $id)->get();
                        } else {
                            $despachos = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where(
                                'fecha',
                                '>=',
                                $d
                            )->where('fecha', '<=', $h)->whereIn('ruta_id', $rutasId)->where('unidad_id', $id)->get();
                        }

                        if ($despachos->count() > 0)
                            array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
                    }
                }
            }
        }

        if (count($reportes) > 0) {
            Excel::create('Multas', function ($excel) use ($reportes, $cooperativa, $request) {
                $arrlength = count($reportes) - 1;
                $arrayindex = 0;
                $arrayindex_tmp = 0;
                $index_2 = true;
                $array = array();
                foreach ($reportes as $reporte) {
                    if (($arrayindex + 1) <= $arrlength) {
                        if ($arrayindex_tmp < 4) {
                            array_push($array, $reporte);
                            $arrayindex_tmp = $arrayindex_tmp + 1;
                        } else {
                            $arrayindex_tmp = 0;
                            $index_2 = false;
                        }
                    } else {
                        /**********EXCEL */
                        array_push($array, $reporte);
                        $unidades = "";
                        foreach ($array as $array_unique) {
                            $unidades = $unidades . "-" . $array_unique["unidad"]["descripcion"];
                        }
                        if (strlen($unidades) > 30) {
                            $unidades = substr($unidades, 0, 30);
                        }
                        $excel->sheet($unidades, function ($sheet) use ($request, $array, $cooperativa, $unidades) {
                            $sheet->setTitle($unidades);

                            $sheet->loadView('panel.reporte-multas-max-dos-unidades', [
                                'reportes' => $array, 'cooperativa' => $cooperativa,
                                'desde' => new Carbon($request->input('desde'))
                            ]);

                            // $sheet->protect('password.1');
                        });

                        unset($array);
                        $array = array();
                        $index_2 = true;
                    }

                    $arrayindex = $arrayindex + 1;
                    if ($arrayindex_tmp == 4) {
                        $arrayindex_tmp = 0;
                        // array_push($array, $reporte);
                        $descripcion = $array[0]["unidad"]["descripcion"] . "-" . $array[1]["unidad"]["descripcion"]
                            . "-" . $array[2]["unidad"]["descripcion"] . "-" . $array[3]["unidad"]["descripcion"];
                        if (strlen($descripcion) > 30) {
                            $descripcion = substr($descripcion, 0, 30);
                        }
                        $excel->sheet($descripcion, function ($sheet) use ($request, $array, $cooperativa, $descripcion) {
                            $sheet->setTitle($descripcion);

                            $sheet->loadView('panel.reporte-multas-max-dos-unidades', [
                                'reportes' => $array, 'cooperativa' => $cooperativa,
                                'desde' => new Carbon($request->input('desde'))
                            ]);

                            // $sheet->protect('password.1');
                        });
                        unset($array);
                        $array = array();
                    }
                }
                $excel->download('xls');
            });
        } else {
            return redirect()->back()->withInput();
        }
    }

    public function searchTicketMulta(Request $request)
    {
        set_time_limit(0);
        $this->validate($request, [
            'unidad_id' => 'required',
            'ruta_id' => 'required',
            'ruta_padre' => 'required',
            'desde' => 'required|date',
            'hasta' => 'required|date'
        ]);
        // $d = new Carbon($request->input('desde') . ' 00:00:00');
        $d = new Carbon($request->input('desde') );
        date_sub($d, date_interval_create_from_date_string('5 hours'));
        //$d = new UTCDateTime(($desde->getTimestamp()) * 1000);
        // $h = new Carbon($request->input('hasta') . ' 23:59:59');
        $h = new Carbon($request->input('hasta'));
        date_sub($h, date_interval_create_from_date_string('5 hours'));
        //$h = new UTCDateTime(($hasta->getTimestamp()) * 1000);
        $unidad_id = $request->input('unidad_id');
        $ruta_id = $request->input('ruta_id');
        $ruta_padre = $request->input('ruta_padre');
        $reportes = array();

        if ($ruta_padre == "S") {
            $rutas = array();
            $rutaSelect = "";
            $r = Ruta::find($ruta_id);
            if ($r->tipo_ruta != 'H') {
                array_push($rutas, $ruta_id);
                $rutaSelect = $ruta_id;
            } else {
                $ruta_hijas = Ruta::where('ruta_padre', $r->rutapadre->_id)->get();
                $rutaSelect = $r->rutapadre->_id;
                foreach ($ruta_hijas as $hijas)
                    array_push($rutas, $hijas->_id);
            }

            $ruta = Ruta::findOrFail($rutaSelect);

            $unidad = Unidad::findOrFail($unidad_id);
            $despachos = Despacho::with('conductor')->orderBy('fecha', 'asc')->where('estado', 'C')->where(
                'fecha',
                '>=',
                $d
            )->where('fecha', '<=', $h)->whereIn('ruta_id', $rutas)->where('unidad_id', $unidad_id)->get();
            if ($despachos->count() > 0) {
                array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
            }

            return response()->json(['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
        } else {

            $ruta = Ruta::findOrFail($ruta_id);
            $unidad = Unidad::findOrFail($unidad_id);
            $despachos = Despacho::with('conductor')->orderBy('fecha', 'asc')->where('estado', 'C')->where(
                'fecha',
                '>=',
                $d
            )->where('fecha', '<=', $h)->where('ruta_id', $ruta->_id)->where('unidad_id', $unidad_id)->get();
            if ($despachos->count() > 0) {
                array_push($reportes, ['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
            }

            // $ultimo = Despacho::orderBy('fecha', 'desc')->where('estado', 'C')->where('fecha', '<', $h)->where(
            //     'fecha',
            //     '>',
            //     $d
            // )->where('unidad_id', $unidad_id)->first();

            /*return view('panel.table-report', [
            'cooperativas' => $cooperativas, 'reportes' => $reportes, 'unidades' => $unidad_id,
            'rutas' => $rutas, 'cooperativa_id' => $request->input('cooperativa_id'),
            'desde' => $request->input('desde'), 'hasta' => $request->input('hasta')
        ]);*/

            return response()->json(['unidad' => $unidad, 'ruta' => $ruta, 'despachos' => $despachos]);
        }
        // 'ultimo' => $ultimo]);
    }
}
