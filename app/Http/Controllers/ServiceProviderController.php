<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use App\Models\Property;
use App\Models\PropertyEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->profile_id === 1) {
            // Proprietário puro - apenas suas propriedades, SEM clientes
            $valuationData = $this->getOwnerValuationData($user);
            $stats = $this->getOwnerStats($user);
            
            return Inertia::render('Dashboard', [
                'valuationData' => $valuationData,
                'stats' => $stats,
                'properties' => Property::whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->with(['owners', 'evaluations' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }])->with(['owners', 'evaluations' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }])->get(),
                'evaluations' => PropertyEvaluation::whereHas('property.owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->latest()->get(),
                'latestEvaluations' => PropertyEvaluation::whereHas('property.owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->latest()
                ->get()
                ->unique('property_id')
                ->keyBy('property_id'),
                'showGraphs' => true,
                'showClients' => false,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_id' => $user->profile_id
                ],
            ]);
            
        } else if ($user->profile_id === 2) {
            // Prestador puro - apenas clientes, SEM gráficos
            $serviceProviderId = $user->id;
            
            $serviceProviders = Authorization::where('service_provider_id', $serviceProviderId)
                ->with('owner')
                ->get();
            
            $owners = [];
            foreach ($serviceProviders as $serviceProvider) {
                if ($serviceProvider->can_create_properties || $serviceProvider->can_view_documents) {
                    $owners[] = $serviceProvider->owner;
                }
            }

            return Inertia::render('DashboardService', [
                'serviceProviders' => $owners,
                'valuationData' => ['urban' => [], 'commercial' => [], 'rural' => []],
                'stats' => [
                    'totalClients' => count($owners),
                    'totalProperties' => 0,
                    'totalEvaluations' => 0,
                    'propertiesByType' => []
                ],
                'showGraphs' => false,
                'showClients' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_id' => $user->profile_id
                ]
            ]);

        } else if ($user->profile_id === 3) {
            // Proprietário/Prestador - suas propriedades + clientes
            $serviceProviderId = $user->id;
            
            $serviceProviders = Authorization::where('service_provider_id', $serviceProviderId)
                ->with('owner')
                ->get();
            
            $owners = [];
            foreach ($serviceProviders as $serviceProvider) {
                if ($serviceProvider->can_create_properties || $serviceProvider->can_view_documents) {
                    $owners[] = $serviceProvider->owner;
                }
            }

            // Gráfico das PRÓPRIAS propriedades, não dos clientes
            $valuationData = $this->getOwnerValuationData($user);
            $stats = $this->getOwnerStats($user);
            
            // Adicionar estatística de clientes
            $stats['totalClients'] = count($owners);

            return Inertia::render('DashboardService', [
                'serviceProviders' => $owners,
                'valuationData' => $valuationData,
                'stats' => $stats,
                'showGraphs' => true,
                'showClients' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_id' => $user->profile_id
                ]
            ]);

        } else {
            return Inertia::render('Dashboard');
        }
    }

    /**
     * Retorna dados de valorização das PRÓPRIAS propriedades do usuário (não dos clientes)
     */
    private function getOwnerValuationData($user)
    {
        // Buscar apenas propriedades onde o usuário é proprietário
        $propertyIds = Property::whereHas('owners', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('id')->toArray();
        
        if (empty($propertyIds)) {
            return [
                'urban' => [],
                'commercial' => [],
                'rural' => []
            ];
        }
        
        // Buscar avaliações das últimas 12 meses das próprias propriedades
        $evaluations = DB::table('property_evaluations as pe')
            ->whereIn('pe.property_id', $propertyIds)
            ->where('pe.created_at', '>=', now()->subMonths(12))
            ->select([
                'pe.property_type',
                'pe.urban_subtype', 
                'pe.valuation',
                'pe.created_at',
                DB::raw('DATE_FORMAT(pe.created_at, "%Y-%m") as month_year')
            ])
            ->orderBy('pe.created_at')
            ->get();
        
        // Agrupar por tipo e calcular médias mensais
        $grouped = $evaluations->groupBy(function($item) {
            if ($item->property_type === 'urbana') {
                return $item->urban_subtype === 'residencial' ? 'urban' : 'commercial';
            }
            return 'rural';
        });
        
        $result = [];
        
        foreach (['urban', 'commercial', 'rural'] as $type) {
            if (!isset($grouped[$type])) {
                $result[$type] = [];
                continue;
            }
            
            $monthlyData = $grouped[$type]->groupBy('month_year')->map(function($items, $month) {
                return [
                    'month' => $month,
                    'value' => $items->avg('valuation'),
                    'count' => $items->count()
                ];
            })->values()->toArray();
            
            $result[$type] = $monthlyData;
        }
        
        return $result;
    }
    
    /**
     * Busca estatísticas das PRÓPRIAS propriedades do usuário
     */
    private function getOwnerStats($user)
    {
        // Buscar apenas propriedades onde o usuário é proprietário
        $propertyIds = Property::whereHas('owners', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('id')->toArray();
        
        if (empty($propertyIds)) {
            return [
                'totalProperties' => 0,
                'totalEvaluations' => 0,
                'averageValuation' => 0,
                'lastMonthGrowth' => 0,
                'propertiesByType' => []
            ];
        }
        
        // Contar propriedades por tipo (baseado nas avaliações)
        $propertiesByType = DB::table('property_evaluations as pe')
            ->whereIn('pe.property_id', $propertyIds)
            ->select([
                DB::raw('CASE 
                    WHEN pe.property_type = "urbana" AND pe.urban_subtype = "residencial" THEN "Urbanas"
                    WHEN pe.property_type = "urbana" AND pe.urban_subtype = "comercial" THEN "Comerciais" 
                    WHEN pe.property_type = "rural" THEN "Rurais"
                    ELSE "Outros"
                END as type'),
                DB::raw('COUNT(DISTINCT pe.property_id) as count')
            ])
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
        
        // Total de propriedades únicas
        $totalProperties = count($propertyIds);
            
        // Estatísticas de avaliações
        $totalEvaluations = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->count();
            
        $averageValuation = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->avg('valuation') ?? 0;
        
        // Crescimento do último mês
        $currentMonth = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->where('created_at', '>=', now()->startOfMonth())
            ->avg('valuation') ?? 0;
            
        $lastMonth = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->avg('valuation') ?? 0;
        
        $growth = 0;
        if ($lastMonth > 0) {
            $growth = (($currentMonth - $lastMonth) / $lastMonth) * 100;
        }
        
        return [
            'totalProperties' => $totalProperties,
            'propertiesByType' => $propertiesByType,
            'totalEvaluations' => $totalEvaluations,
            'averageValuation' => $averageValuation,
            'lastMonthGrowth' => round($growth, 2)
        ];
    }

    /**
     * Retorna dados de valorização patrimonial baseado no perfil do usuário
     */
    private function getValuationData()
    {
        $user = Auth::user();
        $propertyIds = $this->getUserPropertyIds($user);
        
        if (empty($propertyIds)) {
            return [
                'urban' => [],
                'commercial' => [],
                'rural' => []
            ];
        }
        
        // Buscar avaliações das últimas 12 meses
        $evaluations = DB::table('property_evaluations as pe')
            ->whereIn('pe.property_id', $propertyIds)
            ->where('pe.created_at', '>=', now()->subMonths(12))
            ->select([
                'pe.property_type',
                'pe.urban_subtype', 
                'pe.valuation',
                'pe.created_at',
                DB::raw('DATE_FORMAT(pe.created_at, "%Y-%m") as month_year')
            ])
            ->orderBy('pe.created_at')
            ->get();
        
        // Agrupar por tipo e calcular médias mensais
        $grouped = $evaluations->groupBy(function($item) {
            if ($item->property_type === 'urbana') {
                return $item->urban_subtype === 'residencial' ? 'urban' : 'commercial';
            }
            return 'rural';
        });
        
        $result = [];
        
        foreach (['urban', 'commercial', 'rural'] as $type) {
            if (!isset($grouped[$type])) {
                $result[$type] = [];
                continue;
            }
            
            $monthlyData = $grouped[$type]->groupBy('month_year')->map(function($items, $month) {
                return [
                    'month' => $month,
                    'value' => $items->avg('valuation'),
                    'count' => $items->count()
                ];
            })->values()->toArray();
            
            $result[$type] = $monthlyData;
        }
        
        return $result;
    }
    
    /**
     * Busca estatísticas gerais do dashboard
     */
    private function getDashboardStats()
    {
        $user = Auth::user();
        $propertyIds = $this->getUserPropertyIds($user);
        
        if (empty($propertyIds)) {
            return [
                'totalProperties' => 0,
                'totalClients' => 0,
                'totalEvaluations' => 0,
                'averageValuation' => 0,
                'lastMonthGrowth' => 0,
                'propertiesByType' => []
            ];
        }
        
        // Contar propriedades por tipo
        $propertiesByType = DB::table('property_evaluations as pe')
            ->whereIn('pe.property_id', $propertyIds)
            ->select([
                DB::raw('CASE 
                    WHEN pe.property_type = "urbana" AND pe.urban_subtype = "residencial" THEN "Urbanas"
                    WHEN pe.property_type = "urbana" AND pe.urban_subtype = "comercial" THEN "Comerciais" 
                    WHEN pe.property_type = "rural" THEN "Rurais"
                    ELSE "Outros"
                END as type'),
                DB::raw('COUNT(DISTINCT pe.property_id) as count')
            ])
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
        
        // Total de propriedades únicas
        $totalProperties = DB::table('properties')
            ->whereIn('id', $propertyIds)
            ->count();
            
        // Total de clientes únicos (para prestadores de serviço)
        $totalClients = 0;
        if (in_array($user->profile_id, [2, 3])) {
            $totalClients = Authorization::where('service_provider_id', $user->id)
                ->where(function($query) {
                    $query->where('can_create_properties', 1)
                          ->orWhere('can_view_documents', 1);
                })
                ->distinct('owner_id')
                ->count();
        }
        
        // Estatísticas de avaliações
        $totalEvaluations = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->count();
            
        $averageValuation = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->avg('valuation') ?? 0;
        
        // Crescimento do último mês
        $currentMonth = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->where('created_at', '>=', now()->startOfMonth())
            ->avg('valuation') ?? 0;
            
        $lastMonth = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->avg('valuation') ?? 0;
        
        $growth = 0;
        if ($lastMonth > 0) {
            $growth = (($currentMonth - $lastMonth) / $lastMonth) * 100;
        }
        
        return [
            'totalProperties' => $totalProperties,
            'totalClients' => $totalClients,
            'propertiesByType' => $propertiesByType,
            'totalEvaluations' => $totalEvaluations,
            'averageValuation' => $averageValuation,
            'lastMonthGrowth' => round($growth, 2)
        ];
    }
    
    /**
     * Busca IDs das propriedades que o usuário tem acesso baseado no perfil
     */
    private function getUserPropertyIds($user)
    {
        switch ($user->profile_id) {
            case 1: // Proprietário puro
                return Property::whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->pluck('id')->toArray();
                
            case 2: // Prestador de serviço puro
                return DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('can_view_documents', 1)
                    ->join('property_user', 'property_user.user_id', '=', 'authorizations.owner_id')
                    ->pluck('property_user.property_id')
                    ->unique()
                    ->toArray();
                    
            case 3: // Proprietário/Prestador
                $ownProperties = Property::whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->pluck('id')->toArray();
                
                $authorizedProperties = DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('can_view_documents', 1)
                    ->join('property_user', 'property_user.user_id', '=', 'authorizations.owner_id')
                    ->pluck('property_user.property_id')
                    ->toArray();
                    
                return array_unique(array_merge($ownProperties, $authorizedProperties));
                
            default:
                return [];
        }
    }

    /**
     * API endpoint para buscar dados de valorização (AJAX)
     */
    public function getValuationDataApi()
    {
        return response()->json($this->getValuationData());
    }

    /**
     * API endpoint para buscar estatísticas do dashboard (AJAX)
     */
    public function getDashboardStatsApi()
    {
        return response()->json($this->getDashboardStats());
    }

    /**
     * Buscar propriedades de um cliente específico (para prestadores de serviço)
     */
    public function getClientProperties($clientId)
    {
        $user = Auth::user();
        
        // Verificar se o usuário tem permissão para ver propriedades deste cliente
        if (!in_array($user->profile_id, [2, 3])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }
        
        $hasPermission = Authorization::where('service_provider_id', $user->id)
            ->where('owner_id', $clientId)
            ->where(function($query) {
                $query->where('can_view_documents', 1)
                      ->orWhere('can_create_properties', 1);
            })
            ->exists();
            
        if (!$hasPermission) {
            return response()->json(['error' => 'Sem permissão para este cliente'], 403);
        }
        
        // Buscar propriedades do cliente
        $properties = Property::whereHas('owners', function ($query) use ($clientId) {
            $query->where('user_id', $clientId);
        })->with(['owners', 'evaluations' => function($query) {
            $query->latest()->limit(1); // Última avaliação
        }])->get();
        
        return response()->json($properties);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}