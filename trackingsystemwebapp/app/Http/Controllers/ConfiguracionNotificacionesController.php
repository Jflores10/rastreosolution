<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionNotificacionesController extends Controller
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
        $path = storage_path('app/firebase/service-account.json');
        $exists = is_file($path);

        return view('panel.configuracion-notificaciones', array(
            'firebase_configured' => $exists,
        ));
    }

    public function store(Request $request)
    {
        $this->validate($request, array(
            'firebase_json' => 'required|file|max:10240',
        ));

        $file = $request->file('firebase_json');
        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false) {
            return redirect()->route('configuracion-notificaciones.index')
                ->withErrors(array('firebase_json' => 'No se pudo leer el archivo.'));
        }

        $json = json_decode($contents, true);
        if (!is_array($json) || empty($json['project_id']) || empty($json['private_key']) || empty($json['client_email'])) {
            return redirect()->route('configuracion-notificaciones.index')
                ->withErrors(array('firebase_json' => 'El JSON no parece una cuenta de servicio de Firebase válida (project_id, private_key, client_email).'));
        }

        $dir = storage_path('app/firebase');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . DIRECTORY_SEPARATOR . 'service-account.json';
        if (@file_put_contents($dest, $contents) === false) {
            return redirect()->route('configuracion-notificaciones.index')
                ->withErrors(array('firebase_json' => 'No se pudo guardar el archivo en el servidor.'));
        }
        @chmod($dest, 0640);

        return redirect()->route('configuracion-notificaciones.index')
            ->with('status', 'Archivo de Firebase guardado correctamente.');
    }
}
