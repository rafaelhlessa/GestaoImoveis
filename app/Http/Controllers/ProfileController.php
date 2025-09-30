<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'city_id' => $user->city_id,
                'cpf_cnpj' => $user->cpf_cnpj,
                'profiles' => $user->profiles->pluck('slug')->toArray(), // Converter para array de slugs
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Separar profiles dos outros dados
        $profiles = $validated['profiles'] ?? [];
        unset($validated['profiles']);

        // Atualizar dados básicos do usuário
        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        // Sincronizar profiles
        if (!empty($profiles)) {
            $profileIds = \App\Models\Profile::whereIn('slug', $profiles)->pluck('id');
            $user->profiles()->sync($profileIds);
        } else {
            // Se não há profiles, remover todos
            $user->profiles()->detach();
        }

        return Redirect::route('profile.edit')->with('status', 'Perfil atualizado com sucesso!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
