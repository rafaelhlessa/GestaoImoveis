<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authorization extends Model
{
    use HasFactory;

    protected $table = 'authorizations';

    protected $fillable = [
        'owner_id',
        'service_provider_id',
        'can_view_documents',
        'can_create_properties',
        'evaluation_permission', // ✅ Novo campo
    ];

    protected $casts = [
        'can_view_documents' => 'boolean',
        'can_create_properties' => 'boolean',
        'evaluation_permission' => 'boolean', // ✅ Novo cast
    ];

    /**
     * Relacionamento com o proprietário (owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relacionamento com o prestador de serviço
     */
    public function serviceProvider()
    {
        return $this->belongsTo(User::class, 'service_provider_id');
    }

    /**
     * Relacionamento com a atividade através do service provider
     */
    public function activity()
    {
        return $this->hasOneThrough(
            Activity::class,
            User::class,
            'id', // Foreign key on users table
            'id', // Foreign key on activities table
            'service_provider_id', // Local key on authorizations table
            'activity_id' // Local key on users table
        );
    }

    /**
     * Scope para buscar autorizações ativas de um prestador
     */
    public function scopeForServiceProvider($query, $serviceProviderId)
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    /**
     * Scope para autorizações que permitem criar propriedades
     */
    public function scopeCanCreateProperties($query)
    {
        return $query->where('can_create_properties', true);
    }

    /**
     * Scope para autorizações que permitem visualizar documentos
     */
    public function scopeCanViewDocuments($query)
    {
        return $query->where('can_view_documents', true);
    }

    /**
     * ✅ Novo scope para autorizações que permitem avaliar propriedades
     */
    public function scopeCanEvaluateProperties($query)
    {
        return $query->where('evaluation_permission', true);
    }
}