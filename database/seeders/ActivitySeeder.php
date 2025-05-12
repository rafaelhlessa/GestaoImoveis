<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            ['name' => 'Advogado', 'evaluation_permission' =>false],
            ['name' => 'Contador', 'evaluation_permission' =>false],
            ['name' => 'Corretor de Imóveis', 'evaluation_permission' =>true],
            ['name' => 'Despachante', 'evaluation_permission' =>false],
            ['name' => 'Engenheiro', 'evaluation_permission' =>false],
            ['name' => 'Avaliador', 'evaluation_permission' =>true],
            ['name' => 'Perito', 'evaluation_permission' =>true],
            ['name' => 'Outra Atividade', 'evaluation_permission' =>false],
        ];

        // Insere os registros na tabela
        DB::table('activity')->insert($activities);
    }
}
