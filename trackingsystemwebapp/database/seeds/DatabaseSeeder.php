<?php

use Illuminate\Database\Seeder;
use App\TipoUsuario;
use App\User;
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipo = TipoUsuario::create([
            'descripcion' => 'Distribuidor',
            'valor' => 1
        ]);
        TipoUsuario::create([
            'descripcion' => 'Administrador de cooperativas',
            'valor' => 2
        ]);
        TipoUsuario::create([
            'descripcion' => 'Despachador',
            'valor' => 3
        ]);
        TipoUsuario::create([
            'descripcion' => 'Socio',
            'valor' => 4
        ]);
       
    }
}
