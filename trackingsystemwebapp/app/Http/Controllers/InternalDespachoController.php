<?php

namespace App\Http\Controllers;

use App\Despacho;
use App\TipoUsuario;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints internos llamados por el parseador GPS (sin sesión web).
 */
class InternalDespachoController extends Controller
{
    /**
     * Finaliza un despacho pendiente invocando DespachoController::end.
     * Requiere LARAVEL_PUSH_SECRET (mismo secret que el push interno).
     */
    public function finish(Request $request)
    {
        $tag = '[finish-despacho]';
        Log::info($tag . ' ▶ request recibido', array(
            'despacho_id' => $request->input('despacho_id'),
            'ip' => $request->ip(),
        ));

        $expected = env('LARAVEL_PUSH_SECRET');
        if ($expected === null || $expected === '') {
            Log::warning($tag . ' ✗ LARAVEL_PUSH_SECRET no configurado');
            return response()->json(array('error' => true, 'message' => 'LARAVEL_PUSH_SECRET no configurado'), 503);
        }
        if (trim((string) $request->input('secret')) !== trim((string) $expected)) {
            Log::warning($tag . ' ✗ secret inválido');
            return response()->json(array('error' => true, 'message' => 'Forbidden'), 403);
        }

        $despachoId = trim((string) $request->input('despacho_id'));
        if ($despachoId === '') {
            Log::warning($tag . ' ✗ despacho_id vacío');
            return response()->json(array('error' => true, 'message' => 'despacho_id requerido'), 422);
        }

        $despacho = Despacho::find($despachoId);
        if (!$despacho) {
            Log::warning($tag . ' ✗ despacho no encontrado', array('despacho_id' => $despachoId));
            return response()->json(array('error' => true, 'message' => 'Despacho no encontrado'), 404);
        }

        Log::info($tag . ' despacho encontrado', array(
            'despacho_id' => $despachoId,
            'estado' => $despacho->estado,
            'unidad_id' => (string) $despacho->unidad_id,
            'fecha' => $despacho->fecha,
        ));

        if ($despacho->estado !== 'P') {
            Log::info($tag . ' skip: no está pendiente', array(
                'despacho_id' => $despachoId,
                'estado' => $despacho->estado,
            ));
            return response()->json(array(
                'error' => false,
                'skipped' => true,
                'message' => 'Despacho no está pendiente',
                'estado' => $despacho->estado,
            ));
        }

        try {
            $distributorType = TipoUsuario::where('valor', '1')->firstOrFail();
            $user = User::where('tipo_usuario_id', $distributorType->_id)->firstOrFail();
            Auth::onceUsingId($user->_id);

            Log::info($tag . ' invocando DespachoController::end', array(
                'despacho_id' => $despachoId,
                'user_id' => (string) $user->_id,
            ));

            $ctrl = app()->make(DespachoController::class);
            $fakeRequest = Request::create('/', 'GET', array());
            $response = $ctrl->end($fakeRequest, $despachoId);
            $data = $response->getData(true);

            Log::info($tag . ' ✓ end() completado', array(
                'despacho_id' => $despachoId,
                'result' => $data,
            ));

            return response()->json(array(
                'error' => isset($data['error']) ? (bool) $data['error'] : false,
                'despacho_id' => $despachoId,
                'data' => $data,
            ));
        } catch (\Throwable $e) {
            Log::error($tag . ' ✗ exception: ' . $e->getMessage(), array(
                'despacho_id' => $despachoId,
                'trace' => $e->getTraceAsString(),
            ));
            return response()->json(array(
                'error' => true,
                'message' => $e->getMessage(),
            ), 500);
        }
    }
}