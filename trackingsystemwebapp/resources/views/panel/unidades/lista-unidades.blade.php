@extends('layouts.app')
@section('title')
Unidades
@endsection
@section('content')
<div class="row">
    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">Escritorio</a></li>
        <li class="active">Unidades</li>
    </ol>
</div>
<unidades></unidades>
@endsection