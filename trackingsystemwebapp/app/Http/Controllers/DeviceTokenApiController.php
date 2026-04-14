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

        $row = DeviceToken::where('user_id', $uid)->where('device_id', $deviceId)->first();
        if (!$row) {
            $row = new DeviceToken();
            $row->user_id = $uid;
            $row->device_id = $deviceId;
        }
        $row->token = $request->input('token');
        $row->platform = trim($request->input('platform'));
        $row->active = true;
        $row->save();

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'message' => 'Token registrado.',
        ), 200);
    }
}
