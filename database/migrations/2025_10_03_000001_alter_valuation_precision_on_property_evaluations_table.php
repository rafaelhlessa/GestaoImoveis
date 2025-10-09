<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            // Aumenta a precisão para permitir valores altos (ex.: 5.000.000,00)
            $table->decimal('valuation', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            // Reverte para a precisão original
            $table->decimal('valuation', 8, 2)->nullable()->change();
        });
    }
};
