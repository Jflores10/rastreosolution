<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Response;
use App\User;
use App\TipoUsuario;
use Auth;
use Validator;
use Hash;

class PerfilUsuarioController extends Controller
{
    public function index()
    {
        return view('panel.perfil-usuario',['usuario'=>User::where('_id',Auth::user()->_id)->first(),
        'tipo_usuario'=>TipoUsuario::findOrFail(Auth::user()->tipo_usuario_id)]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        switch($request->input('tipo'))
        {
            case "cambiar_datos":
                if($request->input('email')==Auth::user()->email)
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|max:150',
                        'email' => 'required'
                    ]);
                else
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|max:150',
                        'email' => 'required|unique:users'
                    ]);

                if ($validator->fails())
                    return response()->json(['error' => true, 'messages' => $validator->errors()]);
                else
                {
                    $user = User::findOrFail(Auth::user()->_id);
                    $user->name = $request->input('name');
                    $user->email = $request->input('email');
                    $user->modificador_id = Auth::user()->_id;
                    $user->save();
                    return response()->json(['error' => false, 'user' => $user]);
                }
                break;

            case "cambiar_contraseña":
                $validator = Validator::make($request->all(), [
                    'password' => 'required|min:6|confirmed',
                    'contraseña_actual' => 'required',
                    'password_confirmation' => 'required'
                ]);
                if ($validator->fails())
                    return response()->json(['error' => true,'tipo_error'=>'validacion', 'messages' => $validator->errors()]);
                else
                {
                    $user = User::findOrFail(Auth::user()->_id);
                    if (Hash::check($request->input('contraseña_actual'), $user->password))
                    {
                        $user->password = bcrypt($request->input('password'));
                        $user->modificador_id = Auth::user()->_id;
                        $user->save();
                        return response()->json(['error' => false, 'user' => $user]);
                    }
                    else
                    {
                        return response()->json(['error' => true, 'tipo_error'=>'contraseña_actual']);
                    }
                }
                break;

            default:break;
        }
    }
}
/**
 * Created by PhpStorm.
 * User: José Daniel
 * Date: 26/09/2016
 * Time: 15:39
 */