<!DOCTYPE html>
<html lang="es">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name') }} | @yield('title')</title>
    <!-- Bootstrap -->
    <link href="{{ asset('vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="{{ asset('vendors/bootstrap/dist/css/bootstrap-switch.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ asset('vendors/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- iCheck -->
    <link href="{{ asset('vendors/iCheck/skins/flat/green.css') }}" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="{{ asset('vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css') }}" rel="stylesheet">
    <!-- JQVMap -->
    <link href="{{ asset('vendors/jqvmap/dist/jqvmap.min.css') }}" rel="stylesheet"/>

    <!-- Custom Theme Style -->
    <link href="{{ asset('build/css/custom.min.css') }}" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="{{ asset('/js/jspanel/jspanel.min.css') }}" rel="stylesheet">

    <link href="{{ asset('/js/chosen/chosen.min.css') }}" rel="stylesheet" />

    <link href="{{ asset('/js/jquery-ui/jquery-ui.min.css') }}" rel="stylesheet" />

    <link href="{{ asset('/js/timepicker/css/bootstrap-material-datetimepicker.css') }}" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="{{ asset('/js/datetime/jquery.datetimepicker.css')}}">

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" />

    <link href="https://fonts.googleapis.com/css?family=Ubuntu:500" rel="stylesheet"/>

    <link rel="stylesheet" type="text/css" href="{{ asset('addons/datatables/datatables.min.css') }}"/>

    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}"/>

    @yield('styles')
  </head>

  <body class="nav-md">

  
  @if(Auth::check())
    @if(Auth::user()->estado=='A')
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">
          <div class="left_col scroll-view">

            <!-- menu profile quick info -->
            <div class="profile">
              <img height="100%" width="100%" src="/images/logo.png" />
              <div class="profile_pic">
                <img src="{{ asset('images/user.png') }}" alt="..." class="img-circle profile_img">
              </div>
              <div class="profile_info">
                <span>Bienvenido,</span>
                 <h2>{{Auth::user()->name}}</h2>
              </div>
            </div>
            <!-- /menu profile quick info -->
            <br/>
            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">

                <h3>General</h3>
                <ul class="nav side-menu">
                  <li><a href="{{ url('/home') }}"><i class="fa fa-home"></i> Inicio</a>
                  </li>
                  <li><a href="{{ url('/dashboard') }}"><i class="fa fa-bar-chart"></i> Dashboard</a>
                  </li>
                  @if(Auth::user()->tipo_usuario->valor=='1')
                  <li><a href="{{ url('/cooperativas') }}"><i class="fa fa-building"></i> Cooperativas</a>
                  </li>
                  <li>
                    <a href="{{ route('logs.index') }}"><i class="fa fa-cog"></i> Logs de errores</a>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4'  || Auth::user()->tipo_usuario->valor=='5')
                  <li><a><i class="fa fa-bus"></i> Unidades <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      @if(Auth::user()->tipo_usuario->valor=='1')
                        <li><a href="{{ url('/tipos-de-unidades') }}">Tipos de unidades</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4'  || Auth::user()->tipo_usuario->valor=='5')
                         <li><a href="{{ url('/unidades') }}">Ver listado</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4'  || Auth::user()->tipo_usuario->valor=='5')
                        <li><a href="{{ url('/historico') }}">Ver histórico</a></li>
                        <li><a href="{{ route('velocidad.index') }}">Reporte de velocidades</a></li>
                        <li><a href="{{ route('puertas.index') }}">Reporte de puertas</a></li>
                        <li><a href="{{ route('cortetubo.index') }}">Reporte de corte de tubo</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='3'  || Auth::user()->tipo_usuario->valor=='5')
                        <li><a href="{{ url('/bitacora') }}">Bitacora Unidades</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                        <li><a href="{{ url('/historico-atm') }}">Historico ATM</a></li>
                      @endif
                    </ul>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='3'  || Auth::user()->tipo_usuario->valor=='4'
                   || Auth::user()->tipo_usuario->valor=='5')
                  <li><a><i class="fa fa-desktop"></i> Despachos <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                        <li><a href="{{ url('/puntos-de-control') }}">Puntos de control</a></li>
                        <li><a href="{{ url('/puntos-de-control-atm') }}">Puntos de control ATM</a></li>
                        <li><a href="{{ url('/rutas') }}">Rutas</a></li>
                        <li><a href="{{ url('/rutas-atm') }}">Rutas ATM</a></li>
                      @endif
                      @if (Auth::user()->cooperativa == null || (Auth::user()->cooperativa->taxis == null || Auth::user()->cooperativa->taxis === false))
                        @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3' 
                         || Auth::user()->tipo_usuario->valor=='5')
                          <li><a href="{{ url('/despachos') }}">Listado de despachos</a></li>

                        @endif
                        @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3' 
                         || Auth::user()->tipo_usuario->valor=='5')
                          @php
                            $cooperativa = Auth::user()->cooperativa;
                            $showImporter = Auth::user()->tipo_usuario->valor=='1' || ($cooperativa && $cooperativa->importador_despachos);
                          @endphp
                          <li><a href="{{ url('/importar') }}">Importar despachos</a></li>

                        @endif
                        @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3'  
                        || Auth::user()->tipo_usuario->valor=='4'  || Auth::user()->tipo_usuario->valor=='5')
                          <li><a href="{{ url('/reportes') }}">Reportes de despachos</a></li>
                        @endif

                         {{-- @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3'
                          || Auth::user()->tipo_usuario->valor=='5')
                          <li><a href="{{ url('/liquidacion') }}">Liquidación de despachos</a></li>
                        @endif --}}
                        @if(Auth::user()->tipo_usuario->valor=='1' || (Auth::user()->tipo_usuario->valor=='2') || (Auth::user()->tipo_usuario->valor=='3') 
                        || (Auth::user()->tipo_usuario->valor=='4')  || Auth::user()->tipo_usuario->valor=='5')
                          <li><a href="{{ url('/sorteos') }}">Sorteos</a></li>
                        @endif

                      @endif
                    </ul>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                     <li><a href="{{ url('/conductores') }}"><i class="fa fa-group"></i> Conductores</a>
                      </li>
                      <li><a><i class="fa fa-users"></i> Usuarios <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                          <li><a href="{{ url('/usuarios') }}">Lista Usuarios</a></li>
                          @if (Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                            <li><a href="{{ url('/sesiones') }}">Sesiones Usuarios</a></li>
                          @endif
                        </ul>
                      </li>
                  @endif
                  <li><a href="{{ url('/logout') }}"><i class="fa fa-sign-out"></i>Salir</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
              </div>
              <ul class="nav navbar-nav navbar-right">
                <li class="">
                  <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <img src="{{ asset('images/user.png') }}" alt="">{{Auth::user()->name}}
                    <span class=" fa fa-angle-down"></span>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right">
                    <li><a href="{{ url('/perfil-usuario') }}"><i class="fa fa-user pull-right"></i> Mi cuenta</a></li>
                    <li><a href="{{ url('/logout') }}"><i class="fa fa-sign-out pull-right"></i> Salir</a></li>
                  </ul>
                </li>
                @if (Auth::user()->cooperativa == null || (Auth::user()->cooperativa->taxis == null || Auth::user()->cooperativa->taxis === false))
                  <li>
                    <a href="#" onclick="window.open('/despachos', '', 'width=500,height=600');">Despachos</a>
                  </li>
                @endif
              </ul>
              <div class="navbar-center" style="position: absolute; left: 50%; transform: translateX(-50%); margin-top: 5px;">
                <div id="reloj" style="
                    font-weight: bold; 
                    font-size: 18px; 
                    color: #fff; 
                    background: linear-gradient(45deg, #2A3F54, #1E2A38);
                    padding: 10px 20px; 
                    border-radius: 25px; 
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); 
                    text-align: center;
                    font-family: 'Ubuntu', sans-serif;">
                  00:00:00
                </div>
              </div>
            </nav>
          </div>
        </div>
        <div class="right_col" role="main">
            @yield('content')
        </div>
        <footer>
          <div class="pull-left">
            &copy; {{ date('Y') }}, {{ config('app.name') }} {{ config('app.version') }} </a>  <a href="">Infinity Solutions</a>
          </div>
          <div class="clearfix"></div>
        </footer>
      </div>
    </div>
    <div class="modal fade" id="progress" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="myModalLabel">Cargando...</h4>
          </div>
          <div class="modal-body">
            <div class="progress">
              <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
      @else
      <script>
        window.location.href = '{{ url('/usuarioSuspendido') }}';
      </script>
      @endif

    @else
    <script>
      window.location.href = '{{url('/login')}}';
    </script>
    @endif

    <!-- jQuery -->
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <!-- Bootstrap -->
    <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('vendors/fastclick/lib/fastclick.js') }}"></script>
    <!-- NProgress -->
    <script src="{{ asset('vendors/nprogress/nprogress.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('vendors/Chart.js/dist/Chart.min.js') }}"></script>
    <!-- gauge.js -->
    <script src="{{ asset('vendors/gauge.js/dist/gauge.min.js') }}"></script>
    <!-- bootstrap-progressbar -->
    <script src="{{ asset('vendors/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
    <!-- iCheck -->
    <script src="{{ asset('vendors/iCheck/icheck.min.js') }}"></script>
    <!-- Skycons -->
    <script src="{{ asset('vendors/skycons/skycons.js') }}"></script>
    <!-- Flot -->
    <script src="{{ asset('vendors/Flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.time.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('vendors/Flot/jquery.flot.resize.js') }}"></script>
    <!-- Flot plugins -->
    <script src="{{ asset('vendors/flot.orderbars/js/jquery.flot.orderBars.js') }}"></script>
    <script src="{{ asset('vendors/flot-spline/js/jquery.flot.spline.min.js') }}"></script>
    <script src="{{ asset('vendors/flot.curvedlines/curvedLines.js') }}"></script>
    <!-- DateJS -->
    <script src="{{ asset('vendors/DateJS/build/date.js') }}"></script>
    <!-- JQVMap -->
    <script src="{{ asset('vendors/jqvmap/dist/jquery.vmap.js') }}"></script>
    <script src="{{ asset('vendors/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('vendors/jqvmap/examples/js/jquery.vmap.sampledata.js') }}"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="{{ asset('js/moment/moment.min.js') }}"></script>
    <script src="{{ asset('js/datepicker/daterangepicker.js') }}"></script>

    <!-- Custom Theme Scripts -->
    <script src="{{ asset('build/js/custom.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>

    <!-- Bootstrap -->
    <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap-switch.min.js') }}"></script>

    <script src="{{ asset('/js/chosen/chosen.jquery.min.js') }}"></script>

    <script src="{{ asset('/js/jquery-ui/jquery-ui.min.js') }}"></script>

    <script src="{{ asset('/js/timepicker/js/bootstrap-material-datetimepicker.js') }}"></script>

    <script src="{{ asset('/js/datetime/build/jquery.datetimepicker.full.min.js')}}"></script>

    <script src="{{ asset('addons/pagination/jquery-paginate.min.js') }}"></script>

    <script type="text/javascript" src="{{ asset('addons/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('js/jspanel/jspanel.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
  
    <script>
      setInterval(function(){
        var today = new Date();
        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
        document.getElementById('reloj').innerHTML='<font color="white">'+time+'</font>';
      }, 1000);
      
    </script>
  
  </body>
</html>
