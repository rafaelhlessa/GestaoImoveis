<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PropertyDocument;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar arquivos do storage de volta para BLOB antes de remover as colunas
        $this->migrateFilesToBlob();

        Schema::table('property_documents', function (Blueprint $table) {
            // Tornar a coluna 'file' obrigatória novamente
            $table->longText('file')->nullable(false)->change();
            
            // Remover colunas do sistema de arquivos
            $table->dropColumn(['file_path', 'mime_type', 'file_size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            // Re-adicionar colunas para armazenamento de arquivos
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('mime_type')->nullable()->after('file_path');
            $table->bigInteger('file_size')->nullable()->after('mime_type');

            // Tornar a coluna 'file' nullable novamente
            $table->longText('file')->nullable()->change();
        });
    }

    private function migrateFilesToBlob(): void
    {
        $documents = PropertyDocument::whereNotNull('file_path')
            ->orWhereNotNull('mime_type')
            ->get();

        foreach ($documents as $document) {
            if ($document->file_path && Storage::exists($document->file_path)) {
                try {
                    $fileContent = Storage::get($document->file_path);
                    $base64Content = base64_encode($fileContent);
                    
                    $document->file = $base64Content;
                    $document->save();
                    
                    // Remover arquivo do storage
                    Storage::delete($document->file_path);
                    
                    echo "Migrado documento ID {$document->id} de volta para BLOB\n";
                } catch (\Exception $e) {
                    echo "Erro ao migrar documento ID {$document->id}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
};
