<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            Maquinários da Propriedade
        </h3>

        <!-- Formulário para adicionar maquinário -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4">
            <h4 class="text-md font-medium text-gray-700 mb-3">Adicionar Maquinário</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select v-model="newMachinery.machinery_type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option v-for="(label, value) in machineryTypes" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                    <input type="text" 
                           v-model="newMachinery.brand" 
                           placeholder="Ex: John Deere"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                    <input type="text" 
                           v-model="newMachinery.model" 
                           placeholder="Ex: 5075E"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                    <input type="number" 
                           v-model.number="newMachinery.year" 
                           :min="1950"
                           :max="new Date().getFullYear() + 1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condição</label>
                    <select v-model="newMachinery.condition" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option v-for="(label, value) in conditions" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                    <input type="number" 
                           v-model.number="newMachinery.quantity" 
                           @input="calculateTotal"
                           min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Unitário (R$)</label>
                    <input type="number" 
                           v-model.number="newMachinery.unit_price" 
                           @input="calculateTotal"
                           step="0.01"
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total (R$)</label>
                    <input type="text" 
                           :value="formatCurrency(newMachinery.total_price || 0)"
                           readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especificações Técnicas</label>
                    <textarea v-model="newMachinery.specifications" 
                              placeholder="Ex: Potência, capacidade, características especiais..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                              rows="3"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea v-model="newMachinery.observations" 
                              placeholder="Observações gerais (opcional)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                              rows="3"></textarea>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button @click="addMachinery" 
                        :disabled="!canAddMachinery"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Adicionar Maquinário
                </button>
            </div>
        </div>

        <!-- Lista de maquinários -->
        <div v-if="machinery.length > 0">
            <h4 class="text-md font-medium text-gray-700 mb-3">Maquinários Cadastrados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marca/Modelo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Unit.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(item, index) in machinery" :key="index">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ machineryTypes[item.machinery_type] || item.machinery_type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ item.brand }} {{ item.model }}</div>
                                <div v-if="item.specifications" class="text-xs text-gray-500 truncate max-w-32" :title="item.specifications">
                                    {{ item.specifications }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ item.year || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs rounded-full"
                                      :class="getConditionClass(item.condition)">
                                    {{ conditions[item.condition] || item.condition }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ item.quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatCurrency(item.unit_price) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ formatCurrency(item.total_price) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button @click="removeMachinery(index)" 
                                        class="text-red-600 hover:text-red-900">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-6 py-3 text-right text-sm font-medium text-gray-900">
                                Total Geral:
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                {{ formatCurrency(totalMachineryValue) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div v-else class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <p>Nenhum maquinário cadastrado</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue'])

// Estado local
const machinery = ref([...props.modelValue])
const newMachinery = ref({
    machinery_type: '',
    brand: '',
    model: '',
    year: new Date().getFullYear(),
    quantity: 1,
    unit_price: 0,
    total_price: 0,
    condition: '',
    specifications: '',
    observations: ''
})

// Tipos de maquinários
const machineryTypes = {
    'trator': 'Trator',
    'colheitadeira': 'Colheitadeira',
    'plantadeira': 'Plantadeira',
    'pulverizador': 'Pulverizador',
    'arado': 'Arado',
    'grade': 'Grade',
    'roçadeira': 'Roçadeira',
    'distribuidora': 'Distribuidora de Adubo',
    'carreta': 'Carreta',
    'caminhao': 'Caminhão',
    'ordenhadeira': 'Ordenhadeira',
    'resfriador': 'Resfriador de Leite',
    'picadeira': 'Picadeira de Forragem',
    'enfardadeira': 'Enfardadeira',
    'outros': 'Outros'
}

// Condições do maquinário
const conditions = {
    'novo': 'Novo',
    'seminovo': 'Seminovo',
    'usado_bom': 'Usado - Bom Estado',
    'usado_regular': 'Usado - Estado Regular',
    'usado_ruim': 'Usado - Estado Ruim',
    'para_reforma': 'Para Reforma'
}

// Verificar se pode adicionar maquinário
const canAddMachinery = computed(() => {
    return newMachinery.value.machinery_type && 
           newMachinery.value.quantity > 0 && 
           newMachinery.value.unit_price >= 0
})

// Total dos maquinários
const totalMachineryValue = computed(() => {
    return machinery.value.reduce((total, item) => total + (item.total_price || 0), 0)
})

// Funções
const calculateTotal = () => {
    newMachinery.value.total_price = (newMachinery.value.quantity || 0) * (newMachinery.value.unit_price || 0)
}

const addMachinery = () => {
    if (canAddMachinery.value) {
        machinery.value.push({ ...newMachinery.value })
        
        // Reset form
        newMachinery.value = {
            machinery_type: '',
            brand: '',
            model: '',
            year: new Date().getFullYear(),
            quantity: 1,
            unit_price: 0,
            total_price: 0,
            condition: '',
            specifications: '',
            observations: ''
        }
    }
}

const removeMachinery = (index) => {
    machinery.value.splice(index, 1)
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0)
}

const getConditionClass = (condition) => {
    const classes = {
        'novo': 'bg-green-100 text-green-800',
        'seminovo': 'bg-blue-100 text-blue-800',
        'usado_bom': 'bg-yellow-100 text-yellow-800',
        'usado_regular': 'bg-orange-100 text-orange-800',
        'usado_ruim': 'bg-red-100 text-red-800',
        'para_reforma': 'bg-gray-100 text-gray-800'
    }
    return classes[condition] || 'bg-gray-100 text-gray-800'
}

// Watchers
watch(machinery, (newMachinery) => {
    emit('update:modelValue', newMachinery)
}, { deep: true })

watch(() => props.modelValue, (newValue) => {
    machinery.value = [...newValue]
}, { deep: true })
</script>
