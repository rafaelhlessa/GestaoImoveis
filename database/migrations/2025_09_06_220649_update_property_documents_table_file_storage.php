<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            // Adicionar novas colunas para armazenamento de arquivos
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('mime_type')->nullable()->after('file_path');
            $table->bigInteger('file_size')->nullable()->after('mime_type');

            // Tornar a coluna 'file' nullable (Base64) - será descontinuada
            $table->longText('file')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'mime_type', 'file_size']);
            $table->longText('file')->nullable(false)->change();
        });
    }
};
