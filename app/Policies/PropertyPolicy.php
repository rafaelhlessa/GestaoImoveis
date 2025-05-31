<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Models\Authorization;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\UserAccessHelper;

class PropertyPolicy
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

    public function view(User $user, Property $property)
    {
        return $user->id === $property->owner_id ||
            Authorization::where('user_id', $property->owner_id)
            ->where('authorized_user_id', $user->id)
            ->where('ativo', true)
            ->exists();
    }

    public function create(User $user, ?User $owner = null): bool
    {
        return true;
    }

    public function update(User $user, Property $property)
    {
        return UserAccessHelper::canAccessOwner($user, $property->owner);
    }

    public function delete(User $user, Property $property)
    {
        return $user->id === $property->owner_id;
    }

    public function downloadDocument(User $user, Property $property)
    {
        if ($user->id === $property->owner_id) {
            return true;
        }

        return Authorization::where('user_id', $property->owner_id)
            ->where('authorized_user_id', $user->id)
            ->where('ativo', true)
            ->exists();
    }
}
