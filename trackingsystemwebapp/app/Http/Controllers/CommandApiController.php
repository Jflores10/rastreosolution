<?php

namespace App\Http\Controllers;

use App\Trama;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Redis;

class CommandApiController extends Controller
{
  public function send(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'message' => 'required|string',
      'imei'    => 'required|exists:unidads,imei',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'error'    => true,
        'messages' => $validator->errors(),
      ], 422);
    }

    $host   = config('app.tcp_host');
    $port   = config('app.tcp_port');
    $imei   = $request->input('imei');
    $cmd    = $request->input('message');
    $payload = sprintf("ADMIN;%s;%s\r\n", $imei, $cmd);

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
      return response()->json([
        'error'   => true,
        'message' => 'No se pudo crear el socket: ' . socket_strerror(socket_last_error()),
      ], 500);
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

    // 3. Conectar
    if (socket_connect($socket, $host, $port) === false) {
      $err = socket_last_error($socket);
      socket_close($socket);
      return response()->json([
        'error'   => true,
        'message' => 'Error de conexión: ' . socket_strerror($err),
      ], 500);
    }

    $totalBytes = strlen($payload);
    $bytesSent  = 0;

    while ($bytesSent < $totalBytes) {
      $chunk = substr($payload, $bytesSent);
      $sent  = socket_write($socket, $chunk, $totalBytes - $bytesSent);

      if ($sent === false) {
        $err = socket_last_error($socket);
        socket_close($socket);
        return response()->json([
          'error'        => true,
          'message'      => 'Error al enviar datos: ' . socket_strerror($err),
          'bytes_sent'   => $bytesSent,
          'bytes_total'  => $totalBytes,
          'payload'      => $payload,
        ], 500);
      }

      $bytesSent += $sent;
    }
    /*
    $requestId = str_random(40); 
    // Publicar comando en Redis
    Redis::publish('commands', json_encode([
        'imei'      => $imei,
        'cmd'       => $payload,
        'requestId' => $requestId,
    ]));

    // Esperar respuesta (polling cada 0.5s hasta 5s)
    $response = null;
    $timeout = 5;
    $start = microtime(true);
    $respuesta='';
    while ((microtime(true) - $start) < $timeout) {
        $res = Redis::get("response:$requestId");
        if ($res) {
            $response = json_decode($res, true);
            break;
        }
        usleep(500000); 
    }

    if (!$response) {
      $respuesta='No hubo respuesta del tracker en el tiempo esperado';
    }
    else{
      $respuesta=$response['data'] ?? $response;
    }
      */


    socket_close($socket);
    

    return response()->json([
      'error'       => false,
      'sent'        => true,
      'bytes_sent'  => $bytesSent,
      'bytes_total' => $totalBytes,
      'payload'     => $payload,
      //'respuesta'   =>$respuesta,
    ]);
  }


  public function sendCommandToStartReadingLogs()
  {
    //Execute laravel command in background
    $command = 'php artisan ts:write-log-sockets > /dev/null 2>&1 & echo $!';
    $pid = shell_exec($command);
    if ($pid) {
      return response()->json(['error' => false, 'pid' => $pid]);
    } else {
      return response()->json(['error' => true, 'message' => 'Failed to start command']);
    }
  }

  public function getLogFileTextReversed(Request $request)
  {
      $content = trim($request->input('content', ''));
      $numberOfLines = 100;

      $query = Trama::select('id', 'contenido', 'created_at')
          ->orderBy('created_at', 'desc');

      if ($content != '') {
          $query->where('contenido', 'like', '%' . $content . '%');
      }

      $tramas = $query->limit($numberOfLines)->get();

      return response()->json([
          'error' => false,
          'tramas' => $tramas
      ]);
  }

  /**
   * v2: envío de comando con cierre garantizado del socket (try/finally).
   */
  public function send_v2(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'message' => 'required|string',
      'imei'    => 'required|exists:unidads,imei',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'error'       => true,
        'api_version' => 'v2',
        'messages'    => $validator->errors(),
      ], 422);
    }

    $host   = config('app.tcp_host');
    $port   = config('app.tcp_port');
    $imei   = $request->input('imei');
    $cmd    = $request->input('message');
    $payload = sprintf("ADMIN;%s;%s\r\n", $imei, $cmd);

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
      return response()->json([
        'error'       => true,
        'api_version' => 'v2',
        'message'     => 'No se pudo crear el socket: ' . socket_strerror(socket_last_error()),
      ], 500);
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

    try {
      if (socket_connect($socket, $host, $port) === false) {
        $err = socket_last_error($socket);
        return response()->json([
          'error'       => true,
          'api_version' => 'v2',
          'message'     => 'Error de conexión: ' . socket_strerror($err),
        ], 500);
      }

      $totalBytes = strlen($payload);
      $bytesSent  = 0;

      while ($bytesSent < $totalBytes) {
        $chunk = substr($payload, $bytesSent);
        $sent  = socket_write($socket, $chunk, $totalBytes - $bytesSent);

        if ($sent === false) {
          $err = socket_last_error($socket);
          return response()->json([
            'error'        => true,
            'api_version'  => 'v2',
            'message'      => 'Error al enviar datos: ' . socket_strerror($err),
            'bytes_sent'   => $bytesSent,
            'bytes_total'  => $totalBytes,
          ], 500);
        }

        $bytesSent += $sent;
      }

      return response()->json([
        'error'        => false,
        'api_version'  => 'v2',
        'sent'         => true,
        'bytes_sent'   => $bytesSent,
        'bytes_total'  => $totalBytes,
      ], 200);
    } finally {
      socket_close($socket);
    }
  }

  /**
   * v2: inicio del proceso de logs con respuesta estructurada y soporte básico Windows/Unix.
   */
  public function sendCommandToStartReadingLogs_v2()
  {
    $artisan = base_path('artisan');
    if (!is_file($artisan)) {
      return response()->json([
        'error'       => true,
        'api_version' => 'v2',
        'message'     => 'No se encontró artisan.',
      ], 500);
    }

    $php = PHP_BINARY && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strncasecmp(PHP_OS, 'WIN', 3) === 0;

    if ($isWindows) {
      $cmd = sprintf(
        'start /B "" %s %s ts:write-log-sockets',
        escapeshellarg($php),
        escapeshellarg($artisan)
      );
      $output = shell_exec($cmd);
      $pid = $output !== null ? trim($output) : null;
    } else {
      $cmd = sprintf(
        '%s %s ts:write-log-sockets > /dev/null 2>&1 & echo $!',
        escapeshellarg($php),
        escapeshellarg($artisan)
      );
      $pid = shell_exec($cmd);
    }

    if ($pid !== null && $pid !== '') {
      return response()->json([
        'error'       => false,
        'api_version' => 'v2',
        'pid'         => $pid,
        'message'     => 'Comando de logs iniciado.',
      ], 200);
    }

    return response()->json([
      'error'       => true,
      'api_version' => 'v2',
      'message'     => 'No se pudo iniciar el comando.',
    ], 500);
  }

  /**
   * v2: lectura de tramas con límite acotado y metadatos.
   */
  public function getLogFileTextReversed_v2(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'limit' => 'sometimes|integer|min:1|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'error'       => true,
        'api_version' => 'v2',
        'messages'    => $validator->errors(),
      ], 422);
    }

    $rawContent = $request->input('content', '');
    if ($rawContent !== null && strlen((string) $rawContent) > 500) {
      return response()->json([
        'error'       => true,
        'api_version' => 'v2',
        'message'     => 'El parámetro content no puede superar 500 caracteres.',
      ], 422);
    }

    $content = trim((string) $rawContent);
    $numberOfLines = (int) $request->input('limit', 100);
    if ($numberOfLines < 1) {
      $numberOfLines = 100;
    }
    if ($numberOfLines > 500) {
      $numberOfLines = 500;
    }

    $query = Trama::select('id', 'contenido', 'created_at')
      ->orderBy('created_at', 'desc');

    if ($content !== '') {
      $query->where('contenido', 'like', '%' . $content . '%');
    }

    $tramas = $query->limit($numberOfLines)->get();

    return response()->json([
      'error'         => false,
      'api_version'   => 'v2',
      'count'         => $tramas->count(),
      'limit_applied' => $numberOfLines,
      'tramas'        => $tramas,
    ], 200);
  }
}