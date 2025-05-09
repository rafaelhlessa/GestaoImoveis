<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCriterion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvaluationCriterionController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
        // ou middleware('can:manage-criteria') para perfil admin
    }

    /**
     * GET /criteria
     * Lista os critérios de avaliação (para administração via Vue/Inertia)
     */
    public function index()
    {
        $criteria = EvaluationCriterion::orderBy('name')->get();
        return Inertia::render('Criteria/Index', [
            'criteria' => $criteria,
        ]);
    }

    /**
     * GET /criteria/create
     */
    public function create()
    {
        return Inertia::render('Criteria/Create');
    }

    /**
     * POST /criteria
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
        ]);

        EvaluationCriterion::create($data);

        return redirect()->route('criteria.index')
                         ->with('success', 'Critério adicionado.');
    }

    /**
     * GET /criteria/{criterion}/edit
     */
    public function edit(EvaluationCriterion $criterion)
    {
        return Inertia::render('Criteria/Edit', [
            'criterion' => $criterion,
        ]);
    }

    /**
     * PUT /criteria/{criterion}
     */
    public function update(Request $request, EvaluationCriterion $criterion)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
        ]);

        $criterion->update($data);

        return redirect()->route('criteria.index')
                         ->with('success', 'Critério atualizado.');
    }

    /**
     * DELETE /criteria/{criterion}
     */
    public function destroy(EvaluationCriterion $criterion)
    {
        $criterion->delete();

        return redirect()->route('criteria.index')
                         ->with('success', 'Critério removido.');
    }
}
