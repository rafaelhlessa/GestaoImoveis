<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivationController extends Controller
{
    public function activate($token)
    {

        $user = User::where('activation_token', $token)->first();


        if (!$user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 404);
        }

        $user->update([
            'is_active' => 1,
            'activation_token' => null,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        if($user->profile_id === 2) {
            return redirect()->route('providers.index')->with('status', 'Login realizado com sucesso!');
        } else {    
            return redirect()->route('dashboard')->with('status', 'Login realizado com sucesso!');
        }

        // return redirect()->route('dashboard');
        // return response()->json(['message' => 'Conta ativada com sucesso!']);
    }

    public function loginWithToken($token)
    {
        // Localiza o usuário pelo token
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['token' => 'Token inválido ou expirado.']);
        }

        // Atualiza o campo `is_active` para 1 (ativo)
        $user->update(['is_active' => 1, 'activation_token' => null]); // Limpa o token após o uso

        // Realiza o login do usuário
        Auth::login($user);

        if($user->profile_id === 2) {
            return redirect()->route('providers.index')->with('status', 'Login realizado com sucesso!');
        } else {    
            return redirect()->route('dashboard')->with('status', 'Login realizado com sucesso!');
        }
        // Redireciona para o dashboard ou outra página protegida
        // return redirect()->route('dashboard')->with('status', 'Login realizado com sucesso!');
    }
}
