<?php

namespace App\Listeners;

use App\Events\UserActivated;
use Illuminate\Support\Facades\Log;

class SetEmailVerifiedAt
{
    /**
     * Handle the event.
     */
    public function handle(UserActivated $event): void
    {
        $user = $event->user;

        // Se o email ainda não foi verificado, marca como verificado
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            Log::info("Email verificado automaticamente para o usuário: {$user->email}");
        }
    }
}
