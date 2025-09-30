<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$doc = App\Models\PropertyDocument::find(4);
if ($doc && $doc->file_path) {
    echo 'Arquivo no storage: ' . $doc->file_path . "\n";
    echo 'Arquivo existe?: ' . (Illuminate\Support\Facades\Storage::disk('local')->exists($doc->file_path) ? 'SIM' : 'NÃO') . "\n";
    if (Illuminate\Support\Facades\Storage::disk('local')->exists($doc->file_path)) {
        $content = Illuminate\Support\Facades\Storage::disk('local')->get($doc->file_path);
        echo 'Tamanho: ' . strlen($content) . " bytes\n";
        echo 'Conteúdo: ' . addcslashes(substr($content, 0, 100), "\0..\31\177..\377") . "\n";
        echo 'É XML?: ' . (substr(trim($content), 0, 5) === '<?xml' ? 'SIM' : 'NÃO') . "\n";
    }
} else {
    echo 'Documento não tem file_path' . "\n";
}
