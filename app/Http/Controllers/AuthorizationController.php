<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthorizationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Proprietário pode ver autorizações concedidas
        if ($user->hasProfile('proprietario')) {
            $authorizations = Authorization::where('owner_id', $user->id)
                ->with(['serviceProvider', 'activity'])
                ->get();
            return Inertia::render('Authorizations/IndexAuthorization', [
                'authorizations' => $authorizations
            ]);
        }
        // Prestador não tem acesso
        return Inertia::render('Dashboard');
    }

    public function create()
    {
        $user = Auth::user();
        if ($user->hasProfile('proprietario')) {
            // Buscar apenas usuários que tenham perfil prestador
            $serviceProviders = User::whereHas('profiles', function($q) {
                $q->where('slug', 'prestador');
            })->get();
            return Inertia::render('Authorizations/CreateAuthorization', [
                'serviceProviders' => $serviceProviders,
                'user' => $user
            ]);
        }
        return Inertia::render('Dashboard');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $existingProvider = Authorization::where('owner_id', $user->id)
            ->where('service_provider_id', $request->service_provider_id)
            ->exists();
        $serviceProviders = User::whereHas('profiles', function($q) {
            $q->where('slug', 'prestador');
        })->get();
        if ($existingProvider) {
            return Inertia::render('Authorizations/CreateAuthorization', [
                'serviceProviders' => $serviceProviders,
                'user' => $user
            ])->with(['message' => 'Já existe uma autorização para este usuário.']);
        }
        $request->validate([
            'service_provider_id' => 'required|exists:users,id',
            'can_view_documents' => 'boolean',
            'can_create_properties' => 'boolean',
            'evaluation_permission' => 'boolean',
        ]);
        Authorization::create([
            'owner_id' => $user->id,
            'service_provider_id' => $request->service_provider_id,
            'can_view_documents' => $request->can_view_documents,
            'can_create_properties' => $request->can_create_properties,
            'evaluation_permission' => $request->evaluation_permission,
        ]);
        return redirect()->route('authorizations.index')
            ->with('message', 'Autorização criada com sucesso!');
    }

    public function edit(Authorization $authorization)
    {
        if (Gate::denies('edit-authorization', $authorization)) {
            return redirect()->back()->with('error', 'Você não tem permissão para editar esta autorização.');
        }

        return Inertia::render('Authorizations/EditAuthorization', [
            'authorization' => $authorization,
            'serviceProviders' => User::whereHas('profiles', function($q) {
                $q->where('slug', 'prestador');
            })->get(),
        ]);
    }

    public function update(Authorization $authorization, Request $request)
    {
        dd($request);
        if (Gate::denies('edit-authorization', $authorization)) {
            return redirect()->back()->with('error', 'Você não tem permissão para editar esta autorização.');
        }

        $request->validate([
            'service_provider_id' => 'required|exists:users,id',
            'can_view_documents' => 'boolean',
            'can_create_properties' => 'boolean',
            'evaluation_permission' => 'boolean',
        ]);

        $authorization->update($request->all());

        return redirect()->route('authorizations.index')->with('message', 'Autorização atualizada com sucesso!');
    }

    public function updateAuthChange(Request $request, string $authId)
    {
        // ✅ Debug - Remover em produção
        Log::info('=== UPDATE AUTH CHANGE ===');
        Log::info('Auth ID:', ['id' => $authId]);
        Log::info('Request data:', $request->all());

        $request->validate([
            'can_create_properties' => 'required|boolean',
            'can_view_documents' => 'required|boolean',
            'evaluation_permission' => 'required|boolean',
        ]);

        try {
            $authorization = Authorization::findOrFail($authId);

            // ✅ Log antes da atualização
            Log::info('Authorization antes da atualização:', [
                'can_create_properties' => $authorization->can_create_properties,
                'can_view_documents' => $authorization->can_view_documents,
                'evaluation_permission' => $authorization->evaluation_permission,
            ]);

            $authorization->update([
                'can_create_properties' => $request->can_create_properties,
                'can_view_documents' => $request->can_view_documents,
                'evaluation_permission' => $request->evaluation_permission,
            ]);

            // ✅ Log após a atualização
            $authorization->refresh();
            Log::info('Authorization após atualização:', [
                'can_create_properties' => $authorization->can_create_properties,
                'can_view_documents' => $authorization->can_view_documents,
                'evaluation_permission' => $authorization->evaluation_permission,
            ]);

            return redirect()->back()->with('success', 'Autorização atualizada com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar autorização:', [
                'error' => $e->getMessage(),
                'auth_id' => $authId,
                'request_data' => $request->all()
            ]);

            return redirect()->back()->with('error', 'Erro ao atualizar autorização: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        //
    }

    public function destroy(Authorization $authorization)
    {
        $authorization->delete();
        return redirect()->back()->with('message', 'Autorização removida com sucesso!');
    }
}
