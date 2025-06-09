<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'user_id',
        'appraiser',
        'valuation',
        'comments', // Adicionar este campo
        'created_at',
        'updated_at',
        // Novos campos de avaliação
        'property_type',
        'urban_subtype',
        // Residencial
        'rooms',
        'bedrooms',
        'bathrooms',
        'built_area',
        'total_area',
        'property_condition',
        'garage_spaces',
        'furniture_status',
        // Comercial
        'floors',
        'office_rooms',
        'parking_spaces',
        // Rural
        'rural_total_area',
        'has_construction',
        'construction_types',
        'has_farming',
        'farming_types',
        'water_source',
        'water_source_details',
        'observations'
    ];

    protected $casts = [
        'construction_types' => 'array',
        'farming_types' => 'array',
        'has_construction' => 'boolean',
        'has_farming' => 'boolean',
        'built_area' => 'decimal:2',
        'total_area' => 'decimal:2',
        'rural_total_area' => 'decimal:2',
        'valuation' => 'decimal:2'
    ];

    // Adicionar appends para incluir automaticamente os accessors
    protected $appends = [
        'construction_types_text',
        'farming_types_text',
        'property_condition_label',
        'furniture_status_label',
        'water_source_label'
    ];

    // Relacionamentos existentes
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Accessor para tipos de construção como string
    public function getConstructionTypesTextAttribute()
    {
        if (!$this->construction_types) return '';
        
        $types = [
            'casa_sede' => 'Casa Sede',
            'galpon' => 'Galpão',
            'estabulo' => 'Estábulo',
            'celeiro' => 'Celeiro',
            'pocilga' => 'Pocilga',
            'galinheiro' => 'Galinheiro',
            'outros' => 'Outros'
        ];

        return collect($this->construction_types)
            ->map(fn($type) => $types[$type] ?? $type)
            ->join(', ');
    }

    // Accessor para tipos de lavoura como string
    public function getFarmingTypesTextAttribute()
    {
        if (!$this->farming_types) return '';
        
        $types = [
            'soja' => 'Soja',
            'milho' => 'Milho',
            'feijao' => 'Feijão',
            'arroz' => 'Arroz',
            'trigo' => 'Trigo',
            'cana_acucar' => 'Cana de Açúcar',
            'cafe' => 'Café',
            'pastagem' => 'Pastagem',
            'fruticultura' => 'Fruticultura',
            'outros' => 'Outros'
        ];

        return collect($this->farming_types)
            ->map(fn($type) => $types[$type] ?? $type)
            ->join(', ');
    }

    // Accessor para condição da propriedade
    public function getPropertyConditionLabelAttribute()
    {
        if (!$this->property_condition) return '';
        
        $conditions = [
            'excelente' => 'Excelente',
            'bom' => 'Bom',
            'regular' => 'Regular',
            'ruim' => 'Ruim',
            'pessimo' => 'Péssimo'
        ];

        return $conditions[$this->property_condition] ?? '';
    }

    // Accessor para status da mobília
    public function getFurnitureStatusLabelAttribute()
    {
        if (!$this->furniture_status) return '';
        
        $statuses = [
            'mobiliado' => 'Mobiliado',
            'semi_mobiliado' => 'Semi-mobiliado',
            'nao_mobiliado' => 'Não mobiliado'
        ];

        return $statuses[$this->furniture_status] ?? '';
    }

    // Accessor para fonte de água
    public function getWaterSourceLabelAttribute()
    {
        if (!$this->water_source) return '';
        
        $sources = [
            'poco_artesiano' => 'Poço Artesiano',
            'rio' => 'Rio',
            'nascente' => 'Nascente',
            'rede_publica' => 'Rede Pública',
            'cisterna' => 'Cisterna',
            'outros' => 'Outros'
        ];

        return $sources[$this->water_source] ?? '';
    }

    // Scopes úteis
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('property_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Métodos auxiliares
    public function getFormattedValuationAttribute()
    {
        return 'R$ ' . number_format($this->valuation, 2, ',', '.');
    }

    public function getTypeDisplayAttribute()
    {
        if ($this->property_type === 'rural') {
            return 'Rural';
        }
        
        if ($this->property_type === 'urbana') {
            return $this->urban_subtype === 'residencial' ? 'Residencial' : 'Comercial';
        }
        
        return 'N/A';
    }
}