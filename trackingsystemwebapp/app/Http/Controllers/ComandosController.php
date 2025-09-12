<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cooperativa;
use App\Unidad;
use App\Comando;
use Auth;

use Validator;

class ComandosController extends Controller
{
     public function index()
    {
       return view('panel.lista-comandos',
        [
            'comandos'=> Comando::orderBy('descripcion', 'asc')
                ->with('cooperativa')
                ->paginate(10),
            'cooperativas' => Cooperativa::orderBy('descripcion')->where('estado', 'A')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'comando' => 'required|string',
            'modo' => 'required|in:A,M',
            'cooperativa_id' => 'required',
            'unidades' => 'nullable|array',
            'unidades.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        }

      

        $comando_guardado = Comando::create([
            'descripcion' => $request->descripcion,
            'comando' => $request->comando,
            'automatico' => $request->modo === 'A',
            'bloque' => $request->bloque,
            'cooperativa_id' => $request->cooperativa_id,
            'buses' => $request->unidades ?? [],
            'creador_id' => Auth::user()->_id,
            'modificador_id' => Auth::user()->_id,
        ]);

        return response()->json([
            'error' => false,
            'comando' => $comando_guardado
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comando_id' => 'required|exists:comandos,_id',
            'descripcion' => 'required|max:255',
            'comando' => 'required|string',
            'modo' => 'required|in:A,M',
            'cooperativa_id' => 'required',
            'unidades' => 'nullable|array',
            'unidades.*' => 'string',
            'bloque' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        }

        // Buscar el comando por ID
        $comando = Comando::findOrFail($request->comando_id);

        // Actualizar los campos
        $comando->descripcion = $request->descripcion;
        $comando->comando = $request->comando;
        $comando->automatico = $request->modo === 'A';
        $comando->bloque = $request->bloque;
        $comando->cooperativa_id = $request->cooperativa_id;
        $comando->buses = $request->unidades ?? [];
        $comando->modificador_id = Auth::user()->_id;

        $comando->save();

        return response()->json([
            'error' => false,
            'comando' => $comando,
            'message' => 'Comando actualizado correctamente'
        ]);
    }



    
    public function unidades_by_cooperativa($id)
    {
       
        $unidades = Unidad::where('cooperativa_id', $id)->orderBy('descripcion', 'asc')->get();
        return response()->json(['unidades' => $unidades]);
    }

    public function show($id)
    {

        $comando = Comando::findOrFail($id);
        return response()->json([
            'error' => false,
            'comando' => $comando
        ]);
    }

    public function enviar_comando($id)
    {
        try {
            $comando = Comando::findOrFail($id);
            if (!$comando) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No se encontró el comando',
                ], 404);
            }

            $host   = config('app.tcp_host');
            $port   = config('app.tcp_port');
            $cmd    = $comando->comando;

            $enviados      = 0;
            $fallidos      = 0;
            $busesFallidos = [];

            foreach ($comando->buses as $imei) {
                $payload = sprintf("ADMIN;%s;%s\r\n", $imei, $cmd);

                $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if ($socket === false) {
                    $fallidos++;
                    $busesFallidos[] = $imei;
                    continue;
                }

                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
                socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

                if (@socket_connect($socket, $host, $port) === false) {
                    socket_close($socket);
                    $fallidos++;
                    $busesFallidos[] = $imei;
                    continue;
                }

                $totalBytes = strlen($payload);
                $bytesSent  = 0;

                while ($bytesSent < $totalBytes) {
                    $chunk = substr($payload, $bytesSent);
                    $sent  = socket_write($socket, $chunk, $totalBytes - $bytesSent);

                    if ($sent === false) {
                        socket_close($socket);
                        $fallidos++;
                        $busesFallidos[] = $imei;
                        continue 2;
                    }

                    $bytesSent += $sent;
                }

                socket_close($socket);
                $enviados++;
            }

            $total = count($comando->buses);

            return response()->json([
                'error'     => false,
                'mensaje'   => "Se envió el comando a $enviados de $total unidades.",
                'buses_fallidos' => $busesFallidos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'Excepción: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $comando = Comando::findOrFail($id);
            $comando->delete();

            return response()->json([
                'error' => false,
                'message' => 'El comando ha sido eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'No se pudo eliminar el comando: ' . $e->getMessage()
            ]);
        }
    }


}
