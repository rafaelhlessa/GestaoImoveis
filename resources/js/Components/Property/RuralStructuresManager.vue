<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Estruturas Físicas da Propriedade Rural</h3>
    
    <!-- Formulário para adicionar nova estrutura -->
    <div class="border-b border-gray-200 pb-6 mb-6">
      <h4 class="text-md font-medium text-gray-700 mb-3">Adicionar Nova Estrutura</h4>
      <form @submit.prevent="addStructure" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Tipo de Estrutura *
          </label>
          <select
            v-model="newStructure.type"
            required
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">Selecione o tipo</option>
            <option v-for="type in structureTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Quantidade *
          </label>
          <input
            type="number"
            v-model.number="newStructure.quantity"
            min="1"
            required
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Área Total (m²)
          </label>
          <input
            type="number"
            v-model.number="newStructure.area"
            min="0"
            step="0.01"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Condição/Estado
          </label>
          <select
            v-model="newStructure.condition"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">Selecione a condição</option>
            <option v-for="condition in structureConditions" :key="condition.value" :value="condition.value">
              {{ condition.label }}
            </option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Descrição
          </label>
          <input
            type="text"
            v-model="newStructure.description"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Descrição da estrutura"
          >
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Especificidades
          </label>
          <textarea
            v-model="newStructure.specifications"
            rows="2"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Especificidades, materiais, observações..."
          ></textarea>
        </div>

        <div class="md:col-span-4 flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Adicionar Estrutura
          </button>
        </div>
      </form>
    </div>

    <!-- Lista de estruturas -->
    <div v-if="structures.length > 0">
      <h4 class="text-md font-medium text-gray-700 mb-3">Estruturas Cadastradas</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tipo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Quantidade
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Área (m²)
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Condição
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Descrição
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(structure, index) in structures" :key="index">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ getStructureTypeLabel(structure.type) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ structure.quantity }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ structure.area ? structure.area.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ getStructureConditionLabel(structure.condition) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                {{ structure.description || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="removeStructure(index)"
                  class="text-red-600 hover:text-red-900"
                >
                  <TrashIcon class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Resumo das estruturas -->
      <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h5 class="text-sm font-medium text-gray-700 mb-2">Resumo das Estruturas</h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-gray-600">Total de Estruturas:</span>
            <span class="font-medium ml-2">{{ totalStructures }}</span>
          </div>
          <div>
            <span class="text-gray-600">Área Total:</span>
            <span class="font-medium ml-2">
              {{ totalArea.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }} m²
            </span>
          </div>
          <div>
            <span class="text-gray-600">Tipos Diferentes:</span>
            <span class="font-medium ml-2">{{ uniqueTypes }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensagem quando não há estruturas -->
    <div v-else class="text-center py-8">
      <div class="text-gray-400 mb-2">
        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m5 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-6 0v-4a1 1 0 011-1h2a1 1 0 011 1v4" />
        </svg>
      </div>
      <p class="text-gray-500">Nenhuma estrutura física cadastrada</p>
      <p class="text-sm text-gray-400">Adicione estruturas como residências, galpões, estábulos, etc.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue'])

const structures = ref([...props.modelValue])

const newStructure = ref({
  type: '',
  quantity: 1,
  area: null,
  condition: '',
  description: '',
  specifications: ''
})

const structureTypes = [
  { value: 'residencia', label: 'Residência' },
  { value: 'galpao', label: 'Galpão' },
  { value: 'estabulo', label: 'Estábulo' },
  { value: 'curral', label: 'Curral' },
  { value: 'cerca', label: 'Cerca/Cercado' },
  { value: 'pocilga', label: 'Pocilga' },
  { value: 'galinheiro', label: 'Galinheiro' },
  { value: 'deposito', label: 'Depósito' },
  { value: 'silo', label: 'Silo' },
  { value: 'casa_maquinas', label: 'Casa de Máquinas' },
  { value: 'escritorio', label: 'Escritório' },
  { value: 'piscina', label: 'Piscina' },
  { value: 'reservatorio', label: 'Reservatório' },
  { value: 'poco', label: 'Poço' },
  { value: 'casa_caseiro', label: 'Casa do Caseiro' },
  { value: 'capela', label: 'Capela' },
  { value: 'outros', label: 'Outros' }
]

const structureConditions = [
  { value: 'excelente', label: 'Excelente' },
  { value: 'bom', label: 'Bom' },
  { value: 'regular', label: 'Regular' },
  { value: 'ruim', label: 'Ruim' },
  { value: 'pessimo', label: 'Péssimo' },
  { value: 'em_construcao', label: 'Em Construção' },
  { value: 'abandonado', label: 'Abandonado' }
]

const totalStructures = computed(() => {
  return structures.value.reduce((total, structure) => total + structure.quantity, 0)
})

const totalArea = computed(() => {
  return structures.value.reduce((total, structure) => {
    return total + (structure.area || 0) * structure.quantity
  }, 0)
})

const uniqueTypes = computed(() => {
  const types = new Set(structures.value.map(structure => structure.type))
  return types.size
})

const addStructure = () => {
  if (newStructure.value.type && newStructure.value.quantity > 0) {
    structures.value.push({ ...newStructure.value })
    
    // Reset form
    newStructure.value = {
      type: '',
      quantity: 1,
      area: null,
      condition: '',
      description: '',
      specifications: ''
    }
  }
}

const removeStructure = (index) => {
  structures.value.splice(index, 1)
}

const getStructureTypeLabel = (type) => {
  const structureType = structureTypes.find(t => t.value === type)
  return structureType ? structureType.label : type
}

const getStructureConditionLabel = (condition) => {
  const structureCondition = structureConditions.find(c => c.value === condition)
  return structureCondition ? structureCondition.label : condition || '-'
}

// Watch for changes and emit to parent
watch(
  structures,
  (newValue) => {
    emit('update:modelValue', newValue)
  },
  { deep: true }
)

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newValue) => {
    structures.value = [...newValue]
  },
  { deep: true }
)
</script>
