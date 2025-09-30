<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // Perfis antigos (depreciados)
    public const PROFILE_ADMIN   = 3;
    public const PROFILE_MANAGER = 2;
    public const PROFILE_VIEWER  = 1;

    // Perfis novos (acumuláveis)
    public const PROFILE_PROPRIETARIO = 'proprietario';
    public const PROFILE_PRESTADOR    = 'prestador';
    /**
     * Relação N:N com perfis
     */
    public function profiles()
    {
        return $this->belongsToMany(Profile::class);
    }

    /**
     * Verifica se o usuário possui um perfil pelo slug
     */
    public function hasProfile($slug)
    {
        return $this->profiles->contains('slug', $slug);
    }

    /**
     * Adiciona um perfil ao usuário
     */
    public function addProfile($slug)
    {
        $profile = Profile::where('slug', $slug)->first();
        if ($profile && !$this->hasProfile($slug)) {
            $this->profiles()->attach($profile->id);
        }
    }

    /**
     * Remove um perfil do usuário
     */
    public function removeProfile($slug)
    {
        $profile = Profile::where('slug', $slug)->first();
        if ($profile && $this->hasProfile($slug)) {
            $this->profiles()->detach($profile->id);
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cpf_cnpj',
        'email',
        'address',
        'phone',
        'city',
        'city_id',
        'type',
        'password',
        'is_active',
        'activity_id',
        'activation_token',
        'login_token'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Autorizações concedidas (Proprietário concede para prestadores)
    public function givenAuthorizations(): HasMany
    {
        return $this->hasMany(Authorization::class, 'owner_id');
    }

    // Autorizações recebidas (Prestador recebe dos proprietários)
    public function receivedAuthorizations(): HasMany
    {
        return $this->hasMany(Authorization::class, 'service_provider_id');
    }

    // Verificar se um prestador tem permissão para ver documentos de um proprietário
    public function canViewDocumentsFrom(User $owner): bool
    {
        return $this->receivedAuthorizations()
            ->where('owner_id', $owner->id)
            ->where('can_view_documents', true)
            ->exists();
    }

    // Verificar se um prestador pode criar propriedades para um proprietário
    public function canCreatePropertiesFor(User $owner): bool
    {
        return $this->receivedAuthorizations()
            ->where('owner_id', $owner->id)
            ->where('can_create_properties', true)
            ->exists();
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_user', 'user_id', 'property_id');
    }

    public function typeOwnership(): HasOneThrough
    {
        return $this->hasOneThrough(
            TypeOwnership::class,
            PropertyUser::class,
            'user_id', // Chave estrangeira em property_user referenciando User
            'id', // Chave primária em type_ownership
            'id', // Chave primária em User
            'type_ownership_id' // Chave estrangeira em property_user referenciando type_ownership
        );
    }

    /**
     * Relacionamento com Activity (via activity_id)
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    // Helper method to get authenticated user's activity
    public static function getAuthenticatedUserActivity()
    {
        if (Auth::check()) {
            return Auth::user()->activity;
        }
        return null;
    }

    // Removido método canEvaluateProperties baseado em profile_id (usar lógica contextual nos controllers/policies)

    /**
     * Accessor para verificar se tem atividade de avaliação
     */
    public function getHasEvaluationPermissionAttribute()
    {
        return $this->activity && $this->activity->evaluation_permission;
    }

    /**
     * Verifica se é proprietário de uma propriedade específica
     */
    public function isOwnerOfProperty($propertyId)
    {
        return $this->properties()->where('property_id', $propertyId)->exists();
    }
}
