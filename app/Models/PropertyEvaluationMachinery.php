<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationMachinery extends Model
{
    use HasFactory;

    protected $table = 'property_evaluation_machinery';

    protected $fillable = [
        'property_evaluation_id',
        'machinery_type',
        'brand',
        'model',
        'year',
        'quantity',
        'unit_price',
        'total_price',
        'condition',
        'specifications',
        'observations',
    ];

    protected $casts = [
        'year' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relacionamento com PropertyEvaluation
     */
    public function propertyEvaluation()
    {
        return $this->belongsTo(PropertyEvaluation::class);
    }

    /**
     * Calcula o valor total automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($machinery) {
            $machinery->total_price = $machinery->quantity * $machinery->unit_price;
        });
    }

    /**
     * Opções de tipos de maquinários
     */
    public static function getMachineryTypes()
    {
        return [
            'trator' => 'Trator',
            'colheitadeira' => 'Colheitadeira',
            'plantadeira' => 'Plantadeira',
            'pulverizador' => 'Pulverizador',
            'arado' => 'Arado',
            'grade' => 'Grade',
            'roçadeira' => 'Roçadeira',
            'distribuidora' => 'Distribuidora de Adubo',
            'carreta' => 'Carreta',
            'caminhao' => 'Caminhão',
            'ordenhadeira' => 'Ordenhadeira',
            'resfriador' => 'Resfriador de Leite',
            'picadeira' => 'Picadeira de Forragem',
            'enfardadeira' => 'Enfardadeira',
            'outros' => 'Outros',
        ];
    }

    /**
     * Opções de condições do maquinário
     */
    public static function getConditions()
    {
        return [
            'novo' => 'Novo',
            'seminovo' => 'Seminovo',
            'usado_bom' => 'Usado - Bom Estado',
            'usado_regular' => 'Usado - Estado Regular',
            'usado_ruim' => 'Usado - Estado Ruim',
            'para_reforma' => 'Para Reforma',
        ];
    }
}
