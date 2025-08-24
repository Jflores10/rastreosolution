@extends('layouts.app')
@section('title')
Historico de unidades
@endsection
@section('content')
<div class="row">
    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">Escritorio</a></li>
        <li class="active">Historico</li>
    </ol>
</div>
<historico></historico>
@endsection