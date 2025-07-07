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
        Schema::create('property_evaluation_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_evaluation_id')->constrained('property_evaluations')->onDelete('cascade');
            $table->string('animal_type'); // Tipo do animal (bovino, suíno, ovino, equino, etc.)
            $table->string('animal_category')->nullable(); // Categoria (bezerro, vaca, touro, etc.)
            $table->integer('quantity'); // Quantidade
            $table->decimal('unit_price', 10, 2); // Cotação unitária
            $table->decimal('total_price', 12, 2); // Cotação total (calculada)
            $table->text('observations')->nullable(); // Observações específicas
            $table->timestamps();

            $table->index(['property_evaluation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_evaluation_animals');
    }
};
