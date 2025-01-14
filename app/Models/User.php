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

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cpf_cnpj',
        'email',
        'phone',
        'profile_id',
        'city',
        'city_id',
        'type',
        'password',
        'is_active',
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

    // public function typeOwnerships()
    // {
    //     return $this->belongsToMany(TypeOwnership::class, 'property_user', 'type_ownership_id', 'user_id');
    // }

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

}
