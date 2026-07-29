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
        $expected = env('LARAVEL_PUSH_SECRET');
        if ($expected === null || $expected === '') {
            return response()->json(array('error' => true, 'message' => 'LARAVEL_PUSH_SECRET no configurado'), 503);
        }
        if (trim((string) $request->input('secret')) !== trim((string) $expected)) {
            return response()->json(array('error' => true, 'message' => 'Forbidden'), 403);
        }

        $despachoId = trim((string) $request->input('despacho_id'));
        if ($despachoId === '') {
            return response()->json(array('error' => true, 'message' => 'despacho_id requerido'), 422);
        }

        $despacho = Despacho::find($despachoId);
        if (!$despacho) {
            return response()->json(array('error' => true, 'message' => 'Despacho no encontrado'), 404);
        }

        if ($despacho->estado !== 'P') {
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

            $ctrl = app()->make(DespachoController::class);
            $fakeRequest = Request::create('/', 'GET', array());
            $response = $ctrl->end($fakeRequest, $despachoId);
            $data = $response->getData(true);

            return response()->json(array(
                'error' => isset($data['error']) ? (bool) $data['error'] : false,
                'despacho_id' => $despachoId,
                'data' => $data,
            ));
        } catch (\Throwable $e) {
            Log::error('InternalDespachoController::finish error: ' . $e->getMessage(), array(
                'despacho_id' => $despachoId,
            ));
            return response()->json(array(
                'error' => true,
                'message' => $e->getMessage(),
            ), 500);
        }
    }
}
