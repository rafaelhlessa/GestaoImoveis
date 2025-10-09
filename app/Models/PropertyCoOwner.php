<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyCoOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'cpf_cnpj',
        'percentage',
        'type_ownership_id',
        'observations'
    ];

    protected $casts = [
        'percentage' => 'decimal:2'
    ];

    /**
     * Relacionamento com Property
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relacionamento com TypeOwnership
     */
    public function typeOwnership(): BelongsTo
    {
        return $this->belongsTo(TypeOwnership::class, 'type_ownership_id');
    }

    /**
     * Accessor para formatar o CPF/CNPJ
     */
    public function getFormattedCpfCnpjAttribute()
    {
        $cpfCnpj = preg_replace('/\D/', '', $this->cpf_cnpj);
        
        if (strlen($cpfCnpj) === 11) {
            // CPF: 000.000.000-00
            return substr($cpfCnpj, 0, 3) . '.' . 
                   substr($cpfCnpj, 3, 3) . '.' . 
                   substr($cpfCnpj, 6, 3) . '-' . 
                   substr($cpfCnpj, 9, 2);
        } elseif (strlen($cpfCnpj) === 14) {
            // CNPJ: 00.000.000/0000-00
            return substr($cpfCnpj, 0, 2) . '.' . 
                   substr($cpfCnpj, 2, 3) . '.' . 
                   substr($cpfCnpj, 5, 3) . '/' . 
                   substr($cpfCnpj, 8, 4) . '-' . 
                   substr($cpfCnpj, 12, 2);
        }
        
        return $cpfCnpj;
    }

    /**
     * Scope para buscar por propriedade
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
