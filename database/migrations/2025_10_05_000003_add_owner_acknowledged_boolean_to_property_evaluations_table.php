<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            $table->boolean('owner_acknowledged')->default(false)->after('pdf_path');
        });

        // Backfill from existing timestamp if present
        DB::table('property_evaluations')
            ->whereNotNull('owner_acknowledged_at')
            ->update(['owner_acknowledged' => true]);
    }

    public function down(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            $table->dropColumn('owner_acknowledged');
        });
    }
};
