<?php

namespace App\Http\Controllers;

use App\DeviceToken;
use App\NotificationType;
use App\Services\FcmV1Service;
use App\Services\LocationIqReverseGeocodeService;
use App\Unidad;
use App\User;
use App\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalPushController extends Controller
{
    /**
     * Determina si un registro en devices_token debe usarse para enviar push.
     * En Mongo a veces active viene como string o falta (legacy).
     */
    protected function deviceTokenRowIsActive($row)
    {
        if (!isset($row->token) || trim((string) $row->token) === '') {
            return false;
        }
        if (!isset($row->active)) {
            return true;
        }
        $a = $row->active;
        if ($a === false || $a === 0 || $a === '0' || $a === 'false' || $a === 'FALSE') {
            return false;
        }
        return true;
    }

    /**
     * Llamado por el parseador GPS (HTTP). Requiere LARAVEL_PUSH_SECRET.
     *
     * notification_type_code: código del tipo en notification_types. Si viene informado, solo reciben push
     * los usuarios con fila en user_notification_settings y enabled=true (sin fila => no enviar).
     * Si viene vacío, se envía a todos los usuarios enlazados a la unidad (comportamiento legacy).
     *
     * Cuerpo con HTML (p. ej. icono fa-plug): FCM no renderiza HTML en la bandeja del sistema.
     * FcmV1Service envía texto plano en notification.body y el HTML en data.html_body; la app móvil debe
     * usar html_body al construir la notificación (p. ej. Html.fromHtml) si se quiere ver el icono FA.
     */
    public function pushByUnidad(Request $request, FcmV1Service $fcm, LocationIqReverseGeocodeService $locationIq)
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

        $tokens = DeviceToken::whereIn('user_id', $userIds)->get();
        $tokens = $tokens->filter(function ($row) {
            return $this->deviceTokenRowIsActive($row);
        });
        if ($tokens->isEmpty()) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Sin tokens de dispositivo'));
        }

        // Un mismo token FCM no debe enviarse varias veces (varias filas / usuarios).
        $seenToken = array();
        $uniqueTokens = $tokens->filter(function ($row) use (&$seenToken) {
            $t = trim((string) $row->token);
            if ($t === '' || isset($seenToken[$t])) {
                return false;
            }
            $seenToken[$t] = true;
            return true;
        });
        if ($uniqueTokens->isEmpty()) {
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Sin tokens de dispositivo'));
        }

        if (!$fcm->hasServiceAccountFile()) {
            Log::warning('Push omitido: falta JSON de Firebase en storage/app/firebase/service-account.json');
            return response()->json(array('error' => false, 'sent' => 0, 'message' => 'Firebase no configurado'));
        }

        // Reverse geocoding solo si hay destinatarios reales (evita llamar LocationIQ en cada trama GPS).
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
            $body = $locationIq->appendDireccionToPushBody($body, $lat, $lng);
        }

        $title = 'Aviso';
        $sent = 0;
        foreach ($uniqueTokens as $row) {
            $tokenStr = trim((string) $row->token);
            if ($tokenStr === '') {
                continue;
            }
            $result = $fcm->sendToDevice($tokenStr, $title, $body);
            if (is_array($result) && !empty($result['deactivate_token'])) {
                DeviceToken::where('token', $tokenStr)->update(array('active' => false));
            }
            if (is_array($result) && !empty($result['success'])) {
                ++$sent;
            }
        }

        return response()->json(array('error' => false, 'sent' => $sent));
    }
}
