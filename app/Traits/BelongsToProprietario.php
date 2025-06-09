<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Authorization;

trait BelongsToProprietario
{
    /**
     * Scope para filtrar registros do proprietário
     * 
     * @param Builder $query
     * @param int|null $userId ID do usuário (opcional)
     * @return Builder
     */
    public function scopeOfProprietario(Builder $query, ?int $userId = null): Builder
    {
        // Se não há usuário autenticado, retorna query vazia
        if (!Auth::check() && !$userId) {
            return $query->whereRaw('1 = 0');
        }

        // Define qual usuário usar
        $targetUserId = $userId ?? Auth::id();

        // Retorna registros onde o usuário é proprietário direto
        // OU onde tem autorização de outros proprietários
        return $query->where(function ($q) use ($targetUserId) {
            $q->where('user_id', $targetUserId)
              ->orWhereHas('authorizedUsers', function ($subQuery) use ($targetUserId) {
                  $subQuery->where('user_id', $targetUserId);
              });
        });
    }

    /**
     * Scope para filtrar apenas por um usuário específico (sem autorizações)
     * 
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeOfUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar propriedades com autorizações de prestadores
     * 
     * @param Builder $query
     * @param int|null $serviceProviderId
     * @return Builder
     */
    public function scopeWithAuthorizations(Builder $query, ?int $serviceProviderId = null): Builder
    {
        $providerId = $serviceProviderId ?? Auth::id();

        return $query->whereHas('owners', function ($ownerQuery) use ($providerId) {
            $ownerQuery->whereHas('authorizations', function ($authQuery) use ($providerId) {
                $authQuery->where('service_provider_id', $providerId)
                         ->where(function ($permissions) {
                             $permissions->where('can_view_documents', true)
                                        ->orWhere('can_create_properties', true);
                         });
            });
        });
    }

    /**
     * Scope para propriedades que o usuário pode visualizar
     * (próprias + autorizadas)
     * 
     * @param Builder $query
     * @param int|null $userId
     * @return Builder
     */
    public function scopeViewableBy(Builder $query, ?int $userId = null): Builder
    {
        if (!Auth::check() && !$userId) {
            return $query->whereRaw('1 = 0');
        }

        $targetUserId = $userId ?? Auth::id();
        $user = \App\Models\User::find($targetUserId);

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Se é proprietário (perfil 1 ou 3)
        if (in_array($user->profile_id, [1, 3])) {
            return $query->whereHas('owners', function ($q) use ($targetUserId) {
                $q->where('user_id', $targetUserId);
            });
        }

        // Se é prestador de serviço (perfil 2)
        if ($user->profile_id === 2) {
            return $query->whereHas('owners.authorizations', function ($q) use ($targetUserId) {
                $q->where('service_provider_id', $targetUserId)
                  ->where('can_view_documents', true);
            });
        }

        // Outros perfis não veem nada por padrão
        return $query->whereRaw('1 = 0');
    }
}