<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_evaluation_herds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('property_evaluations')->onDelete('cascade');
            $table->string('especie')->nullable();
            $table->string('raca')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_evaluation_herds');
    }
};
