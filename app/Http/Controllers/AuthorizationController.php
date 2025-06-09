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
        if (Auth::user()->profile_id != 2) {
            $authorizations = Authorization::where('owner_id', Auth::user()->id)
                ->with(['serviceProvider', 'activity'])
                ->get();
            
            return Inertia::render('Authorizations/IndexAuthorization', [
                'authorizations' => $authorizations
            ]);
        } else {
            return Inertia::render('Dashboard');
        }
    }

    public function create()
    {
        if (Auth::user()->profile_id != 2) {
            $serviceProviders = User::where('profile_id', '>', 1)->get();
            return Inertia::render('Authorizations/CreateAuthorization', [
                'serviceProviders' => $serviceProviders, 
                'user' => Auth::user()
            ]);
        } else {
            return Inertia::render('Dashboard');
        }
    }

    public function store(Request $request)
    {
        $existingProvider = Authorization::where('owner_id', Auth::user()->id)
            ->where('service_provider_id', $request->service_provider_id)
            ->exists();

        $serviceProviders = User::where('profile_id', 2)->get();
        
        if ($existingProvider === true) {
            return Inertia::render('Authorizations/CreateAuthorization', [
                'serviceProviders' => $serviceProviders, 
                'user' => Auth::user()
            ])->with(['message' => 'Já existe uma autorização para este usuário.']);
        } else {
            $request->validate([
                'service_provider_id' => 'required|exists:users,id',
                'can_view_documents' => 'boolean',
                'can_create_properties' => 'boolean',
                'evaluation_permission' => 'boolean', // ✅ Nova validação
            ]);

            Authorization::create([
                'owner_id' => Auth::user()->id,
                'service_provider_id' => $request->service_provider_id,
                'can_view_documents' => $request->can_view_documents,
                'can_create_properties' => $request->can_create_properties,
                'evaluation_permission' => $request->evaluation_permission, // ✅ Novo campo
            ]);

            return redirect()->route('authorizations.index')
                ->with('message', 'Autorização criada com sucesso!');
        }
    }

    public function edit(Authorization $authorization)
    {
        if (Gate::denies('edit-authorization', $authorization)) {
            return redirect()->back()->with('error', 'Você não tem permissão para editar esta autorização.');
        }

        return Inertia::render('Authorizations/EditAuthorization', [
            'authorization' => $authorization,
            'serviceProviders' => User::where('profile_id', 2)->get(),
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