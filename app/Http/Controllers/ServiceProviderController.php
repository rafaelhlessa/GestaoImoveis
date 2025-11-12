<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use App\Models\Property;
use App\Models\PropertyEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ServiceProviderController extends Controller
{
    // Cache TTL em minutos
    private const CACHE_TTL = 15;
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = $this->loadUserWithProfiles(Auth::user());
        
        // Determinar tipo de perfil
        $isOwner = $user->hasProfile('proprietario');
        $isProvider = $user->hasProfile('prestador');
        
        // Proprietário puro
        if ($isOwner && !$isProvider) {
            return $this->ownerDashboard($user);
        }
        
        // Prestador puro
        if ($isProvider && !$isOwner) {
            return $this->providerDashboard($user);
        }
        
        // Dual profile (Proprietário + Prestador)
        if ($isOwner && $isProvider) {
            return $this->dualProfileDashboard($user);
        }
        
        // Nenhum perfil reconhecido
        return Inertia::render('Dashboard');
    }

    /**
     * Dashboard para proprietário puro
     */
    private function ownerDashboard($user)
    {
        $cacheKey = "dashboard_owner_{$user->id}";
        
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $propertyIds = $this->getOwnerPropertyIds($user->id);
            
            return [
                'valuationData' => $this->getOwnerValuationData($propertyIds),
                'stats' => $this->getOwnerStats($propertyIds),
                'properties' => Property::whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['owners', 'evaluations' => function($query) {
                    $query->latest()->limit(5);
                }])
                ->get(),
            ];
        });
        
        // Avaliações não são cacheadas pois mudam com frequência
        $evaluations = PropertyEvaluation::whereHas('property.owners', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        // Select only existing columns; properties table has no 'name' column.
        // Include fields useful for display (nickname/address).
        ->with(['property' => function($q) { $q->select('id', 'nickname', 'address'); }])
        ->latest()
        ->get();
        
        $latestEvaluations = $evaluations
            ->unique('property_id')
            ->keyBy('property_id');
        
        return Inertia::render('Dashboard', array_merge($data, [
            'evaluations' => $evaluations,
            'latestEvaluations' => $latestEvaluations,
            'showGraphs' => true,
            'showClients' => false,
            'user' => $this->formatUserData($user),
        ]));
    }

    /**
     * Dashboard para prestador puro
     */
    private function providerDashboard($user)
    {
        $cacheKey = "dashboard_provider_{$user->id}";
        
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            // Otimizar query com eager loading e filtros diretos
            $authorizations = Authorization::where('service_provider_id', $user->id)
                ->where(function($query) {
                    $query->where('can_create_properties', true)
                          ->orWhere('can_view_documents', true);
                })
                ->with(['owner' => function($query) {
                    $query->withCount([
                        'properties',
                        'properties as evaluations_count' => function($q) {
                            $q->join('property_evaluations', 'properties.id', '=', 'property_evaluations.property_id');
                        }
                    ]);
                }])
                ->get();
            
            $clients = $authorizations->map(function($auth) {
                $owner = $auth->owner;
                $owner->permissions = [
                    'can_view_documents' => $auth->can_view_documents,
                    'can_create_properties' => $auth->can_create_properties,
                    'evaluation_permission' => $auth->evaluation_permission ?? false,
                ];
                return $owner;
            });
            
            $totalProperties = $clients->sum('properties_count');
            $totalEvaluations = $clients->sum('evaluations_count');
            
            return [
                'clients' => $clients,
                'stats' => [
                    'totalClients' => $clients->count(),
                    'totalProperties' => $totalProperties,
                    'totalEvaluations' => $totalEvaluations,
                    'activeAuthorizations' => $authorizations->count(),
                ],
            ];
        });
        
        return Inertia::render('DashboardServiceModern', array_merge($data, [
            'user' => $this->formatUserData($user),
        ]));
    }

    /**
     * Dashboard para perfil dual
     */
    private function dualProfileDashboard($user)
    {
        $cacheKey = "dashboard_dual_{$user->id}";
        
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $propertyIds = $this->getOwnerPropertyIds($user->id);
            
            $clients = Authorization::where('service_provider_id', $user->id)
                ->where(function($query) {
                    $query->where('can_create_properties', true)
                          ->orWhere('can_view_documents', true);
                })
                ->with('owner')
                ->get()
                ->pluck('owner');
            
            $stats = $this->getOwnerStats($propertyIds);
            $stats['totalClients'] = $clients->count();
            
            return [
                'serviceProviders' => $clients,
                'valuationData' => $this->getOwnerValuationData($propertyIds),
                'stats' => $stats,
            ];
        });
        
        return Inertia::render('DashboardService', array_merge($data, [
            'showGraphs' => true,
            'showClients' => true,
            'user' => $this->formatUserData($user),
        ]));
    }

    /**
     * Retorna dados de valorização com query otimizada
     */
    private function getOwnerValuationData(array $propertyIds)
    {
        if (empty($propertyIds)) {
            return [
                'urban_residential' => [],
                'urban_commercial' => [],
                'urban_misto' => [],
                'rural' => [],
                'industrial' => [],
            ];
        }
        
        $evaluations = PropertyEvaluation::query()
            ->whereIn('property_id', $propertyIds)
            ->where('created_at', '>=', now()->subMonths(12))
            ->select([
                'property_type',
                'urban_subtype',
                'valuation',
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_year')
            ])
            ->orderBy('created_at')
            ->get();
        
        return $this->groupEvaluationsByType($evaluations);
    }
    
    /**
     * Agrupa avaliações por tipo
     */
    private function groupEvaluationsByType($evaluations)
    {
        $grouped = $evaluations->groupBy(function($item) {
            if ($item->property_type === 'urbana') {
                return match ($item->urban_subtype) {
                    'residencial' => 'urban_residential',
                    'comercial' => 'urban_commercial',
                    'misto' => 'urban_misto',
                    default => 'urban_residential',
                };
            }
            if ($item->property_type === 'industrial') {
                return 'industrial';
            }
            return 'rural';
        });

        $result = [];

        foreach (['urban_residential', 'urban_commercial', 'urban_misto', 'rural', 'industrial'] as $type) {
            $result[$type] = isset($grouped[$type])
                ? $grouped[$type]
                    ->groupBy('month_year')
                    ->map(fn($items, $month) => [
                        'month' => $month,
                        'value' => round($items->avg('valuation'), 2),
                        'count' => $items->count()
                    ])
                    ->values()
                    ->toArray()
                : [];
        }

        return $result;
    }
    
    /**
     * Busca estatísticas otimizadas
     */
    private function getOwnerStats(array $propertyIds)
    {
        if (empty($propertyIds)) {
            return $this->emptyStats();
        }
        
        // Query única para buscar todas as estatísticas
        $stats = DB::table('property_evaluations')
            ->whereIn('property_id', $propertyIds)
            ->select([
                DB::raw('COUNT(*) as total_evaluations'),
                DB::raw('COUNT(DISTINCT property_id) as total_properties'),
                DB::raw('AVG(valuation) as avg_valuation'),
                DB::raw('CASE 
                    WHEN property_type = "urbana" AND urban_subtype = "residencial" THEN "Urbanas - Residenciais"
                    WHEN property_type = "urbana" AND urban_subtype = "comercial" THEN "Urbanas - Comerciais"
                    WHEN property_type = "urbana" AND urban_subtype = "misto" THEN "Urbanas - Misto"
                    WHEN property_type = "rural" AND (urban_subtype IS NULL OR urban_subtype = "residencial") THEN "Rurais - Residenciais"
                    WHEN property_type = "rural" AND urban_subtype = "comercial" THEN "Rurais - Comerciais"
                    WHEN property_type = "rural" AND urban_subtype = "misto" THEN "Rurais - Misto"
                    WHEN property_type = "industrial" THEN "Industriais"
                    ELSE "Outros"
                END as type')
            ])
            ->groupBy('type')
            ->get();
        
        // Calcular crescimento mensal
        $growth = $this->calculateMonthlyGrowth($propertyIds);
        
        return [
            'totalProperties' => $stats->sum('total_properties'),
            'totalEvaluations' => $stats->sum('total_evaluations'),
            'averageValuation' => round($stats->avg('avg_valuation') ?? 0, 2),
            'lastMonthGrowth' => $growth,
            'propertiesByType' => $stats->pluck('total_properties', 'type')->toArray(),
        ];
    }
    
    /**
     * Calcula crescimento mensal
     */
    private function calculateMonthlyGrowth(array $propertyIds)
    {
        $months = PropertyEvaluation::query()
            ->whereIn('property_id', $propertyIds)
            ->select([
                DB::raw('AVG(valuation) as avg_val'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year')
            ])
            ->where('created_at', '>=', now()->subMonths(2))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(2)
            ->get();
        
        if ($months->count() < 2 || !$months[1]->avg_val) {
            return 0;
        }
        
        return round((($months[0]->avg_val - $months[1]->avg_val) / $months[1]->avg_val) * 100, 2);
    }

    /**
     * Retorna IDs das propriedades do proprietário
     */
    private function getOwnerPropertyIds(int $userId): array
    {
        return Property::whereHas('owners', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id')->toArray();
    }
    
    /**
     * Retorna estatísticas vazias
     */
    private function emptyStats(): array
    {
        return [
            'totalProperties' => 0,
            'totalEvaluations' => 0,
            'averageValuation' => 0,
            'lastMonthGrowth' => 0,
            'propertiesByType' => []
        ];
    }

    /**
     * Carrega usuário com perfis se necessário
     */
    private function loadUserWithProfiles($user)
    {
        if (!($user instanceof \App\Models\User)) {
            return \App\Models\User::with('profiles')->findOrFail($user->id);
        }
        
        if (!$user->relationLoaded('profiles')) {
            $user->load('profiles');
        }
        
        return $user;
    }
    
    /**
     * Formata dados do usuário para response
     */
    private function formatUserData($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profiles' => $user->profiles->pluck('slug')->toArray(),
            'hasMultipleProfiles' => $user->profiles->count() > 1,
        ];
    }

    /**
     * Buscar propriedades de um cliente específico
     */
    public function getClientProperties(Request $request, int $clientId)
    {
        $user = $this->loadUserWithProfiles(Auth::user());
        
        // Verificar perfil
        if (!$user->hasProfile('prestador')) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }
        
        // Verificar autorização com cache
        $cacheKey = "auth_{$user->id}_{$clientId}";
        $hasPermission = Cache::remember($cacheKey, 60, function () use ($user, $clientId) {
            return Authorization::where('service_provider_id', $user->id)
                ->where('owner_id', $clientId)
                ->where(function($query) {
                    $query->where('can_view_documents', true)
                          ->orWhere('can_create_properties', true);
                })
                ->exists();
        });
        
        if (!$hasPermission) {
            return response()->json(['error' => 'Sem permissão para este cliente'], 403);
        }
        
        // Buscar propriedades com eager loading otimizado
        $properties = Property::whereHas('owners', function ($query) use ($clientId) {
            $query->where('user_id', $clientId);
        })
        ->with([
            'owners:id,name,email',
            'evaluations' => function($query) {
                $query->select('id', 'property_id', 'valuation', 'created_at')
                      ->latest()
                      ->limit(1);
            }
        ])
        ->get();
        
        return response()->json($properties);
    }
    
    /**
     * Limpar cache do usuário
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();
        
        Cache::forget("dashboard_owner_{$user->id}");
        Cache::forget("dashboard_provider_{$user->id}");
        Cache::forget("dashboard_dual_{$user->id}");
        
        return response()->json(['message' => 'Cache limpo com sucesso']);
    }

    /**
     * API endpoints (mantidos para compatibilidade)
     */
    public function getValuationDataApi()
    {
        $user = $this->loadUserWithProfiles(Auth::user());
        $propertyIds = $this->getOwnerPropertyIds($user->id);
        
        return response()->json($this->getOwnerValuationData($propertyIds));
    }

    public function getDashboardStatsApi()
    {
        $user = $this->loadUserWithProfiles(Auth::user());
        $propertyIds = $this->getOwnerPropertyIds($user->id);
        
        return response()->json($this->getOwnerStats($propertyIds));
    }
}