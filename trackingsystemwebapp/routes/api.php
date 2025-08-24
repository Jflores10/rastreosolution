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
