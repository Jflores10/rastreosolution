<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;



class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @return Response
     */
    public function authenticate()
    {
        if (Auth::attempt(['email' => $email, 'password' => $password, 'estado'=>"A"])) {
            // Authentication passed...
            return redirect()->intended('dashboard');
        }
        elseif(Auth::attempt(['nombre_usuario' => $nombre_usuario, 'password' => $password, 'estado'=>"A"]))
        {
            // Authentication passed...
            return redirect()->intended('dashboard');
        }

    }
}

/**
 * Created by PhpStorm.
 * User: José Daniel
 * Date: 27/09/2016
 * Time: 18:27
 */