<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // ou policy admin
    }

    // GET /plans/admin
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('price_monthly')->get();
        return Inertia::render('Plans/Index', compact('plans'));
    }

    // GET /plans/admin/create
    public function create()
    {
        return Inertia::render('Plans/Create');
    }

    // POST /plans/admin
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|unique:subscription_plans,slug',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'nullable|numeric|min:0',
            'features'      => 'nullable|array',
            'features.*'    => 'string',
        ]);
        $data['features'] = array_values($data['features'] ?? []);

        SubscriptionPlan::create($data);

        return redirect()->route('plans.admin.index')
                         ->with('success', 'Plano criado.');
    }

    // GET /plans/admin/{plan}/edit
    public function edit(SubscriptionPlan $plan)
    {
        return Inertia::render('Plans/Edit', compact('plan'));
    }

    // PUT /plans/admin/{plan}
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => "required|string|max:100|unique:subscription_plans,slug,{$plan->id}",
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'nullable|numeric|min:0',
            'features'      => 'nullable|array',
            'features.*'    => 'string',
        ]);
        $data['features'] = array_values($data['features'] ?? []);

        $plan->update($data);

        return redirect()->route('plans.admin.index')
                         ->with('success', 'Plano atualizado.');
    }

    // DELETE /plans/admin/{plan}
    public function destroy(SubscriptionPlan $plan)
    {
        $plan->delete();
        return redirect()->route('plans.admin.index')
                         ->with('success', 'Plano removido.');
    }
}
