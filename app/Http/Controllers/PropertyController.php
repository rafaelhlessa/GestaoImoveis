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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
            if ($user->profile_id === 1 || $user->profile_id === 3) {
                // Usuário comum vê apenas suas próprias propriedades
                $properties = Property::whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->with('owners')->get();

                return Inertia::render('Properties/IndexProperty', [
                    'properties' => $properties,
                    'owner_id' => $user->id
                ]);
            } else {
                return redirect()->route('dashboard')->with('error', 'Você não tem permissão para criar propriedades para este proprietário.');
            }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {        
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
        // dd($request);
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'type_property' => 'required|integer',
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
            'owners' => 'required|array',
            'owners.*.id' => 'required|integer',
            'owners.*.type_ownership' => 'required|integer',
            'owners.*.percent' => 'required|integer',
        ]);
    
        $propertyId = null;

        DB::transaction(function () use ($validated, $request, & $propertyId) {
            $property = Property::create($validated);
    
            // 🔄 Inserindo Documentos em Massa
            $documents = collect($request->documents)->map(function ($doc) use ($property) {
                return [
                    'name' => $doc['name'],
                    'date' => $doc['date'] === "Sem Data" ? null : $doc['date'],
                    'show' => $doc['show'],
                    'file' => $doc['file'],
                    'file_name' => $doc['file_name'],
                    'property_id' => $property->id,
                ];
            })->toArray();
    
            PropertyDocument::insert($documents);
    
            // 🔄 Inserindo Proprietários em Massa
            $owners = collect($request->owners)->map(function ($owner) use ($property) {
                return [
                    'user_id' => $owner['id'],
                    'type_ownership_id' => $owner['type_ownership'],
                    'percentage' => $owner['percent'],
                    'other' => $owner['observations'],
                    'property_id' => $property->id,
                ];
            })->toArray();
    
            PropertyUser::insert($owners);

            $propertyId = $property->id;

        });
    
        return redirect()->route('property.show', $propertyId)->with('success', 'Propriedade criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        // 🔹 Busca a propriedade e verifica se existe
        $property = Property::with(['owners', 'documents'])->find($id);
        
        $canEdit = false;
        
        if (!$property) {
            abort(404, 'Propriedade não encontrada.');
        }

        // 🔹 Se o usuário for proprietário, pode acessar diretamente
        if ($user->profile_id === 1 || $user->profile_id === 3) {
            $hasAccess = PropertyUser::where('property_id', $id)
                ->where('user_id', $user->id)
                ->exists();
        } 
        // 🔹 Se for prestador de serviço, verifica as autorizações
        else {
            $hasAccess = Authorization::where('service_provider_id', $user->id)
            ->where('can_view_documents', true)
            ->whereHas('owner.properties', function ($query) use ($id) {
                $query->where('properties.id', $id); // 🛠 Especificamos a tabela properties
            })
            ->exists();

            $canEdit = $user->profile_id === 2 && 
                Authorization::where('service_provider_id', $user->id)
                ->where('can_create_properties', true)
                ->exists();
            // dd($hasAccess);
            dd($canEdit);
        } 
        // // 🔹 Se for outro tipo de usuário, verifica permissões adicionais
        // else {
        //     $hasAccess = Authorization::where('service_provider_id', $user->id)
        //     ->where('can_view_documents', true)
        //     ->whereHas('owner.properties', function ($query) use ($id) {
        //         $query->where('properties.id', $id); // 🛠 Especificamos a tabela properties
        //     })
        //     ->exists();
        // }

        
        // // 🔹 Se o usuário não tem acesso, retorna erro 403
        // if (!$hasAccess) {
        //     abort(403, 'Acesso não autorizado.');
        // }

        // 🔹 Retorna a propriedade com os dados necessários
        return Inertia::render('Properties/ShowProperty', [
            'property' => $property,
            'documents' => $property->documents,
            'owners' => $property->owners,
            'success' => session('success'),
            'isServiceProvider' => $user->profile_id === 2,
            'canEdit' => $canEdit,
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
                        'date' => $document['date'] === "Sem Data" ? null : $document['date'],
                        'show' => $document['show'],
                        'file' => $document['file'],
                    ]);
            } else {
                // Criar novo documento
                PropertyDocument::create([
                    'name' => $document['name'],
                    'date' => $document['date'] === "Sem Data" ? null : $document['date'],
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

    /**
     * Update the 'show' field of a document.
     */
    public function updateDocumentShow(Request $request, string $documentId)
    {
        $request->validate([
            'show' => 'required|boolean',
        ]);

        $document = PropertyDocument::findOrFail($documentId);
        $document->update(['show' => $request->show]);

        return redirect()->back()->with('success', 'Document visibility updated successfully.');
    }

    public function clientsProperty($id = null)
    {
        $user = auth()->user();

        // Busca o proprietário correspondente com seus relacionamentos
        $owner = User::where('id', $id)->with('typeOwnership')->firstOrFail();

        // Obtém todas as autorizações desse prestador de serviço para esse proprietário específico
        $authorizations = Authorization::where('service_provider_id', $user->id)
            ->where('owner_id', $id)
            ->get();

        // Define as permissões com base nas autorizações recuperadas
        $canView = $authorizations->contains('can_view_documents', true);
        $canCreateOwners = $authorizations->where('can_create_properties', true)->pluck('owner_id')->toArray();

        // Define a variável `$canCreate`
        $canCreate = !empty($canCreateOwners) ? [
            'can_create' => true,
            'owners' => $canCreateOwners
        ] : [
            'can_create' => false,
            'owners' => []
        ];

        // Se o prestador não tiver permissão para visualizar ou editar, redireciona
        if ($user->profile_id > 1) { // Apenas prestadores de serviço
            if (!$canView && !$canCreate['can_create']) {
                return redirect()->route('dashboard')->with('error', 'Você não tem permissão para acessar essas propriedades.');
            }

            // Obtém as propriedades do proprietário se tiver permissão
            $properties = Property::whereHas('owners', function ($query) use ($id) {
                $query->where('user_id', $id);
            })->with(['owners', 'authorizations'])->get();

            return Inertia::render('Clients/IndexProperty', [
                'properties' => $properties,
                'owner' => $owner,
                'canView' => $canView,
                'canCreate' => $canCreate
            ]);
        } else {
            return redirect()->route('dashboard')->with('error', 'Você não tem permissão para acessar essa página.');
        }
    }

    public function clientShow(string $id)
    {
        $user = auth()->user();

        // 🔹 Busca a propriedade e verifica se existe
        $property = Property::with(['owners', 'documents'])->find($id);
        $typeOwnership = TypeOwnership::all();

        $canView = false;
        $canCreate = false;
        
        if (!$property) {
            abort(404, 'Propriedade não encontrada.');
        }

        // 🔹 Se o usuário for proprietário, pode acessar diretamente
        if ($user->profile_id === 1 ) {
            $hasAccess = PropertyUser::where('property_id', $id)
                ->where('user_id', $user->id)
                ->exists();
        } 
        // 🔹 Se for prestador de serviço, verifica as autorizações
        else {
            $canView = Authorization::where('service_provider_id', $user->id)
            ->where('can_view_documents', true)
            ->whereHas('owner.properties', function ($query) use ($id) {
                $query->where('properties.id', $id); // 🛠 Especificamos a tabela properties
            })
            ->exists();

            $canCreate = $user->profile_id > 1  && 
                Authorization::where('service_provider_id', $user->id)
                ->where('can_create_properties', true)
                ->whereHas('owner.properties', function ($query) use ($id) {
                    $query->where('properties.id', $id); // 🛠 Especificamos a tabela properties
                })
                ->exists();
        } 
        // // 🔹 Se for outro tipo de usuário, verifica permissões adicionais
        // else {
        //     $hasAccess = Authorization::where('service_provider_id', $user->id)
        //     ->where('can_view_documents', true)
        //     ->whereHas('owner.properties', function ($query) use ($id) {
        //         $query->where('properties.id', $id); // 🛠 Especificamos a tabela properties
        //     })
        //     ->exists();
        // }

        
        // // 🔹 Se o usuário não tem acesso, retorna erro 403
        // if (!$hasAccess) {
        //     abort(403, 'Acesso não autorizado.');
        // }
        

        // 🔹 Retorna a propriedade com os dados necessários
        return Inertia::render('Clients/ShowProperty', [
            'property' => $property,
            'documents' => $property->documents,
            'owners' => $property->owners,
            'success' => session('success'),
            'isServiceProvider' => $user->profile_id === 2,
            'typeOwnership' => $typeOwnership,
            'canView' => $canView,
            'canCreate' => $canCreate,

        ]);
    }

    public function viewDocument($id)
    {
        // Busca o documento no banco de dados
        $document = PropertyDocument::findOrFail($id);

        // Se o documento não existir ou não tiver um arquivo válido, retorna erro 404
        if (!$document || !$document->file) {
            abort(404, 'Documento não encontrado.');
        }

        // Converte o base64 para binário
        $fileData = base64_decode($document->file);

        // Identifica o tipo do arquivo (PDF ou KML)
        $mimeType = 'application/octet-stream'; // Tipo padrão genérico

        if (str_ends_with(strtolower($document->file_name), '.pdf')) {
            $mimeType = 'application/pdf';
        } elseif (str_ends_with(strtolower($document->file_name), '.kml')) {
            $mimeType = 'application/vnd.google-earth.kml+xml';
        }

        // Retorna o arquivo como resposta HTTP para exibição direta no navegador
        return Response::make($fileData, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }

}
