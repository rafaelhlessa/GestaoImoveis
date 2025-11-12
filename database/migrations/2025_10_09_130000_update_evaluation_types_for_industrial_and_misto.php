<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Atualiza enum para incluir 'industrial' e 'misto'
        // OBS: usando SQL bruto pois enum não é alterável via Schema builder
        if (Schema::hasTable('property_evaluations')) {
            // property_type: adicionar 'industrial'
            DB::statement("ALTER TABLE `property_evaluations` MODIFY `property_type` ENUM('urbana','rural','industrial') NULL");
            // urban_subtype: adicionar 'misto'
            DB::statement("ALTER TABLE `property_evaluations` MODIFY `urban_subtype` ENUM('residencial','comercial','misto') NULL");
        }
    }

    public function down()
    {
        if (Schema::hasTable('property_evaluations')) {
            // Reverter para os valores anteriores
            DB::statement("ALTER TABLE `property_evaluations` MODIFY `property_type` ENUM('urbana','rural') NULL");
            DB::statement("ALTER TABLE `property_evaluations` MODIFY `urban_subtype` ENUM('residencial','comercial') NULL");
        }
    }
};
