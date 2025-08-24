<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class ApiMiddleware
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
        $user = $request->user();
        if (isset($user) && $user->tipo_usuario->valor == 10)
            return $next($request);
        else if (isset($user))
        {
            Auth::logout();
            abort(403);
        }  
        else 
            return $next($request);  
    }
}
