<?php

namespace App\Http\Controllers\Auth;

use App\Mail\AccountActivation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
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
            'cpf_cnpj' => 'required|string|min:11|max:14',
            'phone' => 'required|string|min:10|max:11',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'city_id' => 'required|integer',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'cpf_cnpj' => $request->cpf_cnpj,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'city_id' => $request->city_id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'activation_token' => Str::random(32), // Gerar o token de ativação
        ]);

        event(new Registered($user));

        // Auth::login($user);

        Mail::to($user->email)->send(new AccountActivation($user));

        // return redirect()->route('/')->with('success', 'Usuário registrado com sucesso, Confirme seu e-mail para ativar sua conta.');
        // return response()->json(['message' => 'Confirme seu e-mail para ativar sua conta.']);
        // return redirect(route('/', absolute: false));
        return Redirect::to('/');
    }
}
