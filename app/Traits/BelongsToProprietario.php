<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Authorization;
use Illuminate\Support\Facades\Auth;

trait BelongsToProprietario
{
    protected static function bootBelongsToProprietario()
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            if (!Auth::check()) return;

            $user = Auth::user();
            $ownerIds = [$user->id];

            // Inclui IDs de usuários que autorizaram o atual
            $autorizacoes = Authorization::where('authorized_user_id', $user->id)
                ->where('ativo', true)
                ->pluck('user_id')
                ->toArray();

            $ownerIds = array_unique(array_merge($ownerIds, $autorizacoes));

            $builder->whereIn('owner_id', $ownerIds);
        });
    }
}
