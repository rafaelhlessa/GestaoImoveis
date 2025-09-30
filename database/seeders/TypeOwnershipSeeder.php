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
            ['name' => 'Comodato'],
            ['name' => 'Parceria Agricola'],
            ['name' => 'Aluguel'],
            ['name' => 'Arrendamento'],
        ];

        // Insere apenas se não existir ainda
        foreach ($types as $type) {
            DB::table('type_ownership')->updateOrInsert(
                ['name' => $type['name']], // Condição de busca
                $type // Dados para inserir/atualizar
            );
        }
    }
}
