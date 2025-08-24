<?php

namespace App\Http\Controllers;
use App\TipoUsuario;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Response;
use App\User;
use App\Cooperativa;
use App\Unidad;
use Auth;
use Validator;
use Excel;
class UsuarioController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('panel.lista-usuarios',
        [
            'usuarios' => User::orderBy('name', 'asc')->permitido()->where('estado', 'A')->where('tipo_usuario_id','!=','5bee25964c34feb713a0233b')->paginate(10),
            'tipos_usuarios' => TipoUsuario::permitido()->get(),
            'cooperativas' => Cooperativa::orderBy('descripcion', 'asc')->permitida()->where('estado', 'A')->get(),
            'unidades' => Unidad::orderBy('descripcion', 'asc')->permitida()->where('estado','A')->get(),
            'tipo_usuario_valor' => $user->tipo_usuario->valor,
            'id_cooperativa' => $user->cooperativa_id
        ]);
    }

    public function store(Request $request)
    {
        if($request->input('opcion')=='crear')
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:150',
                'password' => 'required|min:6|confirmed',
                'tipo_usuario_id' => 'required',
                'cooperativa_id' => 'nullable',
                'email' => 'required|unique:users',
                'telefono' => 'nullable',
                'correo' => 'nullable|email|max:255'
            ]);
            if ($validator->fails())
                return response()->json(['error' => true, 'messages' => $validator->errors()]);
            else
            {
                $user = User::create([
                    'name' => $request->input('name'),
                    'password' => bcrypt($request->input('password')),
                    'tipo_usuario_id' =>$request->input('tipo_usuario_id'),
                    'cooperativa_id' =>($request->input('cooperativa_id') != 'none')?$request->input('cooperativa_id'):null,
                    'unidades_pertenecientes' =>$request->input('unidades_pertenecientes'),
                    'email'=>$request->input('email'),
                    'estado' =>$request->input('estado'),
                    'creador_id' => Auth::user()->_id,
                    'modificador_id' => Auth::user()->_id,
                    'operadora' => $request->input('operadora'),
                    'correo' => $request->input('correo'),
                    'telefono' => $request->input('telefono'),
                    'ip' => $request->input('ip')
                ]);
                return response()->json(['error' => false, 'user' => $user]);
            }
        }
        elseif($request->input('opcion')=='getTipoUsuario')
        {
            $validator = Validator::make($request->all(), [
                'tipo_usuario_id' => 'required',
            ]);
            if ($validator->fails())
                return response()->json(['error' => true]);
            else
            {
                $tipo_usuario = TipoUsuario::findOrFail($request->input('tipo_usuario_id'));
                return response()->json(['error' => false, 'tipo_usuario_valor' => $tipo_usuario->valor]);
            }
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if($user->email==$request->input('email'))
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:150',
                'password' => 'required|min:6|confirmed',
                'tipo_usuario_id' => 'required',
                'cooperativa_id' => 'nullable',
                'email' => 'required',
                'correo' => 'nullable|email|max:255',
                'telefono' => 'nullable'
            ]);
        else
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:150',
                'password' => 'required|min:6|confirmed',
                'tipo_usuario_id' => 'required',
                'cooperativa_id' => 'nullable|exists:cooperativas,_id',
                'email' => 'required|unique:users',
                'correo' => 'nullable|email|max:255',
                'telefono' => 'nullable'
            ]);
        if ($validator->fails())
            return response()->json(['error' => true, 'messages' => $validator->errors()]);
        else
        {
            $user->name = $request->input('name');
            $user->password =  bcrypt($request->input('password'));
            $user->email = $request->input('email');
            $user->tipo_usuario_id = $request->input('tipo_usuario_id');
            $user->unidades_pertenecientes = $request->input('unidades_pertenecientes');
            $user->cooperativa_id = ($request->input('cooperativa_id') != 'none')?$request->input('cooperativa_id'):null;
            $user->modificador_id = Auth::user()->_id;
            $user->correo = $request->input('correo');
            $user->operadora = $request->input('operadora');
            $user->telefono = $request->input('telefono');
            $user->ip = $request->input('ip');
            $user->save();
            return response()->json(['error' => false, 'user' => $user]);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if($user->estado=="A")
            $user->estado="I";
        else
            $user->estado="A";

        $user->save();
        return response()->json($user);
    }

    public function eliminar($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json($user);
    }

    public function usuarioSuspendido()
    {
        return view('panel.error',['mensaje_acceso'=>'En este momento su usuario se encuentra suspendido.','suspendido'=>true]);
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'estado' => 'required|max:1',
            'cooperativa' => 'nullable|exists:cooperativas,_id'
        ]);
        $tipo_usuario_valor = Auth::user()->tipo_usuario->valor;
        $search = $request->input('search');
        $usuarios = User::orderBy('name')->permitido($request->input('cooperativa'))->where('tipo_usuario_id','!=','5bee25964c34feb713a0233b')->where(function ($query) use($search) {
            if ($search != null && $search != '')
                $query->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%");
        });
        $estado = $request->input('estado');
        if ($estado != 'T')
            $usuarios->where('estado', $estado);
        if ($request->input('exportar') != null) {
            $filename = 'Usuarios-' . date('YmdHis');
            Excel::create($filename, function ($excel) use ($usuarios) {
                $excel->sheet('Usuarios', function ($sheet) use ($usuarios) {
                    $sheet->loadView('panel.usuarios.excel-usuarios', [
                        'usuarios' => $usuarios->get()
                    ]);
                });
                $excel->download();
            });
        }
        else 
        {
            $usuarios = $usuarios->paginate();
            $usuarios->setPath($request->fullUrl());
            return view('panel.lista-usuarios',
            [
                'usuarios' => $usuarios,
                'tipos_usuarios' => TipoUsuario::permitido()->get(),
                'cooperativas'=>Cooperativa::permitida()->where('estado','A')->get(),
                'tipo_usuario_valor' => Auth::user()->tipo_usuario->valor,
                'unidades' => Unidad::permitida()->where('estado','A')->get(),
                'opcion'=> $estado,
                'coop' => $request->input('cooperativa')
            ]);
        }
    }
}