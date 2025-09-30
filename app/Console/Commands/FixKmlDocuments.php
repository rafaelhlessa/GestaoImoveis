<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyDocument;
use Illuminate\Support\Facades\Storage;

class FixKmlDocuments extends Command
{
    protected $signature = 'property:fix-kml';
    protected $description = 'Fix corrupted KML documents and ensure proper file storage';

    public function handle()
    {
        $this->info('🔧 Iniciando correção de documentos KML...');

        // Buscar documentos KML que podem estar corrompidos
        $kmlDocuments = PropertyDocument::where(function($query) {
            $query->where('file_name', 'like', '%.kml')
                  ->orWhere('file_name', 'like', '%.kmz');
        })->get();

        $this->info("📊 Encontrados {$kmlDocuments->count()} documentos KML/KMZ");

        $fixed = 0;
        $corrupted = 0;

        foreach ($kmlDocuments as $document) {
            $this->line("📄 Verificando: {$document->file_name} (ID: {$document->id})");

            // Verificar se o arquivo no storage existe e é válido
            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                $content = Storage::disk('local')->get($document->file_path);

                if (strlen($content) > 50 && $this->isValidXml($content)) {
                    $this->info("  ✅ Arquivo no storage é válido");
                    continue;
                }
            }

            // Verificar se o Base64 é válido
            if ($document->file) {
                $decoded = base64_decode($document->file);

                if ($decoded !== false && strlen($decoded) > 50 && $this->isValidXml($decoded)) {
                    $this->line("  🔄 Restaurando do Base64...");

                    // Criar arquivo no storage a partir do Base64
                    $extension = pathinfo($document->file_name, PATHINFO_EXTENSION);
                    $uniqueName = 'property_' . $document->property_id . '_' . uniqid() . '.' . $extension;
                    $filePath = 'property_documents/' . $uniqueName;

                    Storage::disk('local')->put($filePath, $decoded);

                    // Atualizar o documento
                    $document->update([
                        'file_path' => $filePath,
                        'mime_type' => $extension === 'kml' ? 'application/vnd.google-earth.kml+xml' : 'application/vnd.google-earth.kmz',
                        'file_size' => strlen($decoded)
                    ]);

                    $this->info("  ✅ Arquivo restaurado no storage");
                    $fixed++;
                    continue;
                }
            }

            $this->error("  ❌ Documento corrompido ou inválido");
            $corrupted++;
        }

        $this->info("\n🎉 Correção concluída!");
        $this->table(['Status', 'Quantidade'], [
            ['Corrigidos', $fixed],
            ['Corrompidos', $corrupted],
            ['Total', $kmlDocuments->count()]
        ]);

        return 0;
    }

    private function isValidXml($content)
    {
        $content = trim($content);

        // Verificar se começa com <?xml ou <kml
        if (!str_starts_with($content, '<?xml') && !str_starts_with($content, '<kml')) {
            return false;
        }

        // Tentar parsear como XML
        $previousSetting = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        libxml_use_internal_errors($previousSetting);

        return $doc !== false;
    }
}
