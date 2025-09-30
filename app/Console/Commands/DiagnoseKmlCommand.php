<?php

namespace App\Console\Commands;

use App\Models\PropertyDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DiagnoseKmlCommand extends Command
{
    protected $signature = 'kml:diagnose {document_id? : ID do documento KML específico} {--all : Diagnosticar todos os arquivos KML}';
    protected $description = 'Diagnostica problemas em arquivos KML';

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $all = $this->option('all');

        if ($documentId) {
            $document = PropertyDocument::find($documentId);
            if (!$document) {
                $this->error("Documento com ID {$documentId} não encontrado.");
                return 1;
            }
            $this->diagnoseDocument($document);
        } elseif ($all) {
            $kmlDocuments = PropertyDocument::where('mime_type', 'application/vnd.google-earth.kml+xml')
                ->orWhere('file_name', 'like', '%.kml')
                ->get();

            $this->info("Encontrados {$kmlDocuments->count()} documentos KML");

            foreach ($kmlDocuments as $document) {
                $this->line("---");
                $this->diagnoseDocument($document);
            }
        } else {
            $this->error("Especifique um document_id ou use --all para diagnosticar todos os KMLs");
            return 1;
        }

        return 0;
    }

    private function diagnoseDocument(PropertyDocument $document)
    {
        $this->info("🔍 Diagnóstico do documento ID: {$document->id}");
        $this->line("Nome: {$document->file_name}");
        $this->line("MIME Type: {$document->mime_type}");
        $this->line("Tamanho: " . number_format($document->file_size / 1024, 2) . " KB");
        
        if ($document->file_path && Storage::exists($document->file_path)) {
            $this->line("✅ Arquivo existe no storage: {$document->file_path}");
            
            try {
                $content = Storage::get($document->file_path);
                $this->analyzeKmlContent($content);
            } catch (\Exception $e) {
                $this->error("❌ Erro ao ler arquivo: " . $e->getMessage());
            }
        } elseif ($document->content) {
            $this->line("📁 Arquivo em Base64 no banco de dados");
            
            try {
                $content = base64_decode($document->content);
                $this->analyzeKmlContent($content);
            } catch (\Exception $e) {
                $this->error("❌ Erro ao decodificar Base64: " . $e->getMessage());
            }
        } else {
            $this->error("❌ Arquivo não encontrado nem no storage nem como Base64");
        }
    }

    private function analyzeKmlContent($content)
    {
        // Verificar se é XML válido
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $this->error("❌ XML inválido:");
            foreach ($errors as $error) {
                $this->error("  - Linha {$error->line}: {$error->message}");
            }
            libxml_clear_errors();
            return;
        }

        $this->info("✅ XML válido");

        // Verificar namespace KML
        $namespaces = $xml->getNamespaces(true);
        $hasKmlNamespace = false;
        foreach ($namespaces as $prefix => $namespace) {
            if (strpos($namespace, 'earth.google.com') !== false || 
                strpos($namespace, 'opengis.net/kml') !== false) {
                $hasKmlNamespace = true;
                $this->info("✅ Namespace KML encontrado: {$namespace}");
                break;
            }
        }

        if (!$hasKmlNamespace) {
            $this->warn("⚠️  Namespace KML não encontrado");
        }

        // Registrar namespace para consultas XPath
        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        
        // Buscar elementos geográficos
        $placemarks = $xml->xpath('//kml:Placemark | //Placemark');
        $points = $xml->xpath('//kml:Point | //Point');
        $polygons = $xml->xpath('//kml:Polygon | //Polygon');
        $linestrings = $xml->xpath('//kml:LineString | //LineString');
        $coordinates = $xml->xpath('//kml:coordinates | //coordinates');

        $this->info("📍 Elementos encontrados:");
        $this->line("  - Placemarks: " . count($placemarks));
        $this->line("  - Points: " . count($points));
        $this->line("  - Polygons: " . count($polygons));
        $this->line("  - LineStrings: " . count($linestrings));
        $this->line("  - Coordinates: " . count($coordinates));

        // Analisar coordenadas
        if (count($coordinates) > 0) {
            $this->info("🗺️  Análise de coordenadas:");
            foreach ($coordinates as $i => $coord) {
                $coordText = trim((string)$coord);
                if (empty($coordText)) {
                    $this->warn("  - Coordenada {$i}: vazia");
                    continue;
                }

                $coords = explode(' ', $coordText);
                $validCoords = 0;
                $latMin = $latMax = $lonMin = $lonMax = null;

                foreach ($coords as $coordPair) {
                    $coordPair = trim($coordPair);
                    if (empty($coordPair)) continue;

                    $parts = explode(',', $coordPair);
                    if (count($parts) >= 2) {
                        $lon = floatval($parts[0]);
                        $lat = floatval($parts[1]);
                        
                        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
                            $validCoords++;
                            
                            if ($latMin === null || $lat < $latMin) $latMin = $lat;
                            if ($latMax === null || $lat > $latMax) $latMax = $lat;
                            if ($lonMin === null || $lon < $lonMin) $lonMin = $lon;
                            if ($lonMax === null || $lon > $lonMax) $lonMax = $lon;
                        }
                    }
                }

                $this->line("  - Coordenada {$i}: {$validCoords} pontos válidos");
                if ($validCoords > 0) {
                    $this->line("    Bounds: Lat({$latMin}, {$latMax}), Lon({$lonMin}, {$lonMax})");
                }
            }
        } else {
            $this->warn("⚠️  Nenhuma coordenada encontrada no arquivo");
        }

        // Verificar encoding
        if (preg_match('/encoding\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $this->line("📝 Encoding: {$matches[1]}");
        }

        // Verificar tamanho do conteúdo
        $size = strlen($content);
        $this->line("📏 Tamanho do conteúdo: " . number_format($size / 1024, 2) . " KB");
        
        if ($size > 1024 * 1024) { // > 1MB
            $this->warn("⚠️  Arquivo muito grande (> 1MB), pode causar problemas de performance");
        }
    }
}
