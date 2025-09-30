<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PropertyDocument;

$doc = PropertyDocument::where('file_name', 'like', '%.kml')->first();
if ($doc) {
    echo 'ID: ' . $doc->id . PHP_EOL;
    echo 'Name: ' . $doc->name . PHP_EOL;
    echo 'File Name: ' . $doc->file_name . PHP_EOL;
    echo 'Has file: ' . ($doc->file ? 'Yes (' . strlen($doc->file) . ' chars)' : 'No') . PHP_EOL;
    if ($doc->file) {
        $decoded = base64_decode($doc->file);
        echo 'Decoded size: ' . strlen($decoded) . ' bytes' . PHP_EOL;
        echo 'First 200 chars: ' . substr($decoded, 0, 200) . PHP_EOL;
        echo 'Last 200 chars: ' . substr($decoded, -200) . PHP_EOL;
    }
} else {
    echo 'Nenhum documento KML encontrado.' . PHP_EOL;
}
