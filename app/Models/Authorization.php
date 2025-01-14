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
}
