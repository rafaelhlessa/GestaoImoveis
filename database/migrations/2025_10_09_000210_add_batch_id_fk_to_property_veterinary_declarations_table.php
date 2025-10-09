<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_veterinary_declarations', function (Blueprint $table) {
            if (!Schema::hasColumn('property_veterinary_declarations', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('property_id');
            }
            $table->foreign('batch_id')
                  ->references('id')
                  ->on('property_veterinary_declaration_batches')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('property_veterinary_declarations', function (Blueprint $table) {
            if (Schema::hasColumn('property_veterinary_declarations', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
        });
    }
};
