<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PropertyDocument;
use App\Models\Authorization;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyDocumentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->profile_id === User::PROFILE_ADMIN) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
            User::PROFILE_VIEWER,
        ]);
    }

    public function view(User $user, PropertyDocument $propertyDocument)
    {
        if ($user->id === $propertyDocument->owner_id) {
            return true;
        }

        return Authorization::where('user_id', $propertyDocument->owner_id)
            ->where('authorized_user_id', $user->id)
            ->where('ativo', true)
            ->exists();
    }

    public function create(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
        ]);
    }

    public function update(User $user, PropertyDocument $propertyDocument)
    {
        return $user->id === $propertyDocument->owner_id;
    }

    public function delete(User $user, PropertyDocument $propertyDocument)
    {
        return $user->id === $propertyDocument->owner_id;
    }
}
