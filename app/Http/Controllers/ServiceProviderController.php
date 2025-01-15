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
        // $serviceProviders = 
        // $serviceProviders = Authorization::with('owner')->get();
        $serviceProviderId = auth()->user()->id;
        $serviceProviders = Authorization::where('service_provider_id', $serviceProviderId)
            ->with('owner')
            ->get();
        
        return Inertia::render('Teste/Teste', ['serviceProviders' => $serviceProviders]);
        // return Inertia::render('ServiceProvider/ServiceProviderIndex', ['serviceProviders' => $serviceProviders]);
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
