<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use GuzzleHttp\Client;
class ValidatorMiddleware
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
        $client = new Client();
        $response = $client->get('http://securitytrack.kimerasoft-ec.com/index.php');
        if ($response->getBody()->getContents() === 'SECURITY')
            return $next($request);
        else 
        {
            if (Auth::check())
            {
                Auth::logout();
                return redirect('/login');
            }
        }
    }
}
