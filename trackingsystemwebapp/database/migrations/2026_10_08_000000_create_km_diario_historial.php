<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/**
 * Km diario por unidad (MongoDB: los campos no requieren columnas).
 * - unidads: mileage_inicio_dia, km_diario, fecha_km_diario ('Y-m-d', America/Guayaquil).
 * - km_diario_historial: un documento por unidad y día cerrado.
 */
class CreateKmDiarioHistorial extends Migration
{
    public function up()
    {
        Schema::create('km_diario_historial', function ($collection) {
            $collection->unique(['unidad_id', 'fecha']);
            $collection->index('fecha');
        });

        // Lo usa el reinicio diario para encontrar unidades con el día vencido.
        Schema::table('unidads', function ($collection) {
            $collection->index('fecha_km_diario');
        });
    }

    public function down()
    {
        Schema::table('unidads', function ($collection) {
            $collection->dropIndex('fecha_km_diario');
        });
        Schema::drop('km_diario_historial');
    }
}
