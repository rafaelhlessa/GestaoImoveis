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

    public const PROFILE_ADMIN   = 3;
    public const PROFILE_MANAGER = 2;
    public const PROFILE_VIEWER  = 1;

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
        'profile_id',
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
        'profile_id' => 'integer',
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

    /**
     * Verifica se o usuário tem permissão para avaliar propriedades
     */
    public function canEvaluateProperties()
    {
        // Proprietários puros (profile_id 1) não podem avaliar
        if ($this->profile_id === 1) {
            return false;
        }
        
        // Prestadores de serviço (profile_id 2) dependem da tabela authorizations
        if ($this->profile_id === 2) {
            // Esta verificação deve ser feita no contexto de uma propriedade específica
            return null; // Retorna null para indicar que precisa de verificação contextual
        }
        
        // Proprietário/Prestador (profile_id 3) depende da activity
        if ($this->profile_id === 3) {
            return $this->activity && $this->activity->evaluation_permission;
        }
        
        return false;
    }

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