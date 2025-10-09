<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            $table->timestamp('owner_acknowledged_at')->nullable()->after('pdf_path');
            $table->foreignId('owner_acknowledged_by')->nullable()->after('owner_acknowledged_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            $table->dropForeign(['owner_acknowledged_by']);
            $table->dropColumn(['owner_acknowledged_at','owner_acknowledged_by']);
        });
    }
};
