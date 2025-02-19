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
            $table->boolean('is_active')->default(true);
            $table->integer('type_property');
            $table->integer('title_deed');
            $table->string('title_deed_number')->nullable();
            $table->string('other')->nullable();
            $table->string('area')->nullable();
            $table->string('unit')->nullable();
            $table->string('address');
            $table->string('city');
            $table->integer('city_id');
            $table->string('district')->nullable();
            $table->string('locality')->nullable();
            $table->string('nickname')->nullable();
            $table->text('about')->nullable();
            $table->longText('file_photo')->nullable();
            $table->integer('purchase_value')->nullable();
            $table->date('purchase_date')->nullable();
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
