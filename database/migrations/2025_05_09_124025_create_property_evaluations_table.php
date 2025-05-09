<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyEvaluationsTable extends Migration
{
    public function up()
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
            $table->decimal('score', 8, 2)
                  ->nullable()
                  ->comment('nota ponderada total');
            $table->text('comments')
                  ->nullable();
            $table->timestamps();

            $table->index(['property_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_evaluations');
    }
}
