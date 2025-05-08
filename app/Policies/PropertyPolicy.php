<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyPolicy
{
    use HandlesAuthorization;

    /**
     * Se você quiser que o “super admin” salte todas as checagens:
     */
    public function before(User $user, $ability)
    {
        if ($user->profile_id === User::PROFILE_ADMIN) {
            return true;
        }
    }

    /**
     * View any properties (listagem)
     */
    public function viewAny(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
            User::PROFILE_VIEWER,
        ]);
    }

    /**
     * View one property
     */
    public function view(User $user, Property $property)
    {
        // Exemplo: só vê se for dono ou admin
        return $user->id === $property->owner_id
            || $user->profile_id === User::PROFILE_ADMIN;
    }

    /**
     * Criar property
     */
    public function create(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
        ]);
    }

    /**
     * Atualizar uma property
     */
    public function update(User $user, Property $property)
    {
        return $user->id === $property->owner_id
            || $user->profile_id === User::PROFILE_ADMIN;
    }

    /**
     * Deletar uma property
     */
    public function delete(User $user, Property $property)
    {
        return $user->id === $property->owner_id
            || $user->profile_id === User::PROFILE_ADMIN;
    }

    /**
     * Download de documento
     */
    public function downloadDocument(User $user, Property $property)
    {
        // só permite se for dono ou admin
        return $user->id === $property->owner_id
            || $user->profile_id === User::PROFILE_ADMIN;
    }
}
