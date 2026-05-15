<?php

use Illuminate\Support\Facades\Route;

Route::group([], function (){
  Route::post('/command', 'CommandApiController@send');
  Route::post('/command/start-logs', 'CommandApiController@sendCommandToStartReadingLogs');
  Route::get('/command/read-logs', 'CommandApiController@getLogFileTextReversed');
});

Route::post('test', function(){
	dd("hola, esto es una prueba");
});
Route::post('logintest', 'UserApiController@login');

Route::group(['middleware' => ['auth.basic', 'api']], function () {
	Route::post('login', 'UserApiController@login');
	Route::get('unidades', 'UnidadApiController@index');
	Route::post('{id}/recorrido', 'UnidadApiController@obtenerHistorial');
	Route::get('cooperativas', 'CooperativaApiController@index');
	Route::post('cooperativas/getrutas', 'CooperativaApiController@getRutas');
	Route::post('cooperativas/getcoordenadas', 'CooperativaApiController@getCoordenadas');
	Route::post('despachos/socios', 'DespachoApiController@getDespachosSocios');
	Route::post('despachos/punto', 'DespachoApiController@getPuntoControl');
});
Route::post('/recorrido-notify', 'RecorridoController@notify');


Route::group(['prefix' => 'v2'], function () {
	Route::group([], function () {
		Route::post('/command', 'CommandApiController@send_v2');
		Route::post('/command/start-logs', 'CommandApiController@sendCommandToStartReadingLogs_v2');
		Route::get('/command/read-logs', 'CommandApiController@getLogFileTextReversed_v2');
	});

	Route::group(['middleware' => ['auth.basic', 'api']], function () {
		Route::post('login', 'UserApiController@login_v2');
		Route::post('logout', 'UserApiController@logout_v2');
		Route::post('users/{id}/cambiar-contrasena', 'UserApiController@cambiarContrasena');

		Route::get('unidades', 'UnidadApiController@index_v2');
		Route::post('unidades/vigilante', 'UnidadApiController@actualizarVigilante_v2');
		Route::post('{id}/recorrido', 'UnidadApiController@obtenerHistorial_v2');
		Route::get('cooperativas', 'CooperativaApiController@index_v2');
		Route::post('cooperativas/getrutas', 'CooperativaApiController@getRutas_v2');
		Route::post('cooperativas/getcoordenadas', 'CooperativaApiController@getCoordenadas_v2');
		Route::post('despachos/socios', 'DespachoApiController@getDespachosSocios_v2');
		Route::post('despachos/punto', 'DespachoApiController@getPuntoControl_v2');
		Route::post('puntos-control/buscar', 'PuntoControlApiController@search');
		Route::post('puntos-control/buscar-json', 'PuntoControlApiController@searchJson');
		Route::post('puntos-control', 'PuntoControlApiController@store');
		Route::post('puntos-control/{id}', 'PuntoControlApiController@update');
		Route::get('puntos-control/{id}', 'PuntoControlApiController@show');

		Route::post('historico/unidades-meta', 'HistoricoApiController@getUnidadesMeta');
		Route::post('historico/reproductor', 'HistoricoApiController@getHistoricoReproductor');
		Route::post('device-token', 'DeviceTokenApiController@register_v2');
		Route::get('notification-types', 'UserNotificationSettingsApiController@types_v2');
		Route::get('user-notification-settings', 'UserNotificationSettingsApiController@index_v2');
		Route::post('user-notification-settings', 'UserNotificationSettingsApiController@store_v2');

		Route::get('photo-unidades', 'PhotoUnidadApiController@index');
		Route::get('photo-unidades/{id}/imagen', 'PhotoUnidadApiController@showImage');

	});

	Route::post('/recorrido-notify', 'RecorridoController@notify_v2');
});

