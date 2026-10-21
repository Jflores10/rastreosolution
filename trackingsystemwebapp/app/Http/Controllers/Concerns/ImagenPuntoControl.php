<?php

namespace App\Http\Controllers\Concerns;

use App\PuntoControl;
use Illuminate\Http\Request;
use Storage;

// Imagen de los puntos de control imaginarios (icono en los mapas).
// Compartido por PuntoControlController (web) y PuntoControlApiController (app).
trait ImagenPuntoControl
{
    private function reglasImagen()
    {
        return ['imagen' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048'];
    }

    private function mensajesImagen()
    {
        return [
            'imagen.file' => 'La imagen debe ser un archivo válido.',
            'imagen.mimes' => 'La imagen debe ser PNG, JPG o WEBP.',
            'imagen.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }

    private function validarContenidoImagen($validator, Request $request)
    {
        $validator->after(function ($validator) use ($request) {
            if ($request->hasFile('imagen') && @getimagesize($request->file('imagen')->getRealPath()) === false)
                $validator->errors()->add('imagen', 'El archivo no es una imagen válida.');
        });
    }

    // La imagen solo aplica a puntos imaginarios: si el punto deja de serlo se elimina.
    private function procesarImagen(Request $request, PuntoControl $punto_control)
    {
        if (!$punto_control->pto_imaginario)
            $punto_control->eliminarImagen();
        else if ($request->hasFile('imagen'))
        {
            // Se guarda la nueva antes de borrar la anterior para no perderla si algo falla.
            $nueva = $this->guardarImagen($request->file('imagen'));
            $punto_control->eliminarImagen();
            $punto_control->imagen = $nueva;
        }
        else if ($request->input('quitar_imagen') == 'true')
            $punto_control->eliminarImagen();
    }

    private function guardarImagen($archivo)
    {
        $nombre = md5(uniqid('', true) . str_random(16));
        $png = $this->redimensionarImagen($archivo->getRealPath(), PuntoControl::TAM_IMAGEN);
        if ($png !== null)
        {
            $ruta = 'puntos-control/imagenes/' . $nombre . '.png';
            Storage::disk('public')->put($ruta, $png);
            return $ruta;
        }
        // Sin GD (o formato no soportado por GD): se guarda el original ya validado.
        $ext = strtolower($archivo->guessExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp']))
            $ext = 'png';
        return $archivo->storeAs('puntos-control/imagenes', $nombre . '.' . $ext, 'public');
    }

    // Igual que TipoUnidadController::redimensionarIcono: cuadrado fijo con fondo
    // transparente, re-codificado como PNG (descarta metadatos).
    private function redimensionarImagen($ruta, $tam)
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
}
