<?php

namespace App\Http\Controllers;

use App\Models\User;

class ActivationController extends Controller
{
    public function activate($token)
    {

        $user = User::where('activation_token', $token)->first();


        if (!$user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 404);
        }

        $user->update([
            'activation_token' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Conta ativada com sucesso!']);
    }
}
