<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Authorization;

trait BelongsToProprietario
{
    public function scopeOfProprietario(Builder $query): Builder
    {
        // if (!Auth::check()) return $query;

        // $user = Auth::user();
        // $ownerIds = [$user->id];

        // // IDs de usuários que autorizaram o atual
        // $autorizados = Authorization::where('authorized_user_id', $user->id)
        //     ->where('ativo', true)
        //     ->pluck('user_id')
        //     ->toArray();

        // $ownerIds = array_merge($ownerIds, $autorizados);

        // return $query->whereIn('owner_id', $ownerIds);
        $userId = $userId ?: auth()->id();
        return $query->where('user_id', $userId)
                    ->orWhereHas('authorizedUsers', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
    }
}
