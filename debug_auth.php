<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simular request
$request = new Request();

// Verificar usuário autenticado (se houver)
$user = auth()->user();

echo "=== DEBUG AUTENTICAÇÃO ===\n";
echo "Usuário autenticado: " . ($user ? "SIM" : "NÃO") . "\n";

if ($user) {
    echo "ID: " . $user->id . "\n";
    echo "Nome: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Profiles: " . $user->profiles->pluck('slug')->implode(', ') . "\n";
    echo "Is Admin: " . ($user->is_admin ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "Nenhum usuário autenticado encontrado.\n";
}

// Verificar se existe o usuário Rafael
$rafael = \App\Models\User::where('email', 'rafael@podtech.com.br')->with('profiles')->first();

echo "\n=== USUÁRIO RAFAEL ===\n";
if ($rafael) {
    echo "ID: " . $rafael->id . "\n";
    echo "Nome: " . $rafael->name . "\n";
    echo "Email: " . $rafael->email . "\n";
    echo "Profiles: " . $rafael->profiles->pluck('slug')->implode(', ') . "\n";
    echo "Is Admin: " . ($rafael->is_admin ? 'SIM' : 'NÃO') . "\n";
    echo "Email verificado: " . ($rafael->email_verified_at ? 'SIM' : 'NÃO') . "\n";
    echo "Está ativo: " . ($rafael->is_active ? 'SIM' : 'NÃO') . "\n";
    echo "Token de ativação: " . ($rafael->activation_token ? $rafael->activation_token : 'NENHUM') . "\n";
} else {
    echo "Usuário rafael@podtech.com.br não encontrado.\n";
}

// Verificar profiles disponíveis
echo "\n=== PROFILES DISPONÍVEIS ===\n";
$profiles = \App\Models\Profile::all();
foreach ($profiles as $profile) {
    echo "- {$profile->slug}: {$profile->name}\n";
}

// Verificar autorizações do Rafael como prestador
echo "\n=== AUTORIZAÇÕES DO RAFAEL ===\n";
if ($rafael) {
    $authorizations = \App\Models\Authorization::where('service_provider_id', $rafael->id)->with('owner')->get();

    if ($authorizations->count() > 0) {
        foreach ($authorizations as $auth) {
            echo "Autorização ID: {$auth->id}\n";
            echo "  Proprietário: {$auth->owner->name} ({$auth->owner->email})\n";
            echo "  Visualizar documentos: " . ($auth->can_view_documents ? 'SIM' : 'NÃO') . "\n";
            echo "  Criar propriedades: " . ($auth->can_create_properties ? 'SIM' : 'NÃO') . "\n";
            echo "  Permissão avaliação: " . ($auth->evaluation_permission ? 'SIM' : 'NÃO') . "\n";
            echo "  ---\n";
        }
    } else {
        echo "Nenhuma autorização encontrada para Rafael como prestador.\n";
    }
}
