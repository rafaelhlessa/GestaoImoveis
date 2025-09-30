<?php

require 'vendor/autoload.php';

use App\Models\PropertyDocument;
use Illuminate\Support\Facades\Storage;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Convertendo documentos Base64 para o sistema de arquivos...\n\n";

$documents = PropertyDocument::whereNotNull('file')
    ->whereNull('file_path')
    ->get();

echo "📊 Encontrados " . $documents->count() . " documentos para converter.\n\n";

$converted = 0;
$errors = 0;

foreach ($documents as $document) {
    try {
        echo "📄 Processando: {$document->file_name} (ID: {$document->id})\n";

        // Decodifica o Base64
        $fileContent = base64_decode($document->file);

        if ($fileContent === false) {
            echo "  ❌ Erro ao decodificar Base64\n";
            $errors++;
            continue;
        }

        // Gera nome único para o arquivo
        $extension = pathinfo($document->file_name, PATHINFO_EXTENSION);
        $uniqueName = 'property_' . $document->property_id . '_' . uniqid() . '.' . $extension;
        $filePath = 'property_documents/' . $uniqueName;

        // Salva no storage
        Storage::disk('local')->put($filePath, $fileContent);

        // Detecta MIME type
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];

        $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';

        // Atualiza o registro no banco
        $document->update([
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => strlen($fileContent),
            // Não remove o Base64 ainda para compatibilidade
        ]);

        echo "  ✅ Convertido com sucesso! Tamanho: " . strlen($fileContent) . " bytes\n";
        $converted++;

    } catch (Exception $e) {
        echo "  ❌ Erro: " . $e->getMessage() . "\n";
        $errors++;
    }

    echo "\n";
}

echo "🎉 CONVERSÃO CONCLUÍDA!\n";
echo "✅ Convertidos: {$converted}\n";
echo "❌ Erros: {$errors}\n";
echo "📁 Arquivos salvos em: storage/app/property_documents/\n";
