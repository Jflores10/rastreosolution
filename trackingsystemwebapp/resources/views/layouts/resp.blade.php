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
                  @if(Auth::user()->tipo_usuario->valor=='1')
                  <li><a href="{{ url('/cooperativas') }}"><i class="fa fa-building"></i> Cooperativas</a>
                  </li>
                  <li>
                    <a href="{{ route('logs.index') }}"><i class="fa fa-cog"></i> Logs de errores</a>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4')
                  <li><a><i class="fa fa-bus"></i> Unidades <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      @if(Auth::user()->tipo_usuario->valor=='1')
                        <li><a href="{{ url('/tipos-de-unidades') }}">Tipos de unidades</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4')
                         <li><a href="{{ url('/unidades') }}">Ver listado</a></li>
                      @endif
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='4')
                      <li><a href="{{ url('/historico') }}">Ver histórico</a></li>
                      @endif
                    </ul>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2' || Auth::user()->tipo_usuario->valor=='3')
                  <li><a><i class="fa fa-desktop"></i> Despachos <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                        <li><a href="{{ url('/puntos-de-control') }}">Puntos de control</a></li>
                        <li><a href="{{ url('/rutas') }}">Rutas</a></li>
                      @endif
                      @if (Auth::user()->cooperativa == null || (Auth::user()->cooperativa->taxis == null || Auth::user()->cooperativa->taxis === false))
                        @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3')
                          <li><a href="{{ url('/despachos') }}">Listado de despachos</a></li>

                        @endif
                        @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2'||Auth::user()->tipo_usuario->valor=='3')
                          <li><a href="{{ url('/reportes') }}">Reportes de despachos</a></li>
                        @endif
                      @endif
                    </ul>
                  </li>
                  @endif
                  @if(Auth::user()->tipo_usuario->valor=='1' || Auth::user()->tipo_usuario->valor=='2')
                     <li><a href="{{ url('/conductores') }}"><i class="fa fa-group"></i> Conductores</a>
                      </li>
                      <li><a href="{{ url('/usuarios') }}"><i class="fa fa-users"></i> Usuarios</a>
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
            </nav>
          </div>
        </div>
        <div class="right_col" role="main">
            @yield('content')
        </div>
        <footer>
          <div class="pull-right">
            &copy; {{ date('Y') }}, {{ config('app.name') }} {{ config('app.version') }}</a>
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