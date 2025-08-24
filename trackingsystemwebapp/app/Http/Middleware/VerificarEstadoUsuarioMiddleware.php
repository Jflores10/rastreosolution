<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Hash;

class VerificarEstadoUsuarioMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $usuario = $request->user();
        $passwordUsuario = session()->get('password_usuario');
        if ($passwordUsuario != null && $usuario != null && !Hash::check($passwordUsuario, $usuario->password) &&
        $usuario->estado == "A") {
            Auth::logout();
            $usuario->cerrar_sesion = 'N';
            $usuario->save();
            return redirect(route('login'));
        }
        return $next($request);
    }
}
