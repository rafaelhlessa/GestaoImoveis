<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Authorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'service_provider_id',
        'can_view_documents',
        'can_create_properties',
    ];

    public function properties()
{
    return $this->hasManyThrough(
        Property::class,      // Model final (Propriedade)
        PropertyUser::class,  // Model pivot intermediária
        'user_id',            // FK em PropertyUser que referencia o owner_id
        'id',                 // PK da Property
        'owner_id',           // FK na tabela Authorization que referencia o User
        'property_id'         // FK na PropertyUser que referencia a Property
    );
}

    // Relacionamento: Dono da propriedade (proprietário)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relacionamento: Prestador de serviço
    public function serviceProvider()
    {
        return $this->belongsTo(User::class, 'service_provider_id');
    }

    // Relacionamento: Atividade
    public function activity()
    {
        return $this->hasOneThrough(Activity::class, User::class, 'id', 'id', 'service_provider_id', 'activity_id');
    }

}
