<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Cooperativa;
use App\SesionUsuario;
use Carbon\Carbon;

use Auth as UserControl;
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }
    public function login(Request $request) {
        $this->validateLogin($request);
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        $user = User::where('email', 'like', $request->input('email'))->with('tipo_usuario')->first();      
        if (isset($user) && Auth::attempt(['email' => $user->email, 'password' => $request->input('password')])) {
            if( $user->tipo_usuario->valor != '1'){
                if(isset($user->ip) && $user->ip != null  && $user->ip != ''){
                    $ips=explode(";", $user->ip);
                    $autorizado='NO';
                    foreach($ips as $ip){
                        if(trim($ip) == trim($request->ip())){
                            $autorizado='SI';
                        }
                    }

                    if($autorizado=='SI'){
                        SesionUsuario::create([
                            'fecha_sesion' => Carbon::now(),
                            'usuario_id' => $user->_id,
                            'direccion_ip' => $request->ip(),
                            'conexion' => 'S'
                        ]);
                        return $this->sendLoginResponse($request);
                    }
                    else{
                        Auth::logout();
                        return redirect('login');
                    }
                }else{
                    SesionUsuario::create([
                        'fecha_sesion' => Carbon::now(),
                        'usuario_id' => $user->_id,
                        'direccion_ip' => $request->ip(),
                        'conexion' => 'S'
                    ]);
                    return $this->sendLoginResponse($request);
                }
            }else{
                SesionUsuario::create([
                    'fecha_sesion' => Carbon::now(),
                    'usuario_id' => $user->_id,
                    'direccion_ip' => $request->ip(),
                    'conexion' => 'S'
                ]);
                return $this->sendLoginResponse($request);
            }
        }
        return $this->sendFailedLoginResponse($request);
    }

    public function logout(Request $request) {
        $user = UserControl::user();
        if (isset ($user)) {
            SesionUsuario::create([
                'fecha_sesion' => Carbon::now(),
                'usuario_id' => $user->_id,
                'direccion_ip' => $request->ip(),
                'conexion' => 'N'
            ]);
            UserControl::logout();
        }
        return redirect(url('login'));
    }
}
