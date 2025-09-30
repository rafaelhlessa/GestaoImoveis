<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(22);
$proprietario = \App\Models\Profile::where('slug', 'proprietario')->first();

if ($user && $proprietario) {
    // Verificar se já tem o perfil
    if (!$user->profiles->contains($proprietario->id)) {
        $user->profiles()->attach($proprietario->id);
        echo "Perfil 'proprietario' adicionado ao usuário Rafael.\n";
    } else {
        echo "Usuário Rafael já possui o perfil 'proprietario'.\n";
    }

    // Mostrar perfis atuais
    echo "Perfis atuais: " . $user->fresh()->profiles->pluck('slug')->implode(', ') . "\n";
} else {
    echo "Erro: Usuário ou perfil não encontrado.\n";
}
