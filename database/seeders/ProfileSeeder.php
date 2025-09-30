<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profile;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Perfis administrativos - Apenas para uso interno/futuro sistema de gestão
        Profile::updateOrCreate([
            'slug' => 'administrador',
        ], [
            'name' => 'Administrador',
        ]);

        Profile::updateOrCreate([
            'slug' => 'gestor',
        ], [
            'name' => 'Gestor',
        ]);

        // Perfis públicos - Disponíveis para seleção pelos usuários
        Profile::updateOrCreate([
            'slug' => 'proprietario',
        ], [
            'name' => 'Proprietário',
        ]);

        Profile::updateOrCreate([
            'slug' => 'prestador',
        ], [
            'name' => 'Prestador de Serviço',
        ]);
    }
}
