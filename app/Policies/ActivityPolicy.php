<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Activity;
use App\Models\Authorization;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
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

    public function view(User $user, Activity $activity)
    {
        if ($user->id === $activity->owner_id) {
            return true;
        }

        return Authorization::where('user_id', $activity->owner_id)
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

    public function update(User $user, Activity $activity)
    {
        return $user->id === $activity->owner_id;
    }

    public function delete(User $user, Activity $activity)
    {
        return $user->id === $activity->owner_id;
    }
}
