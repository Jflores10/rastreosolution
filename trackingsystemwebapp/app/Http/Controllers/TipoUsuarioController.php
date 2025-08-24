<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TipoUsuario;
class TipoUsuarioController extends Controller
{
    public function index() {
        return response()->json(TipoUsuario::orderBy('descripcion')->permitido()->get());
    }
}
