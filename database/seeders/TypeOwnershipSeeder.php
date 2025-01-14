<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeOwnershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dados que serão inseridos na tabela type_ownerships
        $types = [
            ['name' => 'Proprietário'],
            ['name' => 'Arrendatário'],
            ['name' => 'Comodato'],
            ['name' => 'Parceria Agrícola'],
            ['name' => 'Aluguel'],
        ];

        // Insere os registros na tabela
        DB::table('type_ownership')->insert($types);
    }
}
