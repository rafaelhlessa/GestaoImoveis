<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_veterinary_declarations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('species');
            $table->string('age_band');
            $table->unsignedInteger('qty_males')->default(0);
            $table->unsignedInteger('qty_females')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            // batch_id FK will be added in a follow-up migration to avoid cross-file edits in existing systems
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_veterinary_declarations');
    }
};
