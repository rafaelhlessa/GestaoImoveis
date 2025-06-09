<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToProprietario;

class Activity extends Model
{
    protected $table = 'activity';

    protected $fillable = [
        'name', 
        'evaluation_permission'
    ];

    protected $casts = [
        'evaluation_permission' => 'boolean',
    ];

    use BelongsToProprietario;

    protected static function booted()
    {
        // static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'activity_id', 'id');
    }

    // Método para verificar se tem permissão de avaliação
    public function hasEvaluationPermission()
    {
        return $this->evaluation_permission;
    }

    // Scopes úteis
    public function scopeWithEvaluationPermission($query)
    {
        return $query->where('evaluation_permission', true);
    }
}