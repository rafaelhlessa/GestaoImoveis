<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        if($user->profile_id === 2 || $user->profile_id === 3) {
            $serviceProviderId = auth()->user()->id;
            
            $serviceProviders = Authorization::where('service_provider_id', $serviceProviderId)
                ->with('owner')
                ->get();
            
            $owners = [];
            foreach ($serviceProviders as $serviceProvider) {
                if ($serviceProvider->can_create_properties || $serviceProvider->can_view_documents) {
                    $owners[] = $serviceProvider->owner;
                }
            }

            return Inertia::render('DashboardService', ['serviceProviders' => $owners]);
            
        } else {
            return Inertia::render('Dashboard');
        }
        
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
