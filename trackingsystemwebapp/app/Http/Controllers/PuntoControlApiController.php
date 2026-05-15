<?php

namespace App\Http\Controllers;

use App\PuntoControl;
use App\TipoUsuario;
use Auth;
use Illuminate\Http\Request;
use Validator;

/**
 * API REST de puntos de control (v2). Lógica alineada con PuntoControlController (panel web).
 */
class PuntoControlApiController extends Controller
{
    /**
     * Tipo 1 (superadmin): puede usar cualquier cooperativa_id permitida en el request.
     * Resto: solo la cooperativa del usuario autenticado.
     */
    private function usuarioPuedeUsarCooperativa($cooperativaId)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        $tipo = (int) $user->tipo_usuario->valor;
        if ($tipo === 1) {
            return true;
        }

        return (string) $user->cooperativa_id === (string) $cooperativaId;
    }

    /**
     * El punto pertenece a una cooperativa a la que el usuario tiene acceso.
     */
    private function usuarioPuedeAccederPunto(PuntoControl $punto)
    {
        return $this->usuarioPuedeUsarCooperativa($punto->cooperativa_id);
    }

    /**
     * Guardar un punto de control (misma validación y payload que PuntoControlController::store).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'cooperativa_id' => 'required',
            'pdi' => 'required',
            'otro' => 'required',
            'entrada' => 'nullable|required_if:otro,true',
            'salida' => 'nullable|required_if:otro,true',
            'tipo_mar' => 'required|in:1,2',
            'latitud' => 'required_if:tipo_mar,1|numeric|nullable',
            'longitud' => 'required_if:tipo_mar,1|numeric|nullable',
            'radio' => 'required_if:tipo_mar,1|numeric|nullable',
            'poligono' => 'required_if:tipo_mar,2|array|nullable',
            'poligono.*.lat' => 'required_if:tipo_mar,2|numeric|nullable',
            'poligono.*.lng' => 'required_if:tipo_mar,2|numeric|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $cooperativaId = $request->input('cooperativa_id');
        if (!$this->usuarioPuedeUsarCooperativa($cooperativaId)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['cooperativa_id' => ['No autorizado para esta cooperativa.']],
            ], 403);
        }

        $puntoData = [
            'descripcion' => $request->input('descripcion'),
            'cooperativa_id' => $cooperativaId,
            'estado' => $request->input('estado'),
            'pdi' => $request->input('pdi'),
            'creador_id' => Auth::user()->_id,
            'modificador_id' => Auth::user()->_id,
            'entrada' => $request->input('entrada'),
            'salida' => $request->input('salida'),
            'mt' => $request->input('otro'),
            'estado_exportacion' => 'P',
            'tipo_mar' => $request->input('tipo_mar'),
        ];

        if ($request->input('tipo_mar') == '1') {
            $puntoData['latitud'] = $request->input('latitud');
            $puntoData['longitud'] = $request->input('longitud');
            $puntoData['radio'] = $request->input('radio');
            $puntoData['poligono'] = [];
        } else {
            $puntoData['latitud'] = null;
            $puntoData['longitud'] = null;
            $puntoData['radio'] = null;
            $puntoData['poligono'] = $request->input('poligono');
        }

        $puntoData['pto_imaginario'] = $request->input('pto_imaginario') == 'true' ? true : false;

        $punto_control = PuntoControl::create($puntoData);

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'punto_control' => $punto_control,
        ]);
    }

    /**
     * Actualizar un punto de control (POST …/puntos-control/{id}; misma lógica que PuntoControlController::update).
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'cooperativa_id' => 'required',
            'pdi' => 'required',
            'otro' => 'required',
            'entrada' => 'nullable|required_if:otro,true',
            'salida' => 'nullable|required_if:otro,true',
            'tipo_mar' => 'required|in:1,2',
            'latitud' => 'required_if:tipo_mar,1|numeric|nullable',
            'longitud' => 'required_if:tipo_mar,1|numeric|nullable',
            'radio' => 'required_if:tipo_mar,1|numeric|nullable',
            'poligono' => 'required_if:tipo_mar,2|array|nullable',
            'poligono.*.lat' => 'required_if:tipo_mar,2|numeric|nullable',
            'poligono.*.lng' => 'required_if:tipo_mar,2|numeric|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $punto_control = PuntoControl::find($id);
        if (!$punto_control) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['id' => ['Punto de control no encontrado.']],
            ], 404);
        }

        if (!$this->usuarioPuedeAccederPunto($punto_control)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['id' => ['No autorizado.']],
            ], 403);
        }

        $cooperativaId = $request->input('cooperativa_id');
        if (!$this->usuarioPuedeUsarCooperativa($cooperativaId)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['cooperativa_id' => ['No autorizado para esta cooperativa.']],
            ], 403);
        }

        $punto_control->descripcion = $request->input('descripcion');
        $punto_control->cooperativa_id = $cooperativaId;
        $punto_control->latitud = $request->input('latitud');
        $punto_control->longitud = $request->input('longitud');
        $punto_control->radio = $request->input('radio');
        $punto_control->pdi = $request->input('pdi');
        $punto_control->modificador_id = Auth::user()->_id;
        $punto_control->entrada = $request->input('entrada');
        $punto_control->salida = $request->input('salida');
        $punto_control->mt = $request->input('otro');
        $punto_control->estado_exportacion = 'P';
        $punto_control->estado = $request->input('estado');
        $punto_control->tipo_mar = $request->input('tipo_mar');
        if ($request->input('tipo_mar') == '1') {
            $punto_control->latitud = $request->input('latitud');
            $punto_control->longitud = $request->input('longitud');
            $punto_control->radio = $request->input('radio');
            $punto_control->poligono = [];
        } else {
            $punto_control->latitud = null;
            $punto_control->longitud = null;
            $punto_control->radio = null;
            $punto_control->poligono = $request->input('poligono');
        }
        $punto_control->pto_imaginario = $request->input('pto_imaginario') == 'true' ? true : false;

        $punto_control->save();

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'punto_control' => $punto_control,
        ]);
    }

    /**
     * Detalle de un punto y bloques relacionados (PuntoControlController::show).
     */
    public function show($id)
    {
        $punto_control = PuntoControl::find($id);
        if (!$punto_control) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['id' => ['Punto de control no encontrado.']],
            ], 404);
        }

        if (!$this->usuarioPuedeAccederPunto($punto_control)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['id' => ['No autorizado.']],
            ], 403);
        }

        $bloques = [];
        if (isset($punto_control->pdi_padre) && $punto_control->pdi_padre != '') {
            $bloques = PuntoControl::where('pdi_padre', $punto_control->pdi_padre)->get();
        }

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'punto_control' => $punto_control,
            'bloques' => $bloques,
        ]);
    }

    /**
     * Búsqueda JSON: combina criterios de PuntoControlController::search y searchJSON.
     *
     * Parámetros: cooperativa (obligatorio, id cooperativa para scope permitido),
     * estado (obligatorio: A, I o T), search (opcional, filtro por descripción),
     * creador_id (opcional: solo puntos creados por ese usuario; si no se envía o va vacío, no se filtra por creador).
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooperativa' => 'required',
            'estado' => 'required|max:1',
        ], [
            'cooperativa.required' => 'El identificador de cooperativa es obligatorio.',
            'estado.required' => 'El estado es obligatorio (A, I o T).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $id_cooperativa = $request->input('cooperativa');
        if (!$this->usuarioPuedeUsarCooperativa($id_cooperativa)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => ['cooperativa' => ['No autorizado para esta cooperativa.']],
            ], 403);
        }

        $search = $request->input('search', '');
        $query = PuntoControl::permitido($id_cooperativa)
            ->orderBy('descripcion', 'asc')
            ->where(function ($q) use ($search) {
                if ($search !== null && $search !== '') {
                    $q->where('descripcion', 'like', '%'.$search.'%');
                }
            });

        $estado = $request->input('estado');
        if ($estado != 'T') {
            $query->where('estado', $estado);
        }

        $creadorId = $request->input('creador_id');
        if ($creadorId !== null && $creadorId !== '') {
            $creadorId = is_string($creadorId) ? trim($creadorId) : $creadorId;
            if ($creadorId !== '' && $creadorId !== null) {
                $query->where('creador_id', $creadorId);
            }
        }

        $puntos_control = $query->get();

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'puntos_control' => $puntos_control,
        ]);
    }

    /**
     * Búsqueda rápida por texto (equivalente a PuntoControlController::searchJSON).
     * Requiere: search. cooperativa_id obligatorio solo para tipo usuario 1 (superadmin).
     */
    public function searchJson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required',
        ], [
            'search.required' => 'El texto de búsqueda es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $tipo_usuario = TipoUsuario::where('_id', Auth::user()->tipo_usuario_id)->first();
        $search = $request->input('search');

        if ($tipo_usuario->valor == '1') {
            if ($request->input('cooperativa_id') == '') {
                return response()->json([
                    'error' => true,
                    'api_version' => 'v2',
                    'messages' => ['cooperativa_id' => ['cooperativa_id es obligatorio para superadmin.']],
                ], 422);
            }
            if (!$this->usuarioPuedeUsarCooperativa($request->input('cooperativa_id'))) {
                return response()->json([
                    'error' => true,
                    'api_version' => 'v2',
                    'messages' => ['cooperativa_id' => ['No autorizado.']],
                ], 403);
            }

            $result = PuntoControl::orderBy('descripcion', 'asc')
                ->where('descripcion', 'like', '%'.$search.'%')
                ->where('estado', 'A')
                ->where('cooperativa_id', $request->input('cooperativa_id'))
                ->get();
        } elseif ($tipo_usuario->valor == '2') {
            $result = PuntoControl::orderBy('descripcion', 'asc')
                ->where('descripcion', 'like', '%'.$search.'%')
                ->where('cooperativa_id', Auth::user()->cooperativa_id)
                ->where('estado', 'A')
                ->get();
        } else {
            // Mismo criterio que rama tipo 2 en el panel (cooperativa del usuario).
            $result = PuntoControl::orderBy('descripcion', 'asc')
                ->where('descripcion', 'like', '%'.$search.'%')
                ->where('cooperativa_id', Auth::user()->cooperativa_id)
                ->where('estado', 'A')
                ->get();
        }

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'puntos_control' => $result,
        ]);
    }
}
