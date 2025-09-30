<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

class MigrateUserProfiles extends Command
{
    protected $signature = 'migrate:user-profiles';
    protected $description = 'Migra os perfis antigos para a nova estrutura acumulável de perfis (profiles)';

    public function handle()
    {
        DB::beginTransaction();
        try {
            $proprietario = Profile::where('slug', 'proprietario')->first();
            $prestador    = Profile::where('slug', 'prestador')->first();
            if (!$proprietario || !$prestador) {
                throw new \Exception('Perfis não encontrados. Rode o seeder antes.');
            }
            $users = User::all();
            foreach ($users as $user) {
                // Migração baseada em profile_id removida, lógica agora deve ser baseada em dados históricos ou outros critérios.
            }
            DB::commit();
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
