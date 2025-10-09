<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_evaluation_herd_ages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('herd_id')->constrained('property_evaluation_herds')->onDelete('cascade');
            $table->string('faixa_etaria')->nullable();
            $table->unsignedInteger('quantidade_machos')->nullable();
            $table->unsignedInteger('quantidade_femeas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_evaluation_herd_ages');
    }
};
