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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('property_name');
            $table->string('cpf_cnpj');
            $table->string('title_deed');
            $table->string('deed_registry')->nullable();
            $table->string('transcription')->nullable();
            $table->string('other')->nullable();
            $table->string('state');
            $table->string('city');
            $table->string('district');
            $table->string('locality');
            $table->string('nickname')->nullable();
            $table->string('area')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
