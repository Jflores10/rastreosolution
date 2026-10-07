<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use Validator;
use App\TipoUnidad;
use App\TipoUsuario;
use App\Unidad;
use Auth;
use Storage;

class TipoUnidadController extends Controller
{
    // No se usa la regla 'image' porque en Laravel 5.3 acepta SVG y rechaza WebP.
    // 'mimes' valida contra el tipo detectado por contenido (finfo), no por la extensión del cliente.
    const REGLA_ICONO = 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048';

    // campo => tamaño (px) del cuadrado en que se guarda
    private $iconos = [
        'icono_lista' => 40,
        'icono_mapa' => 64
    ];

    private function reglas()
    {
        return [
            'descripcion' => 'required|max:255',
            'icono_lista' => self::REGLA_ICONO,
            'icono_mapa' => self::REGLA_ICONO
        ];
    }

    private function mensajes()
    {
        $mensajes = [];
        foreach (array_keys($this->iconos) as $campo)
        {
            $mensajes[$campo . '.file'] = 'El ícono debe ser un archivo válido.';
            $mensajes[$campo . '.mimes'] = 'El ícono debe ser una imagen PNG, JPG o WEBP.';
            $mensajes[$campo . '.max'] = 'El ícono no debe superar los 2 MB.';
        }
        return $mensajes;
    }

    private function validador(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglas(), $this->mensajes());
        $validator->after(function ($validator) use ($request) {
            foreach (array_keys($this->iconos) as $campo)
            {
                if ($request->hasFile($campo) && @getimagesize($request->file($campo)->getRealPath()) === false)
                    $validator->errors()->add($campo, 'El archivo no es una imagen válida.');
            }
        });
        return $validator;
    }

    // Aplica a $tipoUnidad los íconos nuevos o los quita según el request.
    private function procesarIconos(Request $request, TipoUnidad $tipoUnidad)
    {
        foreach ($this->iconos as $campo => $tam)
        {
            if ($request->hasFile($campo))
            {
                // Se guarda el nuevo antes de borrar el anterior para no perder el ícono si algo falla.
                $nuevo = $this->guardarIcono($request->file($campo), $tam);
                $tipoUnidad->eliminarIcono($campo);
                $tipoUnidad->$campo = $nuevo;
            }
            else if ($request->input('quitar_' . $campo) == 'true')
                $tipoUnidad->eliminarIcono($campo);
        }
    }

    private function guardarIcono($archivo, $tam)
    {
        // Nombre único por subida: store() usa el md5 del contenido, y la misma imagen en lista y mapa
        // (o re-subida) compartiría archivo y se borraría al eliminar el "anterior".
        $nombre = md5(uniqid('', true) . str_random(16));
        $png = $this->redimensionarIcono($archivo->getRealPath(), $tam);
        if ($png !== null)
        {
            $ruta = 'tipos-unidades/iconos/' . $nombre . '.png';
            Storage::disk('public')->put($ruta, $png);
            return $ruta;
        }
        // Sin GD (o formato no soportado por GD): se guarda el original ya validado.
        $ext = strtolower($archivo->guessExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp']))
            $ext = 'png';
        return $archivo->storeAs('tipos-unidades/iconos', $nombre . '.' . $ext, 'public');
    }

    // Redimensiona a un cuadrado fijo con fondo transparente y re-codifica como PNG
    // (descarta metadatos y cualquier contenido ajeno a la imagen).
    private function redimensionarIcono($ruta, $tam)
    {
        if (!function_exists('imagecreatefromstring'))
            return null;
        $origen = @imagecreatefromstring(file_get_contents($ruta));
        if (!$origen)
            return null;

        $w = imagesx($origen);
        $h = imagesy($origen);
        $escala = min($tam / $w, $tam / $h);
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));

        $destino = imagecreatetruecolor($tam, $tam);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origen, (int) (($tam - $nw) / 2), (int) (($tam - $nh) / 2), 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($destino);
        $png = ob_get_clean();
        imagedestroy($origen);
        imagedestroy($destino);
        return $png;
    }

    // Sirve un ícono guardado en storage/app/public/tipos-unidades/iconos.
    // $nombre: nombre del archivo sin extensión (ver TipoUnidad::urlIcono).
    public function icono($nombre)
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext)
        {
            $ruta = 'tipos-unidades/iconos/' . $nombre . '.' . $ext;
            if (Storage::disk('public')->exists($ruta))
            {
                // El nombre es único por subida, así que se puede cachear sin riesgo de servir uno viejo.
                return response()->file(storage_path('app/public/' . $ruta), [
                    'Cache-Control' => 'public, max-age=31536000'
                ]);
            }
        }
        abort(404);
    }

    public function index()
    {
        if(Auth::user()->estado=='A')
        {
            $tipo_usuario = TipoUsuario::where('_id',Auth::user()->tipo_usuario_id)->first();
            if($tipo_usuario->valor==1)
                return view('panel.lista-tipos-de-unidades', ['tipos_de_unidades' => TipoUnidad::where('estado','A')->paginate(10)]);
            else
                return view('panel.error',['mensaje_acceso'=>'No posee suficientes permisos para poder ingresar a este sitio.']);
        }
        else
            return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.']);
    }
    public function store(Request $request)
    {
        $validator = $this->validador($request);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $tipoUnidad = new TipoUnidad([
                'descripcion' => $request->input('descripcion'),
                'estado' =>$request->input('estado'),
                'creador_id' => Auth::user()->_id,
                'modificador_id' => Auth::user()->_id
            ]);
            $this->procesarIconos($request, $tipoUnidad);
            $tipoUnidad->save();
            return response()->json(['error' => false, 'tipo_unidad' => $tipoUnidad]);
        }
    }
    public function show($id)
    {
        $tipoUnidad = TipoUnidad::findOrFail($id);
        return response()->json($tipoUnidad);
    }
    public function update(Request $request, $id)
    {
        $validator = $this->validador($request);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $tipoUnidad = TipoUnidad::findOrFail($id);
            $tipoUnidad->descripcion = $request->input('descripcion');
            $tipoUnidad->modificador_id = Auth::user()->_id;
            $this->procesarIconos($request, $tipoUnidad);
            $tipoUnidad->save();
            return response()->json(['error' => false, 'tipo_unidad' => $tipoUnidad]);
        }
    }
    public function destroy($id)
    {
       $unidad = Unidad::findOrFail($id);
        $tipo_unidad = TipoUnidad::findOrFail($id);

        if($tipo_unidad->estado=="A")
        {
            if($unidad==null)
                $tipo_unidad->estado="I";
        }
        else
            $tipo_unidad->estado="A";

        $tipo_unidad->save();
        return response()->json($tipo_unidad);
    }


    public function search(Request $request)
    {
        $search = $request->input('search');


        switch($request->input('mostrar_modo'))
        {
            case "inactivos":

                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::
                        where('estado', 'I')
                        ->paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->where('estado', 'I')
                        ->paginate(10);
                }


                break;

            case "todos":
                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::

                        paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->paginate(10);
                }

                break;

            default:
                if($search=='')
                {
                    $tipo_unidad = TipoUnidad::
                    where('estado', 'A')
                        ->paginate(10);
                }
                else
                {
                    $tipo_unidad = TipoUnidad::where('descripcion', 'like', '%'. $search . '%')
                        ->where('estado', 'A')
                        ->paginate(10);
                }
                break;
        }
        $tipo_unidad->setPath($request->fullUrl());
        return view('panel.lista-tipos-de-unidades', ['tipos_de_unidades' => $tipo_unidad,'opcion'=> $request->input('mostrar_modo')
        ,'sss'=>$search]);
    }

}
