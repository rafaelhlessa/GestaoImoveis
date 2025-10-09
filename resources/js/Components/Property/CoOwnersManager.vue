<template>
  <div :class="compact ? 'mt-0' : 'mt-6'">
    <h4 :class="compact ? 'text-sm font-medium text-gray-700 mb-2' : 'text-md font-medium text-gray-700 mb-3'">
      Co-proprietários Não Cadastrados
    </h4>
    <p :class="compact ? 'text-xs text-gray-600 mb-3' : 'text-sm text-gray-600 mb-4'">
      Adicione co-proprietários que não possuem cadastro no sistema. 
      O percentual total (proprietários + co-proprietários) não pode exceder 100%.
    </p>

    <!-- Formulário para adicionar co-proprietário -->
    <div :class="compact ? 'bg-white border border-gray-200 p-3 rounded-md mb-3' : 'bg-gray-50 p-4 rounded-lg mb-4'">
      <h5 :class="compact ? 'text-xs font-medium text-gray-700 mb-2' : 'text-sm font-medium text-gray-700 mb-3'">
        Adicionar Co-proprietário
      </h5>
      
      <!-- Primeira linha: Nome e CPF/CNPJ -->
      <div :class="compact ? 'grid grid-cols-1 gap-3 mb-3' : 'grid grid-cols-1 md:grid-cols-2 gap-6 mb-6'">
        <div>
          <label :class="compact ? 'block text-xs font-medium text-gray-700 mb-1' : 'block text-sm font-medium text-gray-700 mb-2'">
            Nome Completo *
          </label>
          <input
            type="text"
            v-model="newCoOwner.name"
            :class="compact ? 'w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent' : 'w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500'"
            placeholder="Nome do co-proprietário"
            required
          >
        </div>

        <div>
          <label :class="compact ? 'block text-xs font-medium text-gray-700 mb-1' : 'block text-sm font-medium text-gray-700 mb-2'">
            CPF/CNPJ *
          </label>
          <input
            type="text"
            v-model="newCoOwner.cpf_cnpj"
            @input="formatCpfCnpj"
            :placeholder="cpfCnpjPlaceholder"
            :class="compact ? 'w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent' : 'w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500'"
            required
          >
        </div>
      </div>

      <!-- Segunda linha: Tipo e Percentual -->
      <div :class="compact ? 'grid grid-cols-1 gap-3 mb-3' : 'grid grid-cols-1 md:grid-cols-2 gap-6 mb-6'">
        <div>
          <label :class="compact ? 'block text-xs font-medium text-gray-700 mb-1' : 'block text-sm font-medium text-gray-700 mb-2'">
            Tipo de Propriedade *
          </label>
          <select
            v-model="newCoOwner.type_ownership_id"
            :class="compact ? 'w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent' : 'w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500'"
            required
          >
            <option value="">Selecione o tipo</option>
            <option 
              v-for="type in typeOwners" 
              :key="type.id" 
              :value="type.id"
            >
              {{ type.name }}
            </option>
          </select>
        </div>

        <div>
          <label :class="compact ? 'block text-xs font-medium text-gray-700 mb-1' : 'block text-sm font-medium text-gray-700 mb-2'">
            Percentual (%) *
          </label>
          <input
            type="number"
            v-model.number="newCoOwner.percentage"
            min="0.01"
            :max="availablePercentage"
            step="0.01"
            :class="compact ? 'w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent' : 'w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500'"
            placeholder="0.00"
            required
          >
          <p class="text-xs text-gray-500 mt-1">
            Disponível: {{ availablePercentage.toFixed(2) }}%
          </p>
        </div>
      </div>

      <!-- Terceira linha: Observações e Botão -->
      <div :class="compact ? 'grid grid-cols-1 gap-3' : 'grid grid-cols-1 md:grid-cols-3 gap-6 items-end'">
        <div :class="compact ? '' : 'md:col-span-2'">
          <label :class="compact ? 'block text-xs font-medium text-gray-700 mb-1' : 'block text-sm font-medium text-gray-700 mb-2'">
            Observações
          </label>
          <textarea
            v-model="newCoOwner.observations"
            rows="2"
            :class="compact ? 'w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent' : 'w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500'"
            placeholder="Observações sobre o co-proprietário..."
          ></textarea>
        </div>
        
        <div>
          <button
            @click="addCoOwner"
            :disabled="!canAddCoOwner"
            :class="compact ? 'w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors' : 'w-full px-4 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium'"
          >
            Adicionar Co-proprietário
          </button>
        </div>
      </div>
    </div>

    <!-- Lista de co-proprietários -->
    <div v-if="coOwners.length > 0">
      <h5 class="text-sm font-medium text-gray-700 mb-3">Co-proprietários Adicionados</h5>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Nome
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                CPF/CNPJ
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Tipo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Percentual
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(coOwner, index) in coOwners" :key="index">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ coOwner.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatCpfCnpjDisplay(coOwner.cpf_cnpj) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ getTypeOwnershipName(coOwner.type_ownership_id) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ coOwner.percentage.toFixed(2) }}%
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="removeCoOwner(index)"
                  class="text-red-600 hover:text-red-900"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Resumo -->
      <div class="mt-4 bg-blue-50 p-3 rounded-lg">
        <div class="flex justify-between items-center text-sm">
          <span class="text-blue-700 font-medium">
            Total de Co-proprietários: {{ coOwners.length }}
          </span>
          <span class="text-blue-700 font-medium">
            Percentual Total: {{ totalCoOwnerPercentage.toFixed(2) }}%
          </span>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-6 text-gray-500">
      <p>Nenhum co-proprietário adicionado</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  typeOwners: {
    type: Array,
    default: () => []
  },
  usedPercentage: {
    type: Number,
    default: 0
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const coOwners = ref([...props.modelValue])

const newCoOwner = ref({
  name: '',
  cpf_cnpj: '',
  percentage: null,
  type_ownership_id: '',
  observations: ''
})

const cpfCnpjPlaceholder = ref('000.000.000-00 ou 00.000.000/0000-00')

// Computed
const totalCoOwnerPercentage = computed(() => {
  return coOwners.value.reduce((total, coOwner) => total + parseFloat(coOwner.percentage || 0), 0)
})

const availablePercentage = computed(() => {
  return 100 - props.usedPercentage - totalCoOwnerPercentage.value
})

const canAddCoOwner = computed(() => {
  const hasName = newCoOwner.value.name && newCoOwner.value.name.trim().length > 0
  const hasCpfCnpj = newCoOwner.value.cpf_cnpj && newCoOwner.value.cpf_cnpj.replace(/\D/g, '').length >= 11
  const hasPercentage = newCoOwner.value.percentage && newCoOwner.value.percentage > 0
  const hasType = newCoOwner.value.type_ownership_id && newCoOwner.value.type_ownership_id !== ''
  const percentageValid = newCoOwner.value.percentage <= availablePercentage.value
  
  return hasName && hasCpfCnpj && hasPercentage && hasType && percentageValid
})

// Methods
const formatCpfCnpj = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  
  if (value.length <= 11) {
    // CPF
    value = value.replace(/(\d{3})(\d)/, '$1.$2')
    value = value.replace(/(\d{3})(\d)/, '$1.$2')
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  } else {
    // CNPJ
    value = value.replace(/^(\d{2})(\d)/, '$1.$2')
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2')
    value = value.replace(/(\d{4})(\d)/, '$1-$2')
  }
  
  newCoOwner.value.cpf_cnpj = value
}

