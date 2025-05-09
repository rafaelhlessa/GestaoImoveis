<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationItemsTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')
                  ->constrained('property_evaluations')
                  ->cascadeOnDelete();
            $table->foreignId('criterion_id')
                  ->constrained('evaluation_criteria')
                  ->cascadeOnDelete();
            $table->decimal('note', 5, 2);
            $table->text('observation')
                  ->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'criterion_id'],
                           'evalitem_eval_crit_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_items');
    }
}
