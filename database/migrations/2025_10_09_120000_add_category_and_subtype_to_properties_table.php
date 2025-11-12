<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Categoria principal do imóvel: urbana, rural, industrial
            $table->enum('property_category', ['urban', 'rural', 'industrial'])
                  ->nullable()
                  ->after('unit');

            // Subtipo por categoria (aplica para urbano e rural): residencial, comercial, misto
            $table->enum('property_subtype', ['residencial', 'comercial', 'misto'])
                  ->nullable()
                  ->after('property_category');
        });

        // Backfill básico a partir do campo existente type_property (1 = urbana, 2 = rural)
        DB::table('properties')
            ->where('type_property', 1)
            ->update(['property_category' => 'urban']);

        DB::table('properties')
            ->where('type_property', 2)
            ->update(['property_category' => 'rural']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['property_subtype', 'property_category']);
        });
    }
};
