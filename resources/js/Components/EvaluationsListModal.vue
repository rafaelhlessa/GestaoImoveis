<template>
  <!-- Modal para Visualizar Avaliações -->
  <Teleport to="body">
    <transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl h-5/6 mx-4 overflow-hidden">
          <!-- Header do Modal -->
          <div class="flex justify-between items-center p-6 border-b">
            <div>
              <h3 class="text-xl font-medium text-gray-900">Avaliações da Propriedade</h3>
              <p class="text-sm text-gray-600 mt-1">{{ property.nickname }} - {{ property.city }}</p>
            </div>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          
          <!-- Conteúdo do Modal -->
          <div class="p-6 overflow-y-auto" style="height: calc(100% - 180px);">
            <!-- Lista de Avaliações -->
            <div v-if="evaluations && evaluations.length > 0">
              <!-- Estatísticas Resumidas -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ evaluations.length }}</div>
                  <div class="text-sm text-blue-700">Total de Avaliações</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-green-600">
                    {{ evaluations.length > 0 ? formatCurrency(evaluations.reduce((avg, evaluation) => avg + (Number(evaluation.valuation) || 0), 0) / evaluations.length) : formatCurrency(0) }}
                  </div>
                  <div class="text-sm text-green-700">Valor Médio</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-purple-600">
                    {{ formatCurrency(Math.max(...evaluations.map(e => e.valuation))) }}
                  </div>
                  <div class="text-sm text-purple-700">Maior Valor</div>
                </div>
              </div>

              <!-- Tabela de Avaliações -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Avaliador
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valor
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tipo
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Condição
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
                    <tr v-for="evaluation in evaluations" :key="evaluation.id" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                          {{ evaluation.appraiser }}
                        </div>
                        <div v-if="evaluation.user" class="text-sm text-gray-500">
                          por {{ evaluation.user.name }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="font-semibold text-green-600">
                          {{ formatCurrency(evaluation.valuation) }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span v-if="evaluation.property_type" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="getTypeClass(evaluation.property_type, evaluation.urban_subtype)">
                          {{ getTypeLabel(evaluation.property_type, evaluation.urban_subtype) }}
                        </span>
                        <span v-else class="text-sm text-gray-400">N/A</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span v-if="evaluation.property_condition" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="getConditionClass(evaluation.property_condition)">
                          {{ evaluation.property_condition_label || evaluation.property_condition }}
                        </span>
                        <span v-else class="text-sm text-gray-400">N/A</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(evaluation.created_at) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                          <button
                            @click="viewEvaluationDetails(evaluation)"
                            class="text-blue-600 hover:text-blue-900 font-medium"
                          >
                            Ver Detalhes
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Resumo por Tipo -->
              <div class="mt-8 space-y-4">
                <h4 class="text-lg font-medium text-gray-900">Resumo por Tipo</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Avaliações Urbanas -->
                  <div v-if="evaluations.filter(e => e.property_type === 'urbana').length > 0" 
                       class="bg-blue-50 p-4 rounded-lg">
                    <h5 class="font-medium text-blue-900 mb-2">Propriedades Urbanas</h5>
                    <div class="text-sm text-blue-700">
                      <div>Quantidade: {{ evaluations.filter(e => e.property_type === 'urbana').length }}</div>
                      <div>Valor médio: {{ formatCurrency(
                        evaluations
                          .filter(e => e.property_type === 'urbana').length > 0
                          ? evaluations
                              .filter(e => e.property_type === 'urbana')
                              .reduce((sum, e) => sum + (Number(e.valuation) || 0), 0) / 
                            evaluations.filter(e => e.property_type === 'urbana').length
                          : 0
                      ) }}</div>
                    </div>
                  </div>

                  <!-- Avaliações Rurais -->
                  <div v-if="evaluations.filter(e => e.property_type === 'rural').length > 0" 
                       class="bg-green-50 p-4 rounded-lg">
                      <div>Valor médio: {{ formatCurrency(evaluations.filter(e => e.property_type === 'rural').length > 0 ? evaluations.filter(e => e.property_type === 'rural').reduce((sum, e) => sum + (Number(e.valuation) || 0), 0) / evaluations.filter(e => e.property_type === 'rural').length : 0) }}</div>
                    </div>
                  </div>
                </div>
              
            </div>

            <!-- Estado Vazio -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma avaliação encontrada</h3>
              <p class="mt-1 text-sm text-gray-500">Esta propriedade ainda não possui avaliações cadastradas.</p>
              <div class="mt-6">
                <button v-if="property.can_create_evaluation"
                  @click="openEvaluationModal"
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  Nova Avaliação
                </button>
              </div>
            </div>
          </div>
          
          <!-- Footer do Modal -->
          <div class="flex justify-between items-center p-4 border-t bg-gray-50">
            <button 
              v-if="property.can_create_evaluation"
              @click="openEvaluationModal"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Nova Avaliação
            </button>
            <div class="flex-1"></div>
            <button @click="closeModal" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>

  <!-- Modal de Detalhes da Avaliação -->
  <EvaluationDetailsModal
    :show="showDetailsModal"
    :evaluation="selectedEvaluation"
    :property="property"
    @close="closeDetailsModal"
  />
</template>

<script>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import EvaluationDetailsModal from './EvaluationDetailsModal.vue'

export default {
  name: 'EvaluationsListModal',
  
  components: {
    XMarkIcon,
    EvaluationDetailsModal
  },

  props: {
    show: {
      type: Boolean,
      required: true
    },
    property: {
      type: Object,
      required: true
    },
    evaluations: {
      type: Array,
      default: () => []
    }
  },

  emits: ['close', 'open-evaluation-modal'],

  setup(props, { emit }) {
    const showDetailsModal = ref(false)
    const selectedEvaluation = ref(null)

    const closeModal = () => {
      emit('close')
    }

    const openEvaluationModal = () => {
      emit('open-evaluation-modal')
      closeModal()
    }

    const viewEvaluationDetails = (evaluation) => {
      selectedEvaluation.value = evaluation
      showDetailsModal.value = true
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedEvaluation.value = null
    }

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(value)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('pt-BR')
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

    return {
      showDetailsModal,
      selectedEvaluation,
      closeModal,
      openEvaluationModal,
      viewEvaluationDetails,
      closeDetailsModal,
      formatCurrency,
      formatDate,
      getTypeClass,
      getTypeLabel,
      getConditionClass
    }
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white {
  transform: scale(0.9);
}
</style>