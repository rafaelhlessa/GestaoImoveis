<template>
  <AppLayout title="Avaliações">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Avaliações de Propriedades
        </h2>
        <Link 
          :href="route('property-evaluations.create')" 
          class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Nova Avaliação
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Filtros -->
          <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Tipo de Propriedade
                </label>
                <select 
                  v-model="filters.property_type"
                  @change="applyFilters"
                  class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Todos</option>
                  <option value="urbana">Urbana</option>
                  <option value="rural">Rural</option>
                </select>
              </div>
              
              <div v-if="filters.property_type === 'urbana'">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Subtipo
                </label>
                <select 
                  v-model="filters.urban_subtype"
                  @change="applyFilters"
                  class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Todos</option>
                  <option value="residencial">Residencial</option>
                  <option value="comercial">Comercial</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Condição
                </label>
                <select 
                  v-model="filters.property_condition"
                  @change="applyFilters"
                  class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Todas</option>
                  <option value="excelente">Excelente</option>
                  <option value="bom">Bom</option>
                  <option value="regular">Regular</option>
                  <option value="ruim">Ruim</option>
                  <option value="pessimo">Péssimo</option>
                </select>
              </div>

              <div class="flex items-end">
                <button
                  @click="clearFilters"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                  Limpar Filtros
                </button>
              </div>
            </div>
          </div>

          <!-- Lista de Avaliações -->
          <div v-if="evaluations.data.length > 0">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Propriedade
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tipo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Área
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Condição
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Avaliador
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Data
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Ações
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="evaluation in evaluations.data" :key="evaluation.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ evaluation.property?.title || `Avaliação #${evaluation.id}` }}
                      </div>
                      <div v-if="evaluation.property?.address" class="text-sm text-gray-500">
                        {{ evaluation.property.address }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getTypeClass(evaluation.property_type, evaluation.urban_subtype)">
                        {{ getTypeLabel(evaluation.property_type, evaluation.urban_subtype) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <span v-if="evaluation.property_type === 'rural'">
                        {{ evaluation.rural_total_area }} ha
                      </span>
                      <span v-else>
                        {{ evaluation.total_area }} m²
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span v-if="evaluation.property_condition" 
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getConditionClass(evaluation.property_condition)">
                        {{ evaluation.property_condition_label }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ evaluation.appraiser || evaluation.user?.name || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(evaluation.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex justify-end space-x-2">
                        <Link 
                          :href="route('property-evaluations.show', evaluation.id)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          Ver
                        </Link>
                        <Link 
                          :href="route('property-evaluations.edit', evaluation.id)"
                          class="text-green-600 hover:text-green-900"
                        >
                          Editar
                        </Link>
                        <button
                          @click="confirmDelete(evaluation)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Excluir
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginação -->
            <div class="px-6 py-4 border-t border-gray-200">
              <Pagination :links="evaluations.links" />
            </div>
          </div>

          <!-- Estado vazio -->
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma avaliação encontrada</h3>
            <p class="mt-1 text-sm text-gray-500">Comece criando uma nova avaliação de propriedade.</p>
            <div class="mt-6">
              <Link 
                :href="route('property-evaluations.create')"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
              >
                Nova Avaliação
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <ConfirmationModal
      :show="showDeleteModal"
      @close="showDeleteModal = false"
      @confirmed="deleteEvaluation"
    >
      <template #title>
        Excluir Avaliação
      </template>
      <template #content>
        Tem certeza que deseja excluir esta avaliação? Esta ação não pode ser desfeita.
      </template>
    </ConfirmationModal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  evaluations: {
    type: Object,
    required: true
  }
})

const showDeleteModal = ref(false)
const evaluationToDelete = ref(null)

const filters = reactive({
  property_type: '',
  urban_subtype: '',
  property_condition: ''
})

const confirmDelete = (evaluation) => {
  evaluationToDelete.value = evaluation
  showDeleteModal.value = true
}

const deleteEvaluation = () => {
  if (evaluationToDelete.value) {
    const form = useForm({})
    form.delete(route('property-evaluations.destroy', evaluationToDelete.value.id), {
      onSuccess: () => {
        showDeleteModal.value = false
        evaluationToDelete.value = null
      }
    })
  }
}

const applyFilters = () => {
  router.get(route('property-evaluations.index'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  applyFilters()
}

const getTypeClass = (propertyType, urbanSubtype) => {
  if (propertyType === 'rural') {
    return 'bg-green-100 text-green-800'
  }
  if (propertyType === 'urbana') {
    return urbanSubtype === 'residencial' 
      ? 'bg-blue-100 text-blue-800'
      : 'bg-purple-100 text-purple-800'
  }
  return 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (propertyType, urbanSubtype) => {
  if (propertyType === 'rural') return 'Rural'
  if (propertyType === 'urbana') {
    return urbanSubtype === 'residencial' ? 'Residencial' : 'Comercial'
  }
  return 'N/A'
}

const getConditionClass = (condition) => {
  const classes = {
    'excelente': 'bg-green-100 text-green-800',
    'bom': 'bg-blue-100 text-blue-800',
    'regular': 'bg-yellow-100 text-yellow-800',
    'ruim': 'bg-orange-100 text-orange-800',
    'pessimo': 'bg-red-100 text-red-800'
  }
  return classes[condition] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR')
}
</script>