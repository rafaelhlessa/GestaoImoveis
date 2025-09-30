<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PropertyDocument;

// Ler o conteúdo do arquivo de exemplo
$kmlContent = file_get_contents('exemplo.kml');
$base64Content = base64_encode($kmlContent);

$doc = PropertyDocument::where('file_name', 'like', '%.kml')->first();
if ($doc) {
    $doc->file = $base64Content;
    $doc->save();

    echo 'Documento KML atualizado com sucesso!' . PHP_EOL;
    echo 'ID: ' . $doc->id . PHP_EOL;
    echo 'Novo tamanho: ' . strlen($kmlContent) . ' bytes' . PHP_EOL;
} else {
    echo 'Nenhum documento KML encontrado.' . PHP_EOL;
}
