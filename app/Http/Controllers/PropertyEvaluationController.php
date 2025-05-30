<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class PropertyEvaluationController extends BaseController
{
    use AuthorizesRequests; // Importante: use o trait AuthorizesRequests

    public function __construct()
    {
        $this->middleware('auth');
    }

    // GET /properties/{property}/evaluations
    public function index(Property $property)
    {
        $this->authorize('view', $property);
        
        $evaluations = PropertyEvaluation::with('user')
            ->where('property_id', $property->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Properties/PropertyEvaluationList', compact('property', 'evaluations'));
    }

    // GET /properties/{property}/evaluations/create
    public function create(Property $property)
    {
        $this->authorize('view', $property);
        $this->authorize('create', PropertyEvaluation::class);

        return Inertia::render('Properties/PropertyEvaluationForm', compact('property'));
    }

    // POST /properties/{property}/evaluations
    public function store(Request $request)
    {
        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);
        $this->authorize('create', PropertyEvaluation::class);
        
        $user = Auth::user();
        // Validação dos dados do formulário
        $validated = $request->validate([
            'avaliador' => 'required|string',
            'valor' => 'required|numeric',
            'observações' => 'nullable|string',
            'property_id' => 'required|exists:properties,id',
        ]);
        
        $evaluation = PropertyEvaluation::create([
            'property_id' => $validated['property_id'],
            'user_id'     => $user->id,
            'appraiser'   => $validated['avaliador'],
            'comments'    => $validated['observações'] ?? null,
            'valuation'   => $validated['valor'],
        ]);

        $totalScore = 0;
        $totalWeight = 0;
        
        // Seu código para cálculo de score, se necessário

        if ($totalWeight > 0) {
            $evaluation->update(['score' => round($totalScore / $totalWeight, 2)]);
        }

        return redirect()->route('properties.evaluations.index', $request->property_id)
                         ->with('success', 'Avaliação registrada.');
    }

    // GET /properties/{property}/evaluations/{evaluation}
    public function show(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('view', $property);
        $this->authorize('view', $evaluation);

        // Carregar os dados relacionados
        
        $evaluation->load(['user', 'activityUsers']);

        return Inertia::render('Evaluations/Show', compact('property', 'evaluation'));
    }

    // GET /properties/{property}/evaluations/{evaluation}/edit
    public function edit(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('update', $property);
        $this->authorize('update', $evaluation);
        
        $evaluation->load('items');

        return Inertia::render('Evaluations/Edit', compact('property', 'evaluation'));
    }

    // PUT /properties/{property}/evaluations/{evaluation}
    public function update(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('update', $property);
        $this->authorize('update', $evaluation);
        
        $evaluation->update(['comments' => $request->input('comments')]);
        $evaluation->items()->delete();

        $totalScore = 0;
        $totalWeight = 0;
        
        if ($totalWeight > 0) {
            $evaluation->update(['score' => round($totalScore / $totalWeight, 2)]);
        }

        return redirect()->route('properties.evaluations.show', [$property, $evaluation])
                         ->with('success', 'Avaliação atualizada.');
    }

    // DELETE /properties/{property}/evaluations/{evaluation}
    public function destroy(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('delete', $property);
        $this->authorize('delete', $evaluation);
        
        $evaluation->delete();

        return redirect()->route('properties.evaluations.index', $property)
                         ->with('success', 'Avaliação removida.');
    }
}