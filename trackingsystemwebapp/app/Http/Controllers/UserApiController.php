<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use Illuminate\Support\Facades\Validator;

//use Validator;
use Hash;
class UserApiController extends Controller
{
    public function login(Request $request)
    {
		// dd("hola es un controller");
    	$validator = Validator::make($request->all(), [
    		'email' => 'required|max:255|exists:users',
    		'password' => 'required|max:255'
    	]);
    	if ($validator->fails())
    		return response()->json(['error' => true, 'messages' => $validator->errors()]);
    	else 
    	{
			$pass=$request->input('password');
			$pass=trim($pass);
			$user=$request->input('email');
			$user=trim($user);
    		$user = User::with('tipo_usuario', 'cooperativa')->where('email', $request->input('email'))->where('estado', 'A')->first();
			if (isset($user) && Hash::check($pass, $user->password) && 
				($user->tipo_usuario->valor == '1'  || $user->tipo_usuario->valor == '4'))
					return response()->json(['error' => false,'usuario'=>true, 'user' => $user]);
    		else
    			return abort(403);
    	}
    }

    /**
     * Login API v2: respuestas JSON consistentes, sin exponer campos sensibles, códigos HTTP explícitos.
     */
    public function login_v2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|max:255|exists:users',
    		'password' => 'required|max:255'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.max' => 'El correo electrónico no puede superar 255 caracteres.',
            'email.exists' => 'El correo electrónico no está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max' => 'La contraseña no puede superar 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'messages' => $validator->errors(),
            ], 422);
        }

        $email = trim($request->input('email'));
        $password = trim($request->input('password'));

        $user = User::with('tipo_usuario', 'cooperativa')
            ->where('email', $email)
            ->where('estado', 'A')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        if (!isset($user->tipo_usuario) || !in_array($user->tipo_usuario->valor, ['1', '4'], true)) {
            return response()->json([
                'error' => true,
                'api_version' => 'v2',
                'message' => 'No autorizado para acceder a la API.',
            ], 403);
        }

        $user->makeHidden(['password', 'remember_token']);

        return response()->json([
            'error' => false,
            'api_version' => 'v2',
            'usuario' => true,
            'user' => $user,
        ], 200);
    }

    public function cambiarContrasena(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6|confirmed',
            'contrasena_actual' => 'required',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'tipo_error' => 'validacion',
                'messages' => $validator->errors()
            ]);
        }

        $user = User::findOrFail($id);
        if (Hash::check($request->input('contrasena_actual'), $user->password)) {
            $user->password = bcrypt($request->input('password'));
            $user->modificador_id = $id;
            $user->save();

            return response()->json([
                'error' => false,
                'user' => $user
            ]);
        }

        return response()->json([
            'error' => true,
            'tipo_error' => 'No se pudo cambiar la contraseña. La contraseña actual no es la correcta.'
        ]);
    }
}