const formatCpfCnpjDisplay = (cpfCnpj) => {
  const numbers = cpfCnpj.replace(/\D/g, '')
  
  if (numbers.length === 11) {
    // CPF
    return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  } else if (numbers.length === 14) {
    // CNPJ  
    return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
  
  return cpfCnpj
}

const getTypeOwnershipName = (typeId) => {
  const type = props.typeOwners.find(t => t.id === typeId)
  return type ? type.name : ''
}

const addCoOwner = () => {
  if (canAddCoOwner.value) {
    // Remove formatação do CPF/CNPJ para salvar apenas números
    const cleanCpfCnpj = newCoOwner.value.cpf_cnpj.replace(/\D/g, '')
    
    coOwners.value.push({
      ...newCoOwner.value,
      cpf_cnpj: cleanCpfCnpj
    })
    
    // Reset form completamente
    Object.assign(newCoOwner.value, {
      name: '',
      cpf_cnpj: '',
      percentage: null,
      type_ownership_id: '',
      observations: ''
    })
    
    // Force reactivity update
    newCoOwner.value = { ...newCoOwner.value }
  }
}

const removeCoOwner = (index) => {
  coOwners.value.splice(index, 1)
}

// Watchers
watch(
  coOwners,
  (newValue) => {
    emit('update:modelValue', newValue)
  },
  { deep: true }
)

watch(
  () => props.modelValue,
  (newValue) => {
    coOwners.value = [...newValue]
  },
  { deep: true }
)
</script>
