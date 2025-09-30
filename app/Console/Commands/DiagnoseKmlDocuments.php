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
            $this->diagnoseDoczument($document);
        }
    }

    /**
     * Diagnostica o documento
     */
    private function diagnoseDoczument($document)
    {
        $this->line("Arquivo: {$document->file_name}");
        $this->line("Tamanho: {$document->size} bytes");
        $this->line("MIME: {$document->mime_type}");
        $this->testDocumentUrl($document->id);

        $decodedData = base64_decode($document->data);
        if ($decodedData === false) {
            $this->error('Erro ao decodificar o conteúdo do arquivo.');
            return;
        }

        // Verificar se é KML válido
        if ($this->isKmlFile($document->file_name)) {
            $this->analyzeKmlContent($decodedData, $document);
        } else {
            $this->line("ℹ️ Não é um arquivo KML");
        }
    }

    /**
     * Analisa o conteúdo KML
     */
    private function analyzeKmlContent($content, $document)
    {
        $this->line("\n--- Análise do conteúdo KML ---");
        // Aqui você pode adicionar validações específicas do KML
        if (strpos($content, '<kml') !== false) {
            $this->info('Arquivo contém tag <kml>');
        } else {
            $this->error('Arquivo não contém tag <kml>');
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
