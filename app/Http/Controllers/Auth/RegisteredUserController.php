<?php

namespace App\Http\Controllers\Auth;

use App\Mail\AccountActivation;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Models\Profile;
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
use App\Http\Requests\RegisterRequest;



class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $activity = Activity::all();

        // Apenas perfis que usuários podem selecionar no registro
        $profiles = Profile::getPublicProfiles();

        return Inertia::render('Auth/Register', [
            'activities' => $activity,
            'profiles' => $profiles
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'cpf_cnpj' => $data['cpf_cnpj'],
            'activity_id' => $data['activity_id'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'city_id' => $data['city_id'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'activation_token' => Str::random(32),
            'activation_token_created_at' => now(),
            'is_active' => false, // Importante: usuário inicia inativo
        ]);

        // Vincular perfis ao usuário
        if (!empty($data['profiles'])) {
            $profileIds = \App\Models\Profile::whereIn('slug', $data['profiles'])->pluck('id');
            $user->profiles()->attach($profileIds);
        }

        event(new Registered($user));

        // Enviar email de ativação
        Mail::to($user->email)->send(new AccountActivation($user));

        return redirect()->route('login')
            ->with('status', 'Enviamos um e-mail com instruções para ativar sua conta. Por favor, verifique sua caixa de entrada.');
    }
}
