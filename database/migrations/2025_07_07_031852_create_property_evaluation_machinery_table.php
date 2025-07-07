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
        Schema::create('property_evaluation_machinery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_evaluation_id')->constrained('property_evaluations')->onDelete('cascade');
            $table->string('machinery_type'); // Tipo de maquinário (trator, colheitadeira, arado, etc.)
            $table->string('brand')->nullable(); // Marca
            $table->string('model')->nullable(); // Modelo
            $table->year('year')->nullable(); // Ano
            $table->integer('quantity'); // Quantidade
            $table->decimal('unit_price', 12, 2); // Cotação unitária
            $table->decimal('total_price', 14, 2); // Cotação total (calculada)
            $table->string('condition')->nullable(); // Estado (novo, seminovo, usado)
            $table->text('specifications')->nullable(); // Especificações técnicas
            $table->text('observations')->nullable(); // Observações
            $table->timestamps();

            $table->index(['property_evaluation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_evaluation_machinery');
    }
};
