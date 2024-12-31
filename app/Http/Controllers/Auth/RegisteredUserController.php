<?php

namespace App\Http\Controllers\Auth;

use App\Mail\AccountActivation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'activation_token' => Str::random(32), // Gerar o token de ativação
        ]);

        event(new Registered($user));



        Auth::login($user);

        Mail::to($user->email)->send(new AccountActivation($user));

        return redirect()->route('dashboard')->with('success', 'Usuário registrado com sucesso, Confirme seu e-mail para ativar sua conta.');
        // return response()->json(['message' => 'Confirme seu e-mail para ativar sua conta.']);
        // return redirect(route('dashboard', absolute: false));
    }
}
