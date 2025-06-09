<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Authorization;

class UserAccessHelper
{
    public static function canCreateForOwner(User $authUser, ?User $owner): bool
    {
        if (!$owner) {
            return false; // ❌ Bloqueia tentativa sem dono válido
        }

        // Proprietário criando seu próprio imóvel
        if ($authUser->id === $owner->id) {
            return true;
        }

        // Prestador com autorização
        return Authorization::where('user_id', $owner->id)
            ->where('authorized_user_id', $authUser->id)
            ->where('ativo', true)
            ->exists();
    }
}
