@extends('layouts.app')
@section('title')
    Sorteos
@endsection
@section('content')
    <div id="app">
        <sorteos :cooperativas="{{ $cooperativas->toJson() }}" :tipo-usuario="{{ $tipo_usuario }}" :unidades="{{ $unidades->toJson() }}"></sorteos>
    </div>
@endsection

@section('scripts')
    <script>
        $('#fecha').datepicker({
            dateFormat : 'yy-mm-dd'
        });
    </script>
@endsection