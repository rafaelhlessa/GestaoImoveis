<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationAnimal extends Model
{
    use HasFactory;

    protected $table = 'property_evaluation_animals';

    protected $fillable = [
        'property_evaluation_id',
        'animal_type',
        'animal_category',
        'quantity',
        'unit_price',
        'total_price',
        'observations',
    ];

    protected $casts = [
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

        static::saving(function ($animal) {
            $animal->total_price = $animal->quantity * $animal->unit_price;
        });
    }

    /**
     * Opções de tipos de animais
     */
    public static function getAnimalTypes()
    {
        return [
            'bovino' => 'Bovino',
            'suino' => 'Suíno',
            'ovino' => 'Ovino',
            'caprino' => 'Caprino',
            'equino' => 'Equino',
            'muares' => 'Muares',
            'aves' => 'Aves',
            'outros' => 'Outros',
        ];
    }

    /**
     * Opções de categorias por tipo de animal
     */
    public static function getAnimalCategories()
    {
        return [
            'bovino' => ['bezerro', 'bezerha', 'novilho', 'novilha', 'vaca', 'touro', 'boi'],
            'suino' => ['leitão', 'leitoa', 'porco', 'porca', 'cachaço'],
            'ovino' => ['cordeiro', 'cordeira', 'carneiro', 'ovelha'],
            'caprino' => ['cabrito', 'cabra', 'bode'],
            'equino' => ['potro', 'potra', 'égua', 'garanhão'],
            'muares' => ['burro', 'mula'],
            'aves' => ['frango', 'galinha', 'galo', 'pato', 'ganso'],
            'outros' => ['outros'],
        ];
    }
}
