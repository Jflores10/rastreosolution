<?php

namespace App\Http\Controllers;

use App\DeviceToken;
use App\NotificationType;
use App\Services\FcmV1Service;
use App\Unidad;
use App\User;
use App\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalPushController extends Controller
{
    /**
     * Llamado por el parseador GPS (HTTP). Requiere LARAVEL_PUSH_SECRET.
     *
     * notification_type_code: código del tipo en notification_types. Si viene informado, solo reciben push
     * los usuarios con fila en user_notification_settings y enabled=true (sin fila => no enviar).
     * Si viene vacío, se envía a todos los usuarios enlazados a la unidad (comportamiento legacy).
     */
    public function pushByUnidad(Request $request, FcmV1Service $fcm)
    {
        $expected = env('LARAVEL_PUSH_SECRET');
        if ($expected === null || $expected === '') {
            return response()->json(array('error' => true, 'message' => 'LARAVEL_PUSH_SECRET no configurado'), 503);
        }
        if (trim((string) $request->input('secret')) !== trim((string) $expected)) {
            return response()->json(array('error' => true, 'message' => 'Forbidden'), 403);
        }

        $imei = trim((string) $request->input('imei'));
        $body = $request->input('body');
        if ($imei === '' || $body === null || $body === '') {
            return response()->json(array('error' => true, 'message' => 'imei y body requeridos'), 422);
        }
        $body = (string) $body;

        $typeCode = trim((string) $request->input('notification_type_code'));
        $notificationType = null;
        if ($typeCode !== '') {
            $notificationType = NotificationType::where('code', $typeCode)->where('activo', true)->first();
            if (!$notificationType) {
                return response()->json(array(
                    'error' => false,
                    'sent' => 0,
                    'message' => 'Tipo de notificación inexistente o inactivo: ' . $typeCode,
                ));
            }
        }

        $unidad = Unidad::where('imei', $imei)->where('estado', 'A')->first();
        if (!$unidad) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Unidad no encontrada'));
        }

        $strUnidadId = (string) $unidad->_id;
        $users = User::where('estado', 'A')->where('unidades_pertenecientes', $strUnidadId)->get();
        if ($users->isEmpty()) {
            $users = User::where('estado', 'A')->where('unidades_pertenecientes', $unidad->_id)->get();
        }
        if ($users->isEmpty()) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Sin usuarios con unidad enlazada'));
        }

        $typeIdStr = $notificationType ? (string) $notificationType->_id : null;

        $userIds = array();
        foreach ($users as $u) {
            $uid = (string) $u->_id;
            if ($typeIdStr !== null) {
                $setting = UserNotificationSetting::where('user_id', $uid)
                    ->where('notification_type_id', $typeIdStr)
                    ->first();
                $enabled = $setting ? (bool) $setting->enabled : false;
                if (!$enabled) {
                    continue;
                }
            }
            $userIds[] = $uid;
        }

        if (empty($userIds)) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Ningún usuario con esta notificación habilitada'));
        }

        $tokens = DeviceToken::whereIn('user_id', $userIds)->where('active', true)->get();
        if ($tokens->isEmpty()) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Sin tokens de dispositivo'));
        }

        if (!$fcm->hasServiceAccountFile()) {
            Log::warning('Push omitido: falta JSON de Firebase en storage/app/firebase/service-account.json');
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Firebase no configurado'));
        }

        $title = 'Rastreo Solution';
        $sent = 0;
        foreach ($tokens as $row) {
            if (!empty($row->token) && $fcm->sendToDevice($row->token, $title, $body)) {
                ++$sent;
            }
        }

        return response()->json(array('error' => false, 'sent' => $sent));
    }
}
