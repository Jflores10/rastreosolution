<?php

namespace App\Http\Controllers;

use App\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceTokenApiController extends Controller
{
    /**
     * Registro/actualización de token FCM para el usuario autenticado (Basic).
     */
    public function register_v2(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'token' => 'required|string|max:512',
            'platform' => 'required|string|max:32',
            'device_id' => 'required|string|max:128',
        ), array(
            'token.required' => 'El token del dispositivo es obligatorio.',
            'token.string' => 'El token del dispositivo debe ser texto.',
            'token.max' => 'El token del dispositivo no puede superar 512 caracteres.',
            'platform.required' => 'La plataforma del dispositivo es obligatoria.',
            'platform.string' => 'La plataforma del dispositivo debe ser texto.',
            'platform.max' => 'La plataforma del dispositivo no puede superar 32 caracteres.',
            'device_id.required' => 'El identificador del dispositivo es obligatorio.',
            'device_id.string' => 'El identificador del dispositivo debe ser texto.',
            'device_id.max' => 'El identificador del dispositivo no puede superar 128 caracteres.',
        ));
        if ($validator->fails()) {
            return response()->json(array(
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ), 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(array(
                'error' => true,
                'api_version' => 'v2',
                'message' => 'No autorizado.',
            ), 401);
        }

        $uid = (string) $user->_id;
        $deviceId = trim($request->input('device_id'));
        $token = trim($request->input('token'));
        $platform = trim($request->input('platform'));

        // Si el mismo dispositivo inicia sesión con otro usuario,
        // desactivar asociaciones previas para evitar notificaciones cruzadas.
        DeviceToken::where('device_id', $deviceId)
            ->where('user_id', '!=', $uid)
            ->update(array('active' => false));

        // También desactivar el mismo token cuando esté asociado a otro usuario.
        // Esto cubre casos donde el token rota o el device_id no llega consistente.
        DeviceToken::where('token', $token)
            ->where('user_id', '!=', $uid)
            ->update(array('active' => false));

        $row = DeviceToken::where('user_id', $uid)->where('device_id', $deviceId)->first();
        if (!$row) {
            $row = new DeviceToken();
            $row->user_id = $uid;
            $row->device_id = $deviceId;
        }
        $row->token = $token;
        $row->platform = $platform;
        $row->active = true;
        $row->save();

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'message' => 'Token registrado.',
        ), 200);
    }
}
