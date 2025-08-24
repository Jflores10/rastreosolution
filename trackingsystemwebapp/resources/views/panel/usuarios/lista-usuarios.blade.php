@extends('layouts.app')
@section('title')
    Usuarios
@endsection
@section('content')
    <div class="row">
        <ol class="breadcrumb">
            <li><a href="{{ url('/') }}">Escritorio</a></li>
            <li class="active">Usuarios</li>
        </ol>
    </div>
    <usuarios></usuarios>
@endsection
