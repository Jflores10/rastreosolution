<?php



Auth::routes();


Route::group(['middleware' => ['auth', 'usuario']], function () {
    Route::get('sesiones', 'SesionController@index');
    Route::get('/', 'HomeController@index');
    Route::post('/home', 'HomeController@search');
    Route::get('/home', 'HomeController@index');
    Route::get('/puntos', 'HomeController@cargarPuntosControl');
    Route::get('/puntos-atm', 'HomeController@cargarPuntosControlATM');
    Route::get('/homeUniCoop', 'HomeController@getVistaNueva');
    Route::get('/dashboard', 'DashboardController@index');
    Route::group(['prefix' => 'dashboard'], function () {
        Route::match(['get', 'post'], '/general/{id}', 'DashboardController@recargarPorCooperativa');
        Route::match(['get', 'post'], '/de', 'DashboardController@recargarDespachosEstados');
        Route::match(['get', 'post'], '/ed', 'DashboardController@recargarExportacionDespachos');
        Route::match(['get', 'post'], '/ct', 'DashboardController@recargarCortesTubo');
        Route::match(['get', 'post'], '/vg', 'DashboardController@recargarVelocidadGeneral');
        Route::match(['get', 'post'], '/vu', 'DashboardController@recargarVelocidadUnidad');
    });

    Route::get('/ubicacion', 'HomeController@ubicacion');

    Route::get('/getRecorrido/{id}', 'UnidadController@getRecorrido');


    Route::group(['prefix' => 'tipos-de-unidades'], function () {
        Route::match(['get', 'post'], '/search', 'TipoUnidadController@search');
    });
    Route::resource('tipos-de-unidades', 'TipoUnidadController');


    Route::group(['prefix' => 'cooperativas'], function () {
        Route::match(['get', 'post'], '/search', 'CooperativaController@search');
        Route::post('/getCooperativas', 'CooperativaController@getCooperativas');
    });
    Route::resource('cooperativas', 'CooperativaController');


    Route::group(['prefix' => 'conductores'], function () {
        Route::match(['get', 'post'], '/search', 'ConductorController@search');
    });
    Route::resource('conductores', 'ConductorController');


    Route::group(['prefix' => 'usuarios'], function () {
        Route::match(['get', 'post'], '/search', 'UsuarioController@search');
        Route::post('/eliminar/{id}', 'UsuarioController@eliminar');
    });
    Route::resource('usuarios', 'UsuarioController');


    Route::group(['prefix' => 'puntos-de-control'], function () {
        Route::match(['get', 'post'], '/search', 'PuntoControlController@search');
        Route::post('/search-json', 'PuntoControlController@searchJSON');
    });
    Route::resource('puntos-de-control', 'PuntoControlController');

    Route::group(['prefix' => 'puntos-de-control-atm'], function () {
        Route::match(['get', 'post'], '/search', 'PuntoControlATMController@search');
    });
    Route::resource('puntos-de-control-atm', 'PuntoControlATMController');


    Route::group(['prefix' => 'unidades'], function () {
        Route::match(['get', 'post'], '/search', 'UnidadController@search');
        Route::get('/', 'UnidadController@index');
        Route::get('{cooperativa}/lista', 'UnidadController@cargarPorCooperativa')->name('unidades.lista');
    });
    Route::resource('unidades', 'UnidadController');

    Route::group(['prefix' => 'historico'], function () {
        Route::get('/', 'HistoricoController@index');
    });
    Route::resource('historicos', 'HistoricoController');

    Route::group(['prefix' => 'despachos'], function () {
        Route::get('/{cooperativa}/unidades', 'DespachoController@getUnidades');
        Route::get('/{unidad_id}/{cooperativa_id}/ultimo_despacho', 'DespachoController@getUltimoDespacho');
        Route::get('/{cooperativa}/conductores', 'DespachoController@getConductores');
        Route::get('/{cooperativa}/rutas', 'DespachoController@getRutas');
        Route::get('/{id}/finish', 'DespachoController@end');
        Route::post('/{id}/cancel', 'DespachoController@cancel');
        Route::get('/error/{id}', 'DespachoController@errorATM');
        Route::post('/reenviarATM/{id}', 'DespachoController@reenviarATM');
        Route::get('/frecuencias', 'DespachoController@frecuencias');
        Route::get('/cancelados', 'DespachoController@canceladas');
        Route::get('/search', 'DespachoController@find');
        Route::post('/cortetubo', 'DespachoController@cortetubo');
        Route::post('/{id}/despachomasivo', 'DespachoController@despachomasivo');
        Route::post('/despachosfinalizar', 'ReporteController@finalizarTodo');
        Route::post('/despachosfinalizar/despacho', 'DespachoController@finalizarTodo');
        Route::get('/info/{id}', 'DespachoController@infoPrint');
        Route::get('/ticketalbosao/{id}', 'DespachoController@showAlbosau');
    });
    Route::resource('/despachos', 'DespachoController');

    Route::get('reproductor/{despachoId}', 'DespachoController@reproductor')->name('despachos.reproductor');

    Route::post('/reportes', 'ReporteController@search');
    Route::get('/reportes/cargar/{id}', 'ReporteController@cargar');
    Route::get('/reportes', 'ReporteController@index');
    Route::post('/reportes/una-hoja', 'ReporteController@reporteGeneral');
    Route::post('/reportes/general', 'ReporteController@generarReporte');
    Route::post('/reportes/rutas', 'ReporteController@reportePorRutas');
    Route::post('/reportes/rutasnovueltas', 'ReporteController@reportePorRutasNoVueltas');
    Route::get('/reportes/multasrutas', 'ReporteController@reportePorRutasCobros');
    Route::get('/reportes/multasticket', 'ReporteController@searchTicketMulta');
    Route::post('/reportes/diario', 'ReporteController@generarReporteDiario');
    Route::post('/reportes/multas', 'ReporteController@generarReportePorUnidad');
    Route::post('/reportes/multasunidades', 'ReporteController@generarReportePorUnidad_DosUnidades');


    Route::group(['prefix' => 'rutas'], function () {
        Route::match(['get', 'post'], '/search', 'RutaController@search');
        Route::get('/', 'RutaController@index');
        Route::get('/{cooperativa}/listar', 'RutaController@listar');
    });
    Route::resource('rutas', 'RutaController');

    Route::group(['prefix' => 'rutas-atm'], function () {
        Route::match(['get', 'post'], '/search', 'RutaATMController@search');
        Route::get('/', 'RutaATMController@index');
    });

    Route::resource('rutas-atm', 'RutaATMController');

    Route::post('/consulta', 'RutaController@consulta');
    Route::get('/rutasclone/{id}', 'RutaController@clonar');

    Route::get('/perfil-usuario', 'PerfilUsuarioController@index');
    Route::resource('perfil-usuario', 'PerfilUsuarioController');

    Route::get('tramas', 'HomeController@tramas');
    Route::get('consola', 'HomeController@consola');
    Route::get('en-linea/{ruta}', 'HomeController@en_linea');
    Route::get('en-linea', 'HomeController@showEnLinea');

    Route::group(['prefix' => 'puntos-virtuales'], function () {
        Route::get('search', 'PuntoVirtualController@search')->name('puntos-virtuales.search');
    });
    Route::resource('puntos-virtuales', 'PuntoVirtualController', ['except' => ['show']]);
    Route::group(['prefix' => 'logs'], function () {
        Route::get('/', 'LogController@index')->name('logs.index');
        Route::get('/search', 'LogController@search')->name('logs.search');
    });

    Route::group(['prefix' => 'reportes-unidades'], function () {
        Route::get('puertas', 'ReportePuertasController@index')->name('puertas.index');
        Route::get('velocidad', 'ReporteVelocidadController@index')->name('velocidad.index');
        Route::get('cortetubo', 'ReporteCorteTubo@index')->name('cortetubo.index');
    });

    Route::match(['get', 'post'], '/importar', 'DespachoController@importar')->name('importar');

    Route::get('/liquidacion', 'LiquidacionController@index');

    Route::get('/bitacora', 'BitacoraController@index');
    Route::get('/bitacora/finalizados', 'BitacoraController@finalizados');
    Route::get('/bitacora/search', 'BitacoraController@find');
    Route::post('/bitacora/unidades', 'BitacoraController@getBitacorasUnidades');
    Route::get('/bitacora/{cooperativa}/unidades', 'BitacoraController@getUnidades');
    Route::resource('/bitacora', 'BitacoraController');
    Route::delete('{id}/sorteo', 'SorteoApiController@eliminarSorteo');
    Route::match(['get', 'post'], 'sorteos', 'SorteoApiController@index');
    Route::get('sorteo', 'SorteoApiController@cargarSorteo');
    Route::get('sorteos/imprimir', 'SorteoApiController@imprimir');

    Route::post('/unidad/conteo', 'UnidadController@resetConteo');

    Route::match(['get', 'post'], 'historico-atm', 'HistoricoController@historicoAtm');
});

Route::get('/usuarioSuspendido', 'UsuarioController@usuarioSuspendido');

Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

Route::get('/reproductor', function () {
    return view('panel.reproductor');
});

Route::get('/geocoding/reverse', 'HomeController@reverseProxy')->name('geocoding.reverse');
