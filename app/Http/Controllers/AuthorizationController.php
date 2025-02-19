<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthorizationController extends Controller
{
    public function index()
    {
        if (auth()->user()->profile_id != 2) {
            // $authorizations = Authorization::where('owner_id', auth()->id())->with('serviceProvider', 'activity')->get();
            $authorizations = Authorization::where('owner_id', auth()->id())
            ->with(['serviceProvider', 'activity']) // Carregar activity corretamente
            ->get();
            // dd($authorizations);
            
            return Inertia::render('Authorizations/IndexAuthorization', ['authorizations' => $authorizations]);
        } else {
            return Inertia::render('dashboard');
        }
    }

    public function create()
    {
        if (auth()->user()->profile_id != 2) {
            $serviceProviders = User::where('profile_id', '>', 1)->get();
            return Inertia::render('Authorizations/CreateAuthorization', ['serviceProviders' => $serviceProviders, 'user' => auth()->user()]);
        } else {
            return Inertia::render('Dashboard');
        }
        
    }

    public function store(Request $request)
    {
        $existisProvider = Authorization::where('owner_id', auth()->user()->id)
        ->where('service_provider_id', $request->service_provider_id)
        ->exists();

        $serviceProviders = User::where('profile_id', 2)->get();
        if ($existisProvider === true) {
            return Inertia::render('Authorizations/CreateAuthorization', ['serviceProviders' => $serviceProviders, 'user' => auth()->user()])->with(['message' => 'Já existe uma autorização para este usuário.']);
        } else {
            $request->validate([
                'service_provider_id' => 'required|exists:users,id',
                'can_view_documents' => 'boolean',
                'can_create_properties' => 'boolean',
            ]);

            Authorization::create([
                'owner_id' => auth()->user()->id,
                'service_provider_id' => $request->service_provider_id,
                'can_view_documents' => $request->can_view_documents,
                'can_create_properties' => $request->can_create_properties,
            ]);

            return redirect()->route('authorizations.index')->with('message', 'Autorização criada com sucesso!');
            // como utilizar o método with() para enviar uma mensagem de sucesso para o componente vue.
        }
        // return redirect()->back()->with('message', 'Autorização concedida com sucesso!');
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

    public function updateAuthChange(Request $request, string $authId)
    {
        
        $request->validate([
            'can_create_properties' => 'required|boolean',
            'can_view_documents' => 'required|boolean',
        ]);
        

        $authorization = Authorization::findOrFail($authId);
        $authorization->update([
            'can_create_properties' => $request->can_create_properties,
            'can_view_documents' => $request->can_view_documents,
        ]);

        return redirect()->back()->with('success', 'Document visibility updated successfully.');
    }
}
