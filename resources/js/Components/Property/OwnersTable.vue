<template>
  <div class="mt-6 mb-12 grid grid-cols-1 gap-4">
    <!-- Botão Adicionar -->
    <div class="flex justify-start">
      <button 
        @click="$emit('add-owner')"
        type="button"
        class="rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-gray-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        Adicionar Proprietário
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block ml-2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
        </svg>
      </button>
    </div>

    <!-- Resumo dos Percentuais -->
    <div v-if="owners.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
            <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
          </svg>
          <span class="text-sm font-medium text-blue-900">
            Total de Propriedade: {{ totalPercentage.toFixed(2) }}%
          </span>
        </div>
        <div :class="[
          'px-2 py-1 rounded-full text-xs font-medium',
          totalPercentage === 100 
            ? 'bg-green-100 text-green-800' 
            : totalPercentage > 100 
              ? 'bg-red-100 text-red-800'
              : 'bg-yellow-100 text-yellow-800'
        ]">
          {{ 
            totalPercentage === 100 
              ? 'Completo' 
              : totalPercentage > 100 
                ? 'Excedeu 100%'
                : 'Incompleto'
          }}
        </div>
      </div>
    </div>

    <!-- Tabela -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-6 py-3">Nome</th>
            <th class="px-6 py-3">CPF/CNPJ</th>
            <th class="px-6 py-3">Percentual</th>
            <th class="px-6 py-3">Tipo de Propriedade</th>
            <th class="px-6 py-3">Notas</th>
            <th class="px-6 py-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="(owner, index) in owners" 
            :key="getOwnerId(owner, index)"
            class="bg-white border-b dark:bg-gray-700 dark:border-gray-500"
          >
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              {{ getOwnerName(owner) }}
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              {{ applyCpfCnpjMask(getOwnerCpf(owner)) }}
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              <span class="font-medium">{{ getOwnerPercentage(owner) }}%</span>
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                {{ getOwnershipTypeName(owner) }}
              </span>
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              <div class="max-w-xs truncate" :title="owner.observations">
                {{ owner.observations || '-' }}
              </div>
            </td>
            <td class="px-6 py-4">
              <button 
                type="button" 
                @click="$emit('remove-owner', index)"
                class="bg-red-500 border border-gray-600 rounded p-1 text-sm font-semibold text-white hover:bg-red-700 transition-colors"
              >
                Excluir
              </button>
            </td>
          </tr>
          <tr v-if="owners.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
              <div class="flex flex-col items-center">
                <svg class="h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Nenhum proprietário adicionado</span>
                <span class="text-xs text-gray-400 mt-1">Clique em "Adicionar Proprietário" para começar</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  owners: {
    type: Array,
    default: () => []
  },
  typeOwners: {
    type: Array,
    default: () => []
  }
})

defineEmits(['add-owner', 'remove-owner'])

// Computed para calcular o percentual total
const totalPercentage = computed(() => {
  return props.owners.reduce((total, owner) => {
    return total + (parseFloat(getOwnerPercentage(owner)) || 0)
  }, 0)
})

// Utilitários para lidar com diferentes estruturas de dados
const getOwnerId = (owner, index) => {
  return owner.user?.id || owner.user_id || owner.id || index
}

const getOwnerName = (owner) => {
  return owner.user?.name || owner.name || 'Nome não informado'
}

const getOwnerCpf = (owner) => {
  return owner.user?.cpf_cnpj || owner.cpf_cnpj || ''
}

const getOwnerPercentage = (owner) => {
  // Verifica todas as possíveis propriedades de percentual
  return owner.percentage || owner.percent || owner.pivot?.percentage || 0
}

const getOwnershipTypeName = (owner) => {
  // Primeiro tenta pegar o nome diretamente do relacionamento
  if (owner.type_ownership?.name) {
    return owner.type_ownership.name
  }
  
  // Fallback para buscar o tipo nos typeOwners usando o ID
  const typeId = owner.type_ownership_id || owner.type_ownership?.id
  if (typeId && props.typeOwners.length > 0) {
    const type = props.typeOwners.find(t => t.id == typeId)
    return type?.name || 'Tipo não informado'
  }
  
  return 'Tipo não informado'
}

const applyCpfCnpjMask = (value) => {
  if (!value) return ''
  const numericValue = value.replace(/\D/g, '')

  if (numericValue.length <= 11) {
    return numericValue
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  } else {
    return numericValue
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
  }
}
</script>