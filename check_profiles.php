<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Verificando perfis criados:\n\n";

$profiles = App\Models\Profile::all();

foreach ($profiles as $profile) {
    echo "✅ ID: {$profile->id} | Nome: {$profile->name} | Slug: {$profile->slug}\n";
}

echo "\n📊 Total de perfis: " . $profiles->count() . "\n";
