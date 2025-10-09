<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_evaluation_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->default(0); // bytes
            $table->timestamps();

            $table->foreign('evaluation_id')
                ->references('id')->on('property_evaluations')
                ->onDelete('cascade');
        });

        Schema::table('property_evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('property_evaluations', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('property_evaluations', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
        Schema::dropIfExists('property_evaluation_media');
    }
};
