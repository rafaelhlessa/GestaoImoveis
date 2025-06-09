<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyDocument;
use Illuminate\Support\Facades\Log;

class DiagnoseKmlDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kml:diagnose {document_id?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica arquivos KML armazenados no banco de dados';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $documentId = $this->argument('document_id');
        $all = $this->option('all');

        if ($documentId) {
            $this->diagnoseSingleDocument($documentId);
        } elseif ($all) {
            $this->diagnoseAllKmlDocuments();
        } else {
            $this->error('Especifique um document_id ou use --all para diagnosticar todos os KMLs');
            return 1;
        }

        return 0;
    }

    /**
     * Diagnostica um único documento
     */
    private function diagnoseSingleDocument($documentId)
    {
        $document = PropertyDocument::find($documentId);

        if (!$document) {
            $this->error("Documento ID {$documentId} não encontrado.");
            return;
        }

        $this->info("=== Diagnóstico do Documento ID: {$documentId} ===");
        $this->diagnoseDoczument($document);
    }

    /**
     * Diagnostica todos os documentos KML
     */
    private function diagnoseAllKmlDocuments()
    {
        $kmlDocuments = PropertyDocument::where('file_name', 'like', '%.kml')
            ->orWhere('file_name', 'like', '%.kmz')
            ->get();

        if ($kmlDocuments->isEmpty()) {
            $this->info('Nenhum documento KML encontrado no banco de dados.');
            return;
        }

        $this->info("=== Diagnóstico de {$kmlDocuments->count()} documentos KML ===");

        foreach ($kmlDocuments as $document) {
            $this->info("\n--- Documento ID: {$document->id} ---");
            $this->diagnoseDoczument($document);
        }
    }

    /**
     * Diagnóstica um documento específico
     */
    private function diagnoseDoczument(PropertyDocument $document)
    {
        // Informações básicas
        $this->line("Nome: {$document->name}");
        $this->line("Arquivo: {$document->file_name}");
        $this->line("Propriedade ID: {$document->property_id}");
        $this->line("Visível: " . ($document->show ? 'Sim' : 'Não'));
        $this->line("Data: " . ($document->date ?: 'Sem data'));

        // Verificar se tem conteúdo base64
        if (!$document->file) {
            $this->error("❌ Documento não possui conteúdo (campo 'file' vazio)");
            return;
        }

        // Informações do arquivo
        $fileSize = strlen($document->file);
        $this->line("Tamanho do base64: {$fileSize} bytes");

        // Tentar decodificar base64
        $decodedData = base64_decode($document->file, true);
        
        if ($decodedData === false) {
            $this->error("❌ Erro ao decodificar base64");
            return;
        }

        $decodedSize = strlen($decodedData);
        $this->line("Tamanho decodificado: {$decodedSize} bytes");

        // Verificar se é KML válido
        if ($this->isKmlFile($document->file_name)) {
            $this->analyzeKmlContent($decodedData, $document);
        } else {
            $this->line("ℹ️ Não é um arquivo KML");
        }

        // Testar URL de acesso
        $this->testDocumentUrl($document->id);
    }

    /**
     * Analisa o conteúdo KML
     */
    private function analyzeKmlContent($content, $document)
    {
        $this->line("\n--- Análise do conteúdo KML ---");

        // Verificar se começa com XML
        if (!str_starts_with(trim($content), '<?xml')) {
            $this->warn("⚠️ Arquivo não começa com declaração XML");
        }

        // Tentar fazer parse do XML
        $previousErrorReporting = error_reporting(0);
        $xml = simplexml_load_string($content);
        error_reporting($previousErrorReporting);

        if ($xml === false) {
            $this->error("❌ XML inválido - não foi possível fazer parse");
            $this->line("Primeiros 200 caracteres:");
            $this->line(substr($content, 0, 200));
            return;
        }

        $this->info("✅ XML válido");

        // Verificar namespace KML
        $namespaces = $xml->getNamespaces(true);
        $this->line("Namespaces encontrados: " . implode(', ', array_keys($namespaces)));

        // Registrar namespace KML se presente
        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('kml', $namespaces['']);
        }

        // Contar elementos importantes
        $this->countKmlElements($xml);
    }

    /**
     * Conta elementos KML importantes
     */
    private function countKmlElements($xml)
    {
        $elements = [
            'Placemark' => 0,
            'Point' => 0,
            'LineString' => 0,
            'Polygon' => 0,
            'coordinates' => 0,
        ];

        foreach ($elements as $element => &$count) {
            $found = $xml->xpath("//{$element}");
            $count = count($found);
        }

        $this->line("\n--- Elementos KML encontrados ---");
        foreach ($elements as $element => $count) {
            $icon = $count > 0 ? '✅' : '⚠️';
            $this->line("{$icon} {$element}: {$count}");
        }

        // Verificar coordenadas
        if ($elements['coordinates'] > 0) {
            $coordsElements = $xml->xpath('//coordinates');
            if (!empty($coordsElements)) {
                $firstCoords = trim((string)$coordsElements[0]);
                $this->line("Primeira coordenada: " . substr($firstCoords, 0, 100));
                
                // Validar formato de coordenadas
                $this->validateCoordinates($firstCoords);
            }
        }
    }

    /**
     * Valida formato das coordenadas
     */
    private function validateCoordinates($coords)
    {
        $lines = explode("\n", trim($coords));
        $firstLine = trim($lines[0]);
        
        if (empty($firstLine)) {
            $this->warn("⚠️ Coordenadas estão vazias");
            return;
        }

        $parts = preg_split('/[\s,]+/', $firstLine);
        
        if (count($parts) >= 2) {
            $lon = $parts[0];
            $lat = $parts[1];
            
            if (is_numeric($lon) && is_numeric($lat)) {
                $this->info("✅ Formato de coordenadas válido (Lon: {$lon}, Lat: {$lat})");
                
                // Verificar se as coordenadas fazem sentido
                if ($lon >= -180 && $lon <= 180 && $lat >= -90 && $lat <= 90) {
                    $this->info("✅ Coordenadas dentro dos limites válidos");
                } else {
                    $this->warn("⚠️ Coordenadas fora dos limites válidos");
                }
            } else {
                $this->error("❌ Coordenadas não são numéricas");
            }
        } else {
            $this->error("❌ Formato de coordenadas inválido");
        }
    }

    /**
     * Testa a URL de acesso ao documento
     */
    private function testDocumentUrl($documentId)
    {
        $url = url("/property/document/{$documentId}");
        $this->line("\n--- Teste de URL ---");
        $this->line("URL de acesso: {$url}");
        
        // Nota: não podemos fazer requisição HTTP aqui porque precisaria de autenticação
        $this->line("💡 Teste manual: acesse a URL acima estando logado no sistema");
    }

    /**
     * Verifica se é arquivo KML
     */
    private function isKmlFile($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, ['kml', 'kmz']);
    }
}

// Para registrar o comando, adicione ao app/Console/Kernel.php:
/*
protected $commands = [
    Commands\DiagnoseKmlDocuments::class,
];

// Uso:
// php artisan kml:diagnose 3          # Diagnostica documento ID 3
// php artisan kml:diagnose --all      # Diagnostica todos os KMLs
*/