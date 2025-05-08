<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    /**
     * Mostra o formulário de solicitação de redefinição de senha
     */
    public function showLinkRequestForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Envia o email de redefinição de senha
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        
        // Gera o token
        $token = Str::random(64);
        
        // Salva os dados na tabela password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );
        
        // Envia o email
        Mail::to($request->email)->send(new ResetPassword($user, $token));
        
        return back()->with('status', 'Link de redefinição de senha enviado com sucesso!');
    }

    /**
     * Mostra o formulário de redefinição de senha
     */
    public function showResetForm(Request $request, $token)
    {
        
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $token,
        ]);
    }

    /**
     * Redefine a senha
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        
        // Verifica se o token é válido
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return back()->withErrors(['email' => 'Token inválido!']);
        }

        // Verifica se o token está expirado (60 minutos)
        $createdAt = Carbon::parse($tokenData->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            return back()->withErrors(['email' => 'Token expirado!']);
        }

        // Atualiza a senha do usuário
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        // Remove o token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Senha redefinida com sucesso!');
    }
}