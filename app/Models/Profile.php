<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Perfis disponíveis para seleção pública pelos usuários
     */
    public static function getPublicProfiles()
    {
        return static::whereIn('slug', ['proprietario', 'prestador'])->get();
    }

    /**
     * Perfis internos/administrativos (não aparecem para usuários)
     */
    public static function getAdminProfiles()
    {
        return static::whereIn('slug', ['administrador', 'gestor'])->get();
    }

    /**
     * Verifica se é um perfil público
     */
    public function isPublic()
    {
        return in_array($this->slug, ['proprietario', 'prestador']);
    }

    /**
     * Verifica se é um perfil administrativo
     */
    public function isAdmin()
    {
        return in_array($this->slug, ['administrador', 'gestor']);
    }
}
