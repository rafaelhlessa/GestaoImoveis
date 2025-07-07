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
        Schema::create('property_evaluation_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_evaluation_id')->constrained('property_evaluations')->onDelete('cascade');
            $table->string('structure_type'); // Tipo de estrutura (residência, galpão, estábulo, etc.)
            $table->integer('quantity'); // Quantidade
            $table->decimal('area', 8, 2)->nullable(); // Área em m²
            $table->string('material')->nullable(); // Material de construção
            $table->string('condition')->nullable(); // Estado da estrutura (ótimo, bom, regular, ruim)
            $table->year('construction_year')->nullable(); // Ano de construção
            $table->text('description'); // Descrição detalhada
            $table->text('specifications'); // Especificidades técnicas
            $table->decimal('estimated_value', 12, 2)->nullable(); // Valor estimado
            $table->text('observations')->nullable(); // Observações adicionais
            $table->timestamps();

            $table->index(['property_evaluation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_evaluation_structures');
    }
};
