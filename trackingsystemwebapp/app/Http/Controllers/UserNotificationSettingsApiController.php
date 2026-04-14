<?php

namespace App\Http\Controllers;

use App\NotificationType;
use App\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserNotificationSettingsApiController extends Controller
{
    /**
     * Tipos activos (para armar la pantalla de toggles en la app).
     */
    public function types_v2()
    {
        $types = NotificationType::where('activo', true)->orderBy('nombre')->get();

        $out = array();
        foreach ($types as $t) {
            $out[] = array(
                'code' => $t->code,
                'nombre' => $t->nombre,
                'descripcion' => $t->descripcion,
            );
        }

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'data' => $out,
        ), 200);
    }

    /**
     * Tipos activos con enabled solo si existe fila en user_notification_settings (sin fila => false).
     */
    public function index_v2()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(array('error' => true, 'api_version' => 'v2', 'message' => 'No autorizado.'), 401);
        }

        $uid = (string) $user->_id;
        $types = NotificationType::where('activo', true)->orderBy('nombre')->get();
        $data = array();

        foreach ($types as $t) {
            $tid = (string) $t->_id;
            $row = UserNotificationSetting::where('user_id', $uid)->where('notification_type_id', $tid)->first();
            $enabled = $row ? (bool) $row->enabled : false;

            $data[] = array(
                'code' => $t->code,
                'nombre' => $t->nombre,
                'descripcion' => $t->descripcion,
                'enabled' => $enabled,
            );
        }

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'data' => $data,
        ), 200);
    }

    /**
     * Actualizar preferencias: body { "settings": [ { "code": "...", "enabled": true }, ... ] }
     */
    public function store_v2(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(array('error' => true, 'api_version' => 'v2', 'message' => 'No autorizado.'), 401);
        }

        $validator = Validator::make($request->all(), array(
            'settings' => 'required|array',
            'settings.*.code' => 'required|string|max:64',
            'settings.*.enabled' => 'required|boolean',
        ));
        if ($validator->fails()) {
            return response()->json(array(
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ), 422);
        }

        $uid = (string) $user->_id;

        foreach ($request->input('settings') as $item) {
            $code = trim($item['code']);
            $type = NotificationType::where('code', $code)->where('activo', true)->first();
            if (!$type) {
                continue;
            }
            $tid = (string) $type->_id;
            $enabled = (bool) $item['enabled'];

            $row = UserNotificationSetting::firstOrNew(array(
                'user_id' => $uid,
                'notification_type_id' => $tid,
            ));
            $row->enabled = $enabled;
            $row->save();
        }

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'message' => 'Preferencias guardadas.',
        ), 200);
    }
}
