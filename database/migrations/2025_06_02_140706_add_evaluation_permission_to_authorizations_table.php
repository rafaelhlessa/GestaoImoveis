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
        Schema::table('authorizations', function (Blueprint $table) {
            $table->boolean('evaluation_permission')->default(false)->after('can_create_properties');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authorizations', function (Blueprint $table) {
            $table->dropColumn('evaluation_permission');
        });
    }
};