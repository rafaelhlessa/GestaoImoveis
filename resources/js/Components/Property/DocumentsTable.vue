<template>
  <div class="border-b border-gray-900/10 pb-12">
    <div class="flex justify-between items-start mb-6">
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          Documentação da Propriedade
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Inclua os documentos da propriedade.
        </p>
      </div>
      <button 
        type="button" 
        @click="$emit('add-document')" 
        class="rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        Adicionar Documento
      </button>
    </div>

    <!-- Documentos Obrigatórios -->
    <RequiredDocumentsInfo 
      v-if="propertyType" 
      :property-type="propertyType" 
    />

    <!-- Tabela de Documentos -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3">Nome do Documento</th>
            <th scope="col" class="px-6 py-3">Data de Vencimento</th>
            <th scope="col" class="px-6 py-3">Visível</th>
            <th scope="col" class="px-6 py-3">Arquivo</th>
            <th scope="col" class="px-6 py-3">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr 
            v-for="(document, index) in documents" 
            :key="document.id || index"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-gray-900">
              {{ document.name }}
            </td>
            <td class="px-6 py-4 text-gray-900">
              {{ formatDate(document.date) }}
            </td>
            <td class="px-6 py-4">
              <span 
                :class="document.show ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ document.show ? 'Sim' : 'Não' }}
              </span>
            </td>
            <td class="px-6 py-4 text-gray-900">
              <div class="flex items-center">
                <DocumentIcon class="h-4 w-4 text-gray-400 mr-2" />
                <span class="truncate max-w-xs" :title="document.file_name">
                  {{ document.file_name }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="flex space-x-2">
                <button 
                  v-if="document.id && mode === 'view'"
                  type="button"
                  @click="$emit('view-document', document.id)"
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-200 rounded hover:bg-blue-200"
                >
                  Ver
                </button>
                <button 
                  type="button" 
                  @click="$emit('remove-document', index)"
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded hover:bg-red-200"
                >
                  Excluir
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="documents.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
              Nenhum documento adicionado
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { DocumentIcon } from '@heroicons/vue/24/outline'
import RequiredDocumentsInfo from './RequiredDocumentsInfo.vue'

defineProps({
  documents: {
    type: Array,
    default: () => []
  },
  propertyType: {
    type: Number,
    default: null
  },
  mode: {
    type: String,
    default: 'edit', // 'edit' or 'view'
    validator: (value) => ['edit', 'view'].includes(value)
  }
})

defineEmits(['add-document', 'remove-document', 'view-document'])

const formatDate = (date) => {
  if (!date || date === "Sem Data") return "Sem Data"
  
  try {
    return new Date(date).toLocaleDateString('pt-BR')
  } catch (error) {
    return "Data inválida"
  }
}
</script>