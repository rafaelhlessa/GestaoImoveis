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
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // Proprietário
            $table->foreignId('service_provider_id')->constrained('users')->onDelete('cascade'); // Prestador de Serviço
            $table->boolean('can_view_documents')->default(false); // Pode visualizar documentos?
            $table->boolean('can_create_properties')->default(false); // Pode cadastrar propriedades?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorizations');
    }
};
