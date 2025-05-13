<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyEvaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class PropertyEvaluationController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(PropertyEvaluation::class, 'evaluation');
    }

    // GET /properties/{property}/evaluations
    public function index(Property $property)
    {
        $this->authorize('view', $property);
        $evaluations = PropertyEvaluation::with('user')
            ->where('property_id', $property->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Evaluations/Index', compact('property', 'evaluations'));
    }

    // GET /properties/{property}/evaluations/create
    public function create(Property $property)
    {
        $this->authorize('create', PropertyEvaluation::class);
        

        return Inertia::render('Evaluations/Create', compact('property'));
    }

    // POST /properties/{property}/evaluations
    public function store(Request $request, Property $property)
    {
        $this->authorize('create', PropertyEvaluation::class);
    

        $evaluation = PropertyEvaluation::create([
            'property_id' => $property->id,
            'user_id'     => auth()->id(),
            'comments'    => $data['comments'] ?? null,
            'valuation'       => 0,
        ]);

        $totalScore = 0;
        $totalWeight = 0;
    

        if ($totalWeight > 0) {
            $evaluation->update(['score' => round($totalScore / $totalWeight, 2)]);
        }

        return redirect()->route('properties.evaluations.index', $property)
                         ->with('success', 'Avaliação registrada.');
    }

    // GET /properties/{property}/evaluations/{evaluation}
    public function show(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('view', $evaluation);
        $evaluation->load(['user', 'items.criterion']);

        return Inertia::render('Evaluations/Show', compact('property', 'evaluation'));
    }

    // GET /properties/{property}/evaluations/{evaluation}/edit
    public function edit(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('update', $evaluation);
        $criteria = EvaluationCriterion::orderBy('name')->get();
        $evaluation->load('items');

        return Inertia::render('Evaluations/Edit', compact('property', 'evaluation', 'criteria'));
    }

    // PUT /properties/{property}/evaluations/{evaluation}
    public function update(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('update', $evaluation);
        $data = $request->validate([
            'comments'             => 'nullable|string',
            'notes'                => 'required|array',
            'notes.*.criterion_id' => 'required|exists:evaluation_criteria,id',
            'notes.*.note'         => 'required|numeric|min:0|max:10',
            'notes.*.observation'  => 'nullable|string',
        ]);

        $evaluation->update(['comments' => $data['comments']]);
        $evaluation->items()->delete();

        $totalScore = 0;
        $totalWeight = 0;
        foreach ($data['notes'] as $item) {
            $criterion = EvaluationCriterion::find($item['criterion_id']);
            $weight    = $criterion->weight;
            $note      = $item['note'];

            $evaluation->items()->create([
                'criterion_id' => $criterion->id,
                'note'         => $note,
                'observation'  => $item['observation'] ?? null,
            ]);

            $totalScore  += $note * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            $evaluation->update(['score' => round($totalScore / $totalWeight, 2)]);
        }

        return redirect()->route('properties.evaluations.show', [$property, $evaluation])
                         ->with('success', 'Avaliação atualizada.');
    }

    // DELETE /properties/{property}/evaluations/{evaluation}
    public function destroy(Property $property, PropertyEvaluation $evaluation)
    {
        $this->authorize('delete', $evaluation);
        $evaluation->delete();

        return redirect()->route('properties.evaluations.index', $property)
                         ->with('success', 'Avaliação removida.');
    }
}
