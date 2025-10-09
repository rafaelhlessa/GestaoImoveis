<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_co_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('name');
            $table->string('cpf_cnpj', 18);
            $table->decimal('percentage', 5, 2);
            $table->foreignId('type_ownership_id')->constrained('type_ownership');
            $table->text('observations')->nullable();
            $table->timestamps();
            
            // Índices para performance
            $table->index('property_id');
            $table->index('cpf_cnpj');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_co_owners');
    }
};