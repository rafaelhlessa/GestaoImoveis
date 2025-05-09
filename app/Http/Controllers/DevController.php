<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DevController extends Controller
{
    /**
     * Exibe o formulário dinâmico.
     */
    public function index()
    {
        // Definição genérica dos campos que você quer no form
        $fields = [
            [
                'name'            => 'title',
                'label'           => 'Título',
                'type'            => 'text',
                'props'           => ['placeholder' => 'Digite o título...'],
                'validationRule'  => 'required|string|max:255',
                'errorMessage'    => 'O título é obrigatório e deve ter até 255 caracteres.',
            ],
            [
                'name'            => 'area',
                'label'           => 'Área (m²)',
                'type'            => 'number',
                'props'           => ['min' => 0, 'step' => '0.01'],
                'validationRule'  => 'required|numeric|min:0',
                'errorMessage'    => 'A área deve ser um número maior ou igual a zero.',
            ],
            [
                'name'    => 'type_property',
                'label'   => 'Tipo de Propriedade',
                'type'    => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Urbana'],
                    ['value' => 2, 'label' => 'Rural'],
                ],
                'props'   => ['placeholder' => 'Selecione...'],
                'validationRule' => 'required|in:1,2',
                'errorMessage'   => 'Selecione o tipo de propriedade.',
            ],
            [
                'name'           => 'is_active',
                'label'          => 'Propriedade Ativa?',
                'type'           => 'checkbox',
                'validationRule' => 'boolean',
            ],
            // Adicione quantos campos quiser...
        ];

        // Estado inicial do formulário
        $initialModel = [
            'title'         => '',
            'area'          => null,
            'type_property' => 1,
            'is_active'     => false,
        ];

        return Inertia::render('Admin/Dev', [
            'fields'       => $fields,
            'initialModel' => $initialModel,
            'submitLabel'  => 'Salvar Propriedade',
        ]);
    }

    /**
     * Recebe o submit do formulário e processa de forma genérica.
     */
    public function store(Request $request)
    {
        // Monta regras de validação dinamicamente a partir dos fields
        // (você pode reaproveitar exatamente o array $fields que passou para a view
        //  ou declará-lo num config/shared file)
        $fields = collect([
            ['name'=>'title','validationRule'=>'required|string|max:255'],
            ['name'=>'area','validationRule'=>'required|numeric|min:0'],
            ['name'=>'type_property','validationRule'=>'required|in:1,2'],
            ['name'=>'is_active','validationRule'=>'boolean'],
        ]);

        $rules = $fields->pluck('validationRule','name')->toArray();

        $data = $request->validate($rules);

        // Aqui você pode criar um registro genérico,
        // ou despachar um job, ou salvar em tabelas específicas.
        // Exemplo simples:
        \App\Models\Property::create($data);

        return redirect()->route('admin.dev.index')
                         ->with('success', 'Propriedade salva com sucesso.');
    }
}
