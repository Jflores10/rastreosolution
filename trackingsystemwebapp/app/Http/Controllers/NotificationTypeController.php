<?php

namespace App\Http\Controllers;

use App\NotificationType;
use App\UserNotificationSetting;
use Illuminate\Http\Request;
use Validator;

class NotificationTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            if (!$u || !isset($u->tipo_usuario) || !in_array($u->tipo_usuario->valor, array('1', '2'), true)) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $items = NotificationType::orderBy('code')->paginate(15);

        return view('panel.notification-types.index', array('items' => $items));
    }

    /**
     * JSON para el modal de edición (mismo patrón que /comandos/get/{id}).
     */
    public function getJson($id)
    {
        $item = NotificationType::findOrFail($id);

        return response()->json(array(
            'error' => false,
            'notification_type' => array(
                '_id' => (string) $item->_id,
                'code' => $item->code,
                'nombre' => $item->nombre,
                'descripcion' => $item->descripcion,
                'activo' => (bool) $item->activo,
            ),
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'code' => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ));
        if ($validator->fails()) {
            return response()->json(array('error' => true, 'messages' => $validator->errors()));
        }

        $code = trim($request->input('code'));
        if (NotificationType::where('code', $code)->first() !== null) {
            return response()->json(array(
                'error' => true,
                'messages' => array('code' => array('Ya existe un tipo con ese código.')),
            ));
        }

        $item = NotificationType::create(array(
            'code' => $code,
            'nombre' => trim($request->input('nombre')),
            'descripcion' => $request->input('descripcion'),
            'activo' => $request->has('activo'),
        ));

        return response()->json(array('error' => false, 'item' => $item));
    }

    /**
     * Actualización vía POST (mismo patrón que /comandos/update), evita _method PUT en AJAX.
     */
    public function updatePost(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'notification_type_id' => 'required|string',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ));
        if ($validator->fails()) {
            return response()->json(array('error' => true, 'messages' => $validator->errors()));
        }

        $item = NotificationType::findOrFail($request->input('notification_type_id'));

        $item->nombre = trim($request->input('nombre'));
        $item->descripcion = $request->input('descripcion');
        $item->activo = $request->has('activo');
        $item->save();

        return response()->json(array('error' => false, 'item' => $item));
    }

    public function update(Request $request, $notification_type)
    {
        $item = NotificationType::findOrFail($notification_type);

        $validator = Validator::make($request->all(), array(
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ));
        if ($validator->fails()) {
            return response()->json(array('error' => true, 'messages' => $validator->errors()));
        }

        $item->nombre = trim($request->input('nombre'));
        $item->descripcion = $request->input('descripcion');
        $item->activo = $request->has('activo');
        $item->save();

        return response()->json(array('error' => false, 'item' => $item));
    }

    /**
     * Eliminación vía POST (mismo patrón que /comandos/delete/{id}).
     */
    public function destroyPost($id)
    {
        return $this->destroy($id);
    }

    public function destroy($notification_type)
    {
        $item = NotificationType::findOrFail($notification_type);

        UserNotificationSetting::where('notification_type_id', (string) $item->_id)->delete();
        $item->delete();

        return response()->json(array(
            'error' => false,
            'message' => 'El tipo de notificación ha sido eliminado correctamente.',
        ));
    }
}
