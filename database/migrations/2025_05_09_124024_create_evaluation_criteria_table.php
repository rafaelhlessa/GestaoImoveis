<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationCriteriaTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('weight', 5, 2)
                  ->default(1)
                  ->comment('peso relativo para cálculo do score');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_criteria');
    }
}
