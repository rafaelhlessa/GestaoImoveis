<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Animais da Propriedade
        </h3>

        <!-- Formulário para adicionar animal -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4">
            <h4 class="text-md font-medium text-gray-700 mb-3">Adicionar Animal</h4>
            
            <!-- Primeira linha -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select v-model="newAnimal.animal_type" 
                            @change="updateCategories"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option v-for="(label, value) in animalTypes" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select v-model="newAnimal.animal_category" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option v-for="category in availableCategories" :key="category" :value="category">
                            {{ category }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                    <input type="number" 
                           v-model.number="newAnimal.quantity" 
                           @input="calculateTotal"
                           min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Segunda linha -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Unitário (R$)</label>
                    <input type="number" 
                           v-model.number="newAnimal.unit_price" 
                           @input="calculateTotal"
                           step="0.01"
                           min="0"
                           placeholder="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total (R$)</label>
                    <input type="text" 
                           :value="formatCurrency(newAnimal.total_price || 0)"
                           readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>

                <div class="flex items-end">
                    <button @click="addAnimal" 
                            :disabled="!canAddAnimal"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Adicionar
                    </button>
                </div>
            </div>

            <!-- Observações em linha separada -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea v-model="newAnimal.observations" 
                          placeholder="Observações sobre o animal (opcional)"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                          rows="2"></textarea>
            </div>
        </div>

        <!-- Lista de animais -->
        <div v-if="animals.length > 0">
            <h4 class="text-md font-medium text-gray-700 mb-3">Animais Cadastrados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Unit.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(animal, index) in animals" :key="index">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ animalTypes[animal.animal_type] || animal.animal_type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ animal.animal_category }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ animal.quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatCurrency(animal.unit_price) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ formatCurrency(animal.total_price) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button @click="removeAnimal(index)" 
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
                            <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-900">
                                Total Geral:
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                {{ formatCurrency(totalAnimalsValue) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div v-else class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            <p>Nenhum animal cadastrado</p>
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
const animals = ref([...props.modelValue])
const newAnimal = ref({
    animal_type: '',
    animal_category: '',
    quantity: 1,
    unit_price: 0,
    total_price: 0,
    observations: ''
})

// Tipos de animais
const animalTypes = {
    'bovino': 'Bovino',
    'suino': 'Suíno',
    'ovino': 'Ovino',
    'caprino': 'Caprino',
    'equino': 'Equino',
    'muares': 'Muares',
    'aves': 'Aves',
    'outros': 'Outros'
}

// Categorias por tipo
const animalCategories = {
    'bovino': ['bezerro', 'bezerha', 'novilho', 'novilha', 'vaca', 'touro', 'boi'],
    'suino': ['leitão', 'leitoa', 'porco', 'porca', 'cachaço'],
    'ovino': ['cordeiro', 'cordeira', 'carneiro', 'ovelha'],
    'caprino': ['cabrito', 'cabra', 'bode'],
    'equino': ['potro', 'potra', 'égua', 'garanhão'],
    'muares': ['burro', 'mula'],
    'aves': ['frango', 'galinha', 'galo', 'pato', 'ganso'],
    'outros': ['outros']
}

// Categorias disponíveis baseadas no tipo selecionado
const availableCategories = computed(() => {
    return animalCategories[newAnimal.value.animal_type] || []
})

// Verificar se pode adicionar animal
const canAddAnimal = computed(() => {
    return newAnimal.value.animal_type && 
           newAnimal.value.quantity > 0 && 
           newAnimal.value.unit_price >= 0
})

// Total dos animais
const totalAnimalsValue = computed(() => {
    return animals.value.reduce((total, animal) => total + (animal.total_price || 0), 0)
})

// Funções
const updateCategories = () => {
    newAnimal.value.animal_category = ''
}

const calculateTotal = () => {
    newAnimal.value.total_price = (newAnimal.value.quantity || 0) * (newAnimal.value.unit_price || 0)
}

const addAnimal = () => {
    if (canAddAnimal.value) {
        animals.value.push({ ...newAnimal.value })
        
        // Reset form
        newAnimal.value = {
            animal_type: '',
            animal_category: '',
            quantity: 1,
            unit_price: 0,
            total_price: 0,
            observations: ''
        }
    }
}

const removeAnimal = (index) => {
    animals.value.splice(index, 1)
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0)
}

// Watchers
watch(animals, (newAnimals) => {
    emit('update:modelValue', newAnimals)
}, { deep: true })

watch(() => props.modelValue, (newValue) => {
    animals.value = [...newValue]
}, { deep: true })
</script>
