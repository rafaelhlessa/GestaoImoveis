<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TokenLoginController extends Controller
{
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

        // Redireciona para o dashboard ou outra página protegida
        return redirect()->route('dashboard')->with('status', 'Login realizado com sucesso!');
    }
}

