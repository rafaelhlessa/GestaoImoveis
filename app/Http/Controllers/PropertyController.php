<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\User;
use App\Models\PropertyUser;
use App\Models\Authorization;
use App\Models\TypeOwnership;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Type;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if (auth()->user()->profile_id === 2) {
            $authorizedProperties = Authorization::where('service_provider_id', auth()->id())
                ->where('can_view_documents', true)
                ->pluck('owner_id');
                $ownerIds = PropertyUser::whereIn('user_id', $authorizedProperties)->pluck('property_id');
                $properties = Property::whereIn('id', $ownerIds)->get();

                return Inertia::render('Properties/IndexProperty', [
                    'properties' => $properties,
                    // 'properties' => Property::where('owner_id', auth()->user()->id)->get()
                ]);
        } else {
            $properties = Property::whereHas('owners', function ($query) {
                $query->where('user_id', auth()->id());
            })->get();
            return Inertia::render('Properties/IndexProperty', [
                'properties' => $properties,
                // 'properties' => Property::where('owner_id', auth()->user()->id)->get()
            ]);
        }

        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {        
        $typeOwner = TypeOwnership::all();

        return Inertia::render('Properties/CreateProperty', [
            'typeOwners' => TypeOwnership::all(),
            'users' => User::where('profile_id', '!=', 2)->get(),
            'propertyUser' => PropertyUser::all(),
        ]); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // dd($request->all());
        $request->validate([
            'is_active' => 'required|boolean',
            'title_deed' => 'required|integer',
            'title_deed_number' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'type_property' => 'required|integer',
            'city' => 'required|string|max:255',
            'city_id' => 'required|integer',
            // 'file_photo' => 'required|file|mimes:jpg,jpeg,png|max:2048',

            // 'documents' => 'required|array',
            // 'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',

            'owners' => 'required|array',
            'owners.*.id' => 'required|integer',
            'owners.*.type_ownership' => 'required|integer',
            'owners.*.percent' => 'required|integer',

        ]);


        // DB::transaction(function () use ($request) {
            $property = Property::create([
                'title_deed' => $request->title_deed,
                'title_deed_number' => $request->title_deed_number,
                'type_property' => $request->type_property,
                'other' => $request->other,
                'area' => $request->area,
                'unit' => $request->unit,
                'city' => $request->city,
                'city_id' => $request->city_id,
                'district' => $request->district,
                'locality' => $request->locality,
                'nickname' => $request->nickname,
                'about' => $request->about,
                'file_photo' => $request->file_photo,
            ]);

            // $property = Property::create($request->all());
            // $property->file_photo = $request->file_photo->store('properties', 'public');
            

            foreach ($request->documents as $document) {
                if ($document['date'] === "Sem Data") {
                    $document['date'] = null;
                }
                PropertyDocument::create([
                    'name' => $document['name'],
                    'date' => $document['date'],
                    'show' => $document['show'],
                    'file' => $document['file'],
                    'file_name' => $document['file_name'],
                    'property_id' => $property->id,
                ]);
            }

            foreach ($request->owners as $owner) {
                PropertyUser::create([
                    'user_id' => $owner['id'],
                    'type_ownership_id' => $owner['type_ownership'],
                    'percentage' => $owner['percent'],
                    'other' => $owner['observations'],
                    'property_id' => $property->id,
                ]);
            }
        // });

        
        
        return redirect()->route('property.show', $property->id)->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $property = Property::find($id);

        if (!$property) {
            abort(404, 'Propriedade não encontrada.');
        }

        $query = PropertyUser::where('property_id', $id);

        if ($user->profile_id == 1) { // Proprietário pode acessar suas propriedades
            $query->where('user_id', $user->id);
        } elseif ($user->profile_id == 3) { // Proprietário e Prestador de Serviço
            $query->where('user_id', $user->id)
                ->orWhereExists(function ($subQuery) use ($user, $property) {
                    $subQuery->select(DB::raw(1))
                        ->from('authorizations')
                        ->whereColumn('authorizations.owner_id', 'property_user.user_id')
                        ->where('authorizations.service_provider_id', $user->id)
                        ->where('authorizations.can_view_documents', true);
                });
        } else {
            abort(403, 'Acesso não autorizado.');
        }

        $owners = $query->get();

        return Inertia::render('Properties/ShowProperty', [
            'property' => $property,
            'documents' => PropertyDocument::where('property_id', $id)->get(),
            'owners' => $owners,
            'success' => session('success'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $property = Property::with([
            'documents',
            'owners',
            'typeOwnerships',
        ])->findOrFail($id);
        
        return Inertia::render('Properties/EditProperty', [
            'property' => $property,
            'typeOwners' => TypeOwnership::all(),
            'users' => User::where('profile_id', '!=', 2)->get(),
            'owners' => PropertyUser::with(['typeOwnership', 'user', 'property'])->where('property_id', $id)->get(),
            'documents' => PropertyDocument::where('property_id', $id)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request);
        $request->validate([
            'title_deed' => 'required|integer|max:255',
            'title_deed_number' => 'required|string|max:255',
            'other' => 'nullable|string|max:255',
            'area' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'city_id' => 'required|integer',
            'district' => 'nullable|string|max:255',
            'locality' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'about' => 'nullable|string',
            'file_photo' => 'nullable|string', // Alterado para aceitar Base64
            'documents' => 'nullable|array',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.file' => 'required|string', // Alterado para aceitar Base64
            'documents.*.file_name' => 'required|string|max:255',
            'owners' => 'required|array|min:1', 
            // 'owners.*.id' => 'integer', // Garante que o usuário existe
            'owners.*.type_ownership_id' => 'required|integer', 
            'owners.*.percentage' => 'required|integer|min:0|max:100',

        ]);

        $property = Property::findOrFail($id);
        // Atualiza somente se houver mudanças na propriedade
        $updatedData = $request->except(['documents', 'owners']);
        if ($property->isDirty($updatedData)) {
            $property->update($updatedData);
        }

        $property->update([
            'title_deed' => $request->title_deed,
            'title_deed_number' => $request->title_deed_number,
            'type_property' => $request->type_property,
            'other' => $request->other,
            'area' => $request->area,
            'unit' => $request->unit,
            'city' => $request->city,
            'city_id' => $request->city_id,
            'district' => $request->district,
            'locality' => $request->locality,
            'nickname' => $request->nickname,
            'about' => $request->about,
            'file_photo' => $request->file_photo,
            
        ]);

    // Atualiza a foto somente se for diferente
    if ($request->has('file_photo') && $property->file_photo !== $request->file_photo) {
        $property->update(['file_photo' => $request->file_photo]);
    }

    

    DB::transaction(function () use ($request, $property) {
        // Atualizar os dados da propriedade, exceto documentos e proprietários
        $property->update($request->except(['documents', 'owners']));
    
        // Remover todos os proprietários atuais
        // PropertyUser::where('property_id', $property->id)->delete();
        DB::table('property_user')->where('property_id', $property->id)->delete();
        
        // Adicionar os novos proprietários do request
        foreach ($request->owners as $owner) {
            PropertyUser::create([
                'user_id' => $owner['user_id'],
                'type_ownership_id' => $owner['type_ownership_id'],
                'percentage' => $owner['percentage'],
                'other' => $owner['observations'] ?? null,
                'property_id' => $property->id,
            ]);
        }
    
        // 🔄 **Atualizar Documentos**
        $existingDocuments = PropertyDocument::where('property_id', $property->id)->pluck('id', 'file_name')->toArray();
        $requestDocuments = collect($request->documents)->keyBy('file_name');
    
        // 🚀 **Remover documentos que não estão mais na lista**
        foreach ($existingDocuments as $fileName => $docId) {
            if (!$requestDocuments->has($fileName)) {
                PropertyDocument::where('id', $docId)->delete();
            }
        }
    
        // 🔄 **Adicionar ou atualizar documentos**
        foreach ($request->documents as $document) {
            if (isset($existingDocuments[$document['file_name']])) {
                // Atualizar documento existente
                PropertyDocument::where('id', $existingDocuments[$document['file_name']])
                    ->update([
                        'name' => $document['name'],
                        'date' => $document['date'] ?? null,
                        'show' => $document['show'],
                        'file' => $document['file'],
                    ]);
            } else {
                // Criar novo documento
                PropertyDocument::create([
                    'name' => $document['name'],
                    'date' => $document['date'] ?? null,
                    'show' => $document['show'],
                    'file' => $document['file'],
                    'file_name' => $document['file_name'],
                    'property_id' => $property->id,
                ]);
            }
        }
    });
    
    

    return redirect()->route('property.show', $id)->with('success', 'Propriedade atualizada com sucesso.');
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
