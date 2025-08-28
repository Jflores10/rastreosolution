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
}
