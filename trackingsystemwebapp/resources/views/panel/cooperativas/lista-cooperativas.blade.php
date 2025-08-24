@extends('layouts.app')
@section('title')
Cooperativas
@endsection
@section('content')
<div class="row">
    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">Escritorio</a></li>
        <li class="active">Cooperativas</li>
    </ol>
</div>
<cooperativas></cooperativas>
@endsection