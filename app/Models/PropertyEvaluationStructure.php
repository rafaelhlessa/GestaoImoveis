<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationStructure extends Model
{
    use HasFactory;

    protected $table = 'property_evaluation_structures';

    protected $fillable = [
        'property_evaluation_id',
        'structure_type',
        'quantity',
        'area',
        'material',
        'condition',
        'construction_year',
        'description',
        'specifications',
        'estimated_value',
        'observations',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'area' => 'decimal:2',
        'construction_year' => 'integer',
        'estimated_value' => 'decimal:2',
    ];

    /**
     * Relacionamento com PropertyEvaluation
     */
    public function propertyEvaluation()
    {
        return $this->belongsTo(PropertyEvaluation::class);
    }

    /**
     * Opções de tipos de estruturas
     */
    public static function getStructureTypes()
    {
        return [
            'residencia' => 'Residência',
            'galpao' => 'Galpão',
            'estabulo' => 'Estábulo',
            'curral' => 'Curral',
            'silo' => 'Silo',
            'armazem' => 'Armazém',
            'cocheira' => 'Cocheira',
            'aviario' => 'Aviário',
            'pocilga' => 'Pocilga',
            'sede_administrativa' => 'Sede Administrativa',
            'oficina' => 'Oficina',
            'deposito' => 'Depósito',
            'casa_funcionarios' => 'Casa de Funcionários',
            'cerca' => 'Cerca',
            'porteira' => 'Porteira',
            'bebedouro' => 'Bebedouro',
            'tanque' => 'Tanque/Açude',
            'poco' => 'Poço',
            'outros' => 'Outros',
        ];
    }

    /**
     * Opções de materiais de construção
     */
    public static function getMaterials()
    {
        return [
            'alvenaria' => 'Alvenaria',
            'madeira' => 'Madeira',
            'metalica' => 'Estrutura Metálica',
            'pre_moldado' => 'Pré-moldado',
            'mista' => 'Mista',
            'adobe' => 'Adobe/Taipa',
            'outros' => 'Outros',
        ];
    }

    /**
     * Opções de condições da estrutura
     */
    public static function getConditions()
    {
        return [
            'otimo' => 'Ótimo',
            'bom' => 'Bom',
            'regular' => 'Regular',
            'ruim' => 'Ruim',
            'pessimo' => 'Péssimo',
            'em_construcao' => 'Em Construção',
            'abandonado' => 'Abandonado',
        ];
    }
}
