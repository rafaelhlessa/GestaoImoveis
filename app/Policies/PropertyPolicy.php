<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Models\Authorization;
use Illuminate\Auth\Access\HandlesAuthorization;

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
        if ($owner) {
            return $user->id === $owner->id ||
                Authorization::where('user_id', $owner->id)
                ->where('authorized_user_id', $user->id)
                ->where('ativo', true)
                ->exists();
        }

        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
        ]);
    }

    public function update(User $user, Property $property)
    {
        if (!$owner) return true;

        return $user->id === $owner->id || $user->cpf === $owner->cpf;
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
