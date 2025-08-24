@extends('layouts.app_ingreso')
@section('title')
   No se pudo acceder al sitio
@endsection
@section('content')
    <div class="page-title">
        <div class="title_left">

        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
               <br/><br/>
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
                        ×</button>
                    <span class="glyphicon glyphicon-hand-right"></span> <strong>Mensaje de error</strong>
                    <hr class="message-inner-separator">
                    <p>{{$mensaje_acceso}}</p>
                </div>
                @if(isset($suspendido))
                <script>console.log('fdsfdsfdf');</script>
                <div class="modal-footer">
                        <button onclick=" window.location.href = '{{url('/logout')}}';" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Aceptar</button>
                </div>
                @endif

            </div>
        </div>
    </div>

@endsection