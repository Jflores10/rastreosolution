<?php

namespace App\Http\Controllers;

use App\DeviceToken;

class DeviceTokenController extends Controller
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
        $tokens = DeviceToken::with('usuario')->orderBy('created_at', 'desc')->paginate(25);

        return view('panel.devices-tokens', array(
            'tokens' => $tokens,
        ));
    }
}
