<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            // Adicionar colunas à tabela existente property_evaluations
            
            
            $table->enum('property_type', ['urbana', 'rural']);
            $table->enum('urban_subtype', ['residencial', 'comercial'])->nullable();
            
            // Dados Residenciais
            $table->integer('rooms')->nullable()->comment('Número de cômodos');
            $table->integer('bedrooms')->nullable()->comment('Número de dormitórios');
            $table->integer('bathrooms')->nullable()->comment('Número de banheiros');
            $table->decimal('built_area', 10, 2)->nullable()->comment('Área construída (m²)');
            $table->decimal('total_area', 10, 2)->nullable()->comment('Área total (m²)');
            $table->enum('property_condition', ['excelente', 'bom', 'regular', 'ruim', 'pessimo'])->nullable();
            $table->integer('garage_spaces')->nullable()->comment('Vagas de garagem');
            $table->enum('furniture_status', ['mobiliado', 'semi_mobiliado', 'nao_mobiliado'])->nullable();
            
            // Dados Comerciais
            $table->integer('floors')->nullable()->comment('Número de pavimentos');
            $table->integer('office_rooms')->nullable()->comment('Número de salas');
            $table->integer('parking_spaces')->nullable()->comment('Vagas de estacionamento');
            
            // Dados Rurais
            $table->decimal('rural_total_area', 12, 2)->nullable()->comment('Área total rural (hectares)');
            $table->boolean('has_construction')->nullable()->comment('Possui construção');
            $table->json('construction_types')->nullable()->comment('Tipos de construções');
            $table->boolean('has_farming')->nullable()->comment('Possui lavoura');
            $table->json('farming_types')->nullable()->comment('Tipos de lavoura');
            $table->enum('water_source', ['poco_artesiano', 'rio', 'nascente', 'rede_publica', 'cisterna', 'outros'])->nullable();
            $table->text('water_source_details')->nullable()->comment('Detalhes da fonte de água');
            
            // Observações gerais
            $table->text('observations')->nullable();
            
            
            
            // $table->foreign('property_evaluation_id')->references('id')->on('property_evaluations')->onDelete('cascade');
            // $table->index(['property_evaluation_id']);
        });
    }

    public function down()
    {
        Schema::table('property_evaluations', function (Blueprint $table) {
            // Remover as colunas adicionadas
            $table->dropColumn([
                'property_type',
                'urban_subtype', 
                'rooms',
                'bedrooms',
                'bathrooms',
                'built_area',
                'total_area',
                'property_condition',
                'garage_spaces',
                'furniture_status',
                'floors',
                'office_rooms', 
                'parking_spaces',
                'rural_total_area',
                'has_construction',
                'construction_types',
                'has_farming',
                'farming_types',
                'water_source',
                'water_source_details',
                'observations'
            ]);
        });
    }
};