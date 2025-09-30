<?php

require 'vendor/autoload.php';

use App\Models\PropertyDocument;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$doc = PropertyDocument::find(4);

if ($doc) {
    echo "ID: " . $doc->id . "\n";
    echo "Nome: " . $doc->file_name . "\n";
    echo "Tamanho Base64: " . strlen($doc->file) . " bytes\n";
    echo "Primeiros 100 chars Base64: " . substr($doc->file, 0, 100) . "\n";

    $decoded = base64_decode($doc->file);
    echo "Tamanho decodificado: " . strlen($decoded) . " bytes\n";
    echo "Primeiros 100 chars decodificados: " . addcslashes(substr($decoded, 0, 100), "\0..\31\177..\377") . "\n";
    echo "É XML válido?: " . (substr(trim($decoded), 0, 5) === '<?xml' ? 'SIM' : 'NÃO') . "\n";

    // Verifica se é válido Base64
    echo "Base64 válido?: " . (base64_encode(base64_decode($doc->file)) === $doc->file ? 'SIM' : 'NÃO') . "\n";

} else {
    echo "Documento ID 4 não encontrado\n";
}
