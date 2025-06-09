<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PropertyEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::info('PropertyEvaluationRequest@authorize called', [
            'user_id' => Auth::id(),
            'request_data' => $this->all()
        ]);
        
        return true; // Autorização será feita no controller
    }

    public function rules(): array
    {
        Log::info('PropertyEvaluationRequest@rules called', [
            'property_type' => $this->property_type,
            'urban_subtype' => $this->urban_subtype,
            'all_data' => $this->all()
        ]);

        $rules = [
            'property_id' => 'nullable|exists:properties,id',
            'user_id' => 'nullable|exists:users,id',
            'appraiser' => 'required|string|max:255',
            'valuation' => 'required|numeric|min:0.01',
            'comments' => 'nullable|string|max:2000',
            'property_type' => 'required|integer',
            'observations' => 'nullable|string|max:2000',
        ];

        // Regras condicionais baseadas no tipo de propriedade
        if ($this->property_type === 1) {
            $rules['urban_subtype'] = 'required|in:residencial,comercial';
            
            if ($this->urban_subtype === 'residencial') {
                $rules = array_merge($rules, $this->residentialRules());
            } elseif ($this->urban_subtype === 'comercial') {
                $rules = array_merge($rules, $this->commercialRules());
            }
        } elseif ($this->property_type === 2) {
            $rules = array_merge($rules, $this->ruralRules());
        }

        Log::info('PropertyEvaluationRequest final rules', ['rules' => $rules]);

        return $rules;
    }

    private function residentialRules(): array
    {
        return [
            'rooms' => 'required|integer|min:1|max:50',
            'bedrooms' => 'required|integer|min:1|max:20',
            'bathrooms' => 'required|integer|min:1|max:20',
            'built_area' => 'required|numeric|min:1|max:99999.99',
            'total_area' => 'required|numeric|min:1|max:99999.99',
            'property_condition' => 'required|in:excelente,bom,regular,ruim,pessimo',
            'garage_spaces' => 'nullable|integer|min:0|max:20',
            'furniture_status' => 'nullable|in:mobiliado,semi_mobiliado,nao_mobiliado',
        ];
    }

    private function commercialRules(): array
    {
        return [
            'floors' => 'required|integer|min:1|max:50',
            'office_rooms' => 'required|integer|min:1|max:200',
            'parking_spaces' => 'nullable|integer|min:0|max:500',
            'total_area' => 'required|numeric|min:1|max:99999.99',
            'property_condition' => 'required|in:excelente,bom,regular,ruim,pessimo',
        ];
    }

    private function ruralRules(): array
    {
        return [
            'rural_total_area' => 'required|numeric|min:0.01|max:999999.99',
            'has_construction' => 'required|boolean',
            'construction_types' => 'nullable|array',
            'construction_types.*' => 'in:casa_sede,galpon,estabulo,celeiro,pocilga,galinheiro,outros',
            'has_farming' => 'required|boolean',
            'farming_types' => 'nullable|array',
            'farming_types.*' => 'in:soja,milho,feijao,arroz,trigo,cana_acucar,cafe,pastagem,fruticultura,outros',
            'water_source' => 'required|in:poco_artesiano,rio,nascente,rede_publica,cisterna,outros',
            'water_source_details' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            // Campos básicos
            'appraiser.required' => 'O nome do avaliador é obrigatório.',
            'appraiser.string' => 'O nome do avaliador deve ser um texto.',
            'appraiser.max' => 'O nome do avaliador não pode ter mais de 255 caracteres.',
            'valuation.required' => 'O valor da avaliação é obrigatório.',
            'valuation.numeric' => 'O valor da avaliação deve ser um número.',
            'valuation.min' => 'O valor da avaliação deve ser maior que zero.',
            'comments.max' => 'Os comentários não podem ter mais de 2000 caracteres.',
            
            // Tipo de propriedade
            'property_type.required' => 'O tipo de propriedade é obrigatório.',
            'property_type.in' => 'O tipo de propriedade deve ser urbana ou rural.',
            'urban_subtype.required' => 'O subtipo da propriedade urbana é obrigatório.',
            'urban_subtype.in' => 'O subtipo deve ser residencial ou comercial.',
            
            // Mensagens residenciais
            'rooms.required' => 'O número de cômodos é obrigatório.',
            'rooms.integer' => 'O número de cômodos deve ser um número inteiro.',
            'rooms.min' => 'O número de cômodos deve ser pelo menos 1.',
            'rooms.max' => 'O número de cômodos não pode ser maior que 50.',
            'bedrooms.required' => 'O número de dormitórios é obrigatório.',
            'bedrooms.integer' => 'O número de dormitórios deve ser um número inteiro.',
            'bedrooms.min' => 'O número de dormitórios deve ser pelo menos 1.',
            'bedrooms.max' => 'O número de dormitórios não pode ser maior que 20.',
            'bathrooms.required' => 'O número de banheiros é obrigatório.',
            'bathrooms.integer' => 'O número de banheiros deve ser um número inteiro.',
            'bathrooms.min' => 'O número de banheiros deve ser pelo menos 1.',
            'bathrooms.max' => 'O número de banheiros não pode ser maior que 20.',
            'built_area.required' => 'A área construída é obrigatória.',
            'built_area.numeric' => 'A área construída deve ser um número.',
            'built_area.min' => 'A área construída deve ser maior que zero.',
            'total_area.required' => 'A área total é obrigatória.',
            'total_area.numeric' => 'A área total deve ser um número.',
            'total_area.min' => 'A área total deve ser maior que zero.',
            'property_condition.required' => 'As condições do imóvel são obrigatórias.',
            'property_condition.in' => 'Selecione uma condição válida para o imóvel.',
            'garage_spaces.integer' => 'O número de vagas de garagem deve ser um número inteiro.',
            'garage_spaces.min' => 'O número de vagas de garagem não pode ser negativo.',
            'garage_spaces.max' => 'O número de vagas de garagem não pode ser maior que 20.',
            
            // Mensagens comerciais
            'floors.required' => 'O número de pavimentos é obrigatório.',
            'floors.integer' => 'O número de pavimentos deve ser um número inteiro.',
            'floors.min' => 'O número de pavimentos deve ser pelo menos 1.',
            'floors.max' => 'O número de pavimentos não pode ser maior que 50.',
            'office_rooms.required' => 'O número de salas é obrigatório.',
            'office_rooms.integer' => 'O número de salas deve ser um número inteiro.',
            'office_rooms.min' => 'O número de salas deve ser pelo menos 1.',
            'office_rooms.max' => 'O número de salas não pode ser maior que 200.',
            'parking_spaces.integer' => 'O número de vagas de estacionamento deve ser um número inteiro.',
            'parking_spaces.min' => 'O número de vagas de estacionamento não pode ser negativo.',
            'parking_spaces.max' => 'O número de vagas de estacionamento não pode ser maior que 500.',
            
            // Mensagens rurais
            'rural_total_area.required' => 'A área total rural é obrigatória.',
            'rural_total_area.numeric' => 'A área total rural deve ser um número.',
            'rural_total_area.min' => 'A área total rural deve ser maior que zero.',
            'rural_total_area.max' => 'A área total rural é muito grande.',
            'has_construction.required' => 'Informe se possui construção.',
            'has_construction.boolean' => 'O campo possui construção deve ser verdadeiro ou falso.',
            'construction_types.array' => 'Os tipos de construção devem ser uma lista.',
            'construction_types.*.in' => 'Tipo de construção inválido selecionado.',
            'has_farming.required' => 'Informe se possui lavoura.',
            'has_farming.boolean' => 'O campo possui lavoura deve ser verdadeiro ou falso.',
            'farming_types.array' => 'Os tipos de lavoura devem ser uma lista.',
            'farming_types.*.in' => 'Tipo de lavoura inválido selecionado.',
            'water_source.required' => 'A fonte de água é obrigatória.',
            'water_source.in' => 'Selecione uma fonte de água válida.',
            'water_source_details.string' => 'Os detalhes da fonte de água devem ser um texto.',
            'water_source_details.max' => 'Os detalhes da fonte de água não podem ter mais de 500 caracteres.',
            'observations.string' => 'As observações devem ser um texto.',
            'observations.max' => 'As observações não podem ter mais de 2000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        Log::info('PropertyEvaluationRequest@prepareForValidation', [
            'before_preparation' => $this->all()
        ]);

        // Garantir que arrays vazios sejam null se não houver construção/lavoura
        if ($this->has_construction === false || $this->has_construction === 'false' || $this->has_construction === '0') {
            $this->merge(['construction_types' => null]);
        }

        if ($this->has_farming === false || $this->has_farming === 'false' || $this->has_farming === '0') {
            $this->merge(['farming_types' => null]);
        }

        // Definir user_id como o usuário autenticado se não informado
        if (!$this->user_id) {
            $this->merge(['user_id' => Auth::id()]);
        }

        // Converter valores booleanos se necessário
        if ($this->has('has_construction')) {
            if (is_string($this->has_construction)) {
                $this->merge(['has_construction' => filter_var($this->has_construction, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }

        if ($this->has('has_farming')) {
            if (is_string($this->has_farming)) {
                $this->merge(['has_farming' => filter_var($this->has_farming, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }

        // Converter números para garantir tipos corretos
        $numericFields = [
            'valuation', 'rooms', 'bedrooms', 'bathrooms', 'built_area', 
            'total_area', 'garage_spaces', 'floors', 'office_rooms', 
            'parking_spaces', 'rural_total_area'
        ];

        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->$field !== null && $this->$field !== '') {
                $value = $this->$field;
                if (is_string($value)) {
                    // Converter vírgula para ponto para números decimais
                    $value = str_replace(',', '.', $value);
                    
                    if (in_array($field, ['built_area', 'total_area', 'rural_total_area', 'valuation'])) {
                        $this->merge([$field => floatval($value)]);
                    } else {
                        $this->merge([$field => intval($value)]);
                    }
                }
            }
        }

        Log::info('PropertyEvaluationRequest@prepareForValidation - after', [
            'after_preparation' => $this->all()
        ]);
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::error('PropertyEvaluationRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all()
        ]);

        parent::failedValidation($validator);
    }
}