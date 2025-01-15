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
        return Inertia::render('Authorizations/IndexAuthorization');
        if (auth()->user()->profile_id != 2) {
            $authorizations = Authorization::where('service_provider_id', auth()->id())->get();
        } else {
            
            $authorizations = Authorization::with(['owner', 'serviceProvider'])->get();
            return Inertia::render('Authorizations/IndexAuthorization', ['authorizations' => $authorizations]);
        }
    }

    public function create()
    {
        return Inertia::render('Authorizations/CreateAuthorization');
    }

    public function store(Request $request)
    {
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

        return redirect()->back()->with('message', 'Autorização concedida com sucesso!');
    }

    public function destroy(Authorization $authorization)
    {
        $authorization->delete();
        return redirect()->back()->with('message', 'Autorização removida com sucesso!');
    }
}
