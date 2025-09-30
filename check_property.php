<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PropertyDocument;

$doc = PropertyDocument::find(3);
if ($doc) {
    echo 'Documento KML ID: ' . $doc->id . PHP_EOL;
    echo 'Property ID: ' . $doc->property_id . PHP_EOL;
    echo 'URL para testar: http://localhost:8000/property/' . $doc->property_id . PHP_EOL;
    echo 'URL do KML: http://localhost:8000/property/kml/' . $doc->id . PHP_EOL;
}
