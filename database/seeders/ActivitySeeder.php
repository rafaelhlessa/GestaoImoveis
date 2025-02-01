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
            ['name' => 'Advogado'],
            ['name' => 'Contador'],
            ['name' => 'Corretor de Imóveis'],
            ['name' => 'Despachante'],
            ['name' => 'Engenheiro'],
            ['name' => 'Outra Atividade'],
        ];

        // Insere os registros na tabela
        DB::table('activity')->insert($activities);
    }
}
