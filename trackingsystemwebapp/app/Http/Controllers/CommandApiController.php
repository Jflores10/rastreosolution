<?php

namespace App\Http\Controllers;

use App\Trama;
use Illuminate\Http\Request;
use Validator;

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

    socket_close($socket);

    return response()->json([
      'error'       => false,
      'sent'        => true,
      'bytes_sent'  => $bytesSent,
      'bytes_total' => $totalBytes,
      'payload'     => $payload,
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
    $content = $request->input('content', '');
    $numberOfLines = 100;
    $builder = Trama::orderBy('created_at', 'desc')
      ->take($numberOfLines);
    if (!empty($content)) {
      $builder->where('contenido', 'like', '%' . $content . '%');
    }
    $tramas = $builder->get();
    return response()->json([
      'error' => false,
      'tramas' => $tramas
    ]);
  }
}
