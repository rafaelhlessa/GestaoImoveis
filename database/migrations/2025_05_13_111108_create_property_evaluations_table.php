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
        Schema::create('property_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('avaliador');
            $table->string('appraiser')
                  ->nullable()
                  ->comment('avaliador');      
            $table->decimal('valuation', 8, 2)
                  ->nullable()
                  ->comment('valor da propriedade avaliada');
             $table->text('comments')
                  ->nullable();      
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_evaluations');
    }
};
