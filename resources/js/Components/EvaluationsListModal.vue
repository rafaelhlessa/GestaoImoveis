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
              <p class="text-sm text-gray-600 mt-1 truncate max-w-[60vw]" :title="`${property.nickname} - ${property.city}`">{{ property.nickname }} - {{ property.city }}</p>
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
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Imagens
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
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
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                          <div v-if="evaluation.media && evaluation.media.length" class="flex -space-x-2">
                            <img v-for="m in evaluation.media.slice(0,3)" :key="m.id" :src="storageUrl(m.path)" class="w-8 h-8 rounded object-cover ring-2 ring-white" />
                          </div>
                          <span class="text-xs text-gray-500" v-if="evaluation.media && evaluation.media.length > 3">
                            +{{ evaluation.media.length - 3 }} mais
                          </span>
                          <span v-else-if="evaluation.media && evaluation.media.length === 0" class="text-xs text-gray-400">Sem imagens</span>
                          <span v-else class="text-xs text-gray-400">Sem imagens</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span v-if="evaluation.owner_acknowledged"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                          Confirmada
                        </span>
                        <span v-else
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                          Pendente
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-3 items-center">
                          <!-- Comum: ver detalhes -->
                          <button
                            @click="viewEvaluationDetails(evaluation)"
                            class="text-blue-600 hover:text-blue-900"
                            title="Ver detalhes" aria-label="Ver detalhes"
                          >
                            <EyeIcon class="w-5 h-5" />
                          </button>

                          <!-- Enviar imagens: avaliador (autor) e proprietários -->
                          <button
                            v-if="isEvaluatorAuthor(evaluation)"
                            @click="openUpload(evaluation)"
                            class="text-gray-700 hover:text-gray-900"
                            title="Enviar imagens (máx 10MB total)" aria-label="Enviar imagens"
                          >
                            <PhotoIcon class="w-5 h-5" />
                          </button>

                          <!-- Proprietário: confirmar recebimento (PUT) enquanto pendente -->
                          <button
                            v-if="isOwner && !evaluation.owner_acknowledged"
                            @click="acknowledge(evaluation)"
                            class="text-emerald-700 hover:text-emerald-900"
                            title="Confirmar recebimento" aria-label="Confirmar recebimento"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                              <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.527-1.493-1.493a.75.75 0 10-1.06 1.06l2.25 2.25a.75.75 0 001.146-.094l3.613-5.378z" clip-rule="evenodd" />
                            </svg>
                          </button>

                          <!-- Proprietário: baixar PDF após ack -->
                          <a v-if="isOwner && evaluation.owner_acknowledged && evaluation.pdf_url"
                             :href="evaluation.pdf_url" target="_blank" rel="noopener"
                             class="text-green-700 hover:text-green-900"
                             title="Baixar PDF" aria-label="Baixar PDF">
                            <ArrowDownTrayIcon class="w-5 h-5" />
                          </a>

                          <!-- Avaliador (autor): gerar/atualizar PDF; oculto para não-autores -->
                          <button
                            v-if="isEvaluatorAuthor(evaluation)"
                            @click="openPdf(evaluation)"
                            class="text-purple-700 hover:text-purple-900"
                            title="Gerar/Atualizar PDF" aria-label="Gerar/Atualizar PDF"
                          >
                            <DocumentTextIcon class="w-5 h-5" />
                          </button>
                          <button
                            v-if="isEvaluatorAuthor(evaluation) && !evaluation.owner_acknowledged"
                            @click="destroyEval(evaluation)"
                            class="text-red-700 hover:text-red-900"
                            title="Excluir avaliação" aria-label="Excluir avaliação"
                          >
                            <TrashIcon class="w-5 h-5" />
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
                    <h5 class="font-medium text-green-900 mb-2">Propriedades Rurais</h5>
                    <div class="text-sm text-green-700">
                      <div>Quantidade: {{ evaluations.filter(e => e.property_type === 'rural').length }}</div>
                      <div>Valor médio: {{ formatCurrency(
                        evaluations.filter(e => e.property_type === 'rural').length > 0
                          ? evaluations
                              .filter(e => e.property_type === 'rural')
                              .reduce((sum, e) => sum + (Number(e.valuation) || 0), 0) /
                            evaluations.filter(e => e.property_type === 'rural').length
                          : 0
                      ) }}</div>
                    </div>
                  </div>

                  <!-- Avaliações Industriais -->
                  <div v-if="evaluations.filter(e => e.property_type === 'industrial').length > 0" 
                       class="bg-red-50 p-4 rounded-lg">
                    <h5 class="font-medium text-red-900 mb-2">Propriedades Industriais</h5>
                    <div class="text-sm text-red-700">
                      <div>Quantidade: {{ evaluations.filter(e => e.property_type === 'industrial').length }}</div>
                      <div>Valor médio: {{ formatCurrency(
                        evaluations.filter(e => e.property_type === 'industrial').length > 0
                          ? evaluations
                              .filter(e => e.property_type === 'industrial')
                              .reduce((sum, e) => sum + (Number(e.valuation) || 0), 0) /
                            evaluations.filter(e => e.property_type === 'industrial').length
                          : 0
                      ) }}</div>
                    </div>
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

  <!-- Upload de Imagens -->
  <input ref="fileInput" type="file" class="hidden" multiple accept="image/*" @change="doUpload" />

  <!-- Configurar PDF -->
  <div v-if="showPdf" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
      <div class="p-4 border-b font-medium">Configurar PDF</div>
      <div class="p-4 space-y-3">
        <div>
          <label class="block text-sm text-gray-700 mb-1">Logo (opcional)</label>
          <input type="file" accept="image/*" @change="(e)=> pdfForm.header_logo = e.target.files?.[0] || null" />
          <p class="text-xs text-gray-500">Será exibido com largura aproximada de 20%.</p>
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Título</label>
          <input v-model="pdfForm.header_title" type="text" class="w-full border rounded px-2 py-1" placeholder="Laudo de Avaliação" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Nome do Avaliador</label>
            <input v-model="pdfForm.appraiser_name" type="text" class="w-full border rounded px-2 py-1" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Registro (CRECI/CREA...)</label>
            <input v-model="pdfForm.appraiser_registry" type="text" class="w-full border rounded px-2 py-1" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Telefone</label>
            <input v-model="pdfForm.appraiser_phone" type="text" class="w-full border rounded px-2 py-1" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Email</label>
            <input v-model="pdfForm.appraiser_email" type="email" class="w-full border rounded px-2 py-1" />
          </div>
        </div>
      </div>
      <div class="p-4 border-t flex justify-end space-x-2">
        <button @click="showPdf=false" class="px-3 py-1 rounded border">Cancelar</button>
        <button @click="submitPdf" class="px-3 py-1 rounded bg-purple-600 text-white">Gerar PDF</button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { XMarkIcon, EyeIcon, PhotoIcon, DocumentTextIcon, ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline'
import EvaluationDetailsModal from './EvaluationDetailsModal.vue'
import { router, usePage } from '@inertiajs/vue3'

export default {
  name: 'EvaluationsListModal',
  
  components: {
    XMarkIcon,
    EyeIcon,
    PhotoIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    TrashIcon,
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
    },
    owners: {
      type: Array,
      default: () => []
    }
  },

  emits: ['close', 'open-evaluation-modal'],

  setup(props, { emit }) {
    const { auth } = usePage().props
    const showDetailsModal = ref(false)
    const selectedEvaluation = ref(null)
    const currentForUpload = ref(null)
    const fileInput = ref(null)
    const showPdf = ref(false)
    const pdfForm = ref({
      header_logo: null,
      header_title: 'Laudo de Avaliação',
      appraiser_name: '',
      appraiser_phone: '',
      appraiser_email: '',
      appraiser_registry: ''
    })

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
      if (propertyType === 'industrial') {
        return 'bg-red-100 text-red-800'
      }
      if (propertyType === 'urbana') {
        if (urbanSubtype === 'residencial') return 'bg-blue-100 text-blue-800'
        if (urbanSubtype === 'comercial') return 'bg-purple-100 text-purple-800'
        if (urbanSubtype === 'misto') return 'bg-indigo-100 text-indigo-800'
        return 'bg-blue-100 text-blue-800'
      }
      return 'bg-gray-100 text-gray-800'
    }

    const getTypeLabel = (propertyType, urbanSubtype) => {
      if (propertyType === 'rural') return 'Rural'
      if (propertyType === 'industrial') return 'Industrial'
      if (propertyType === 'urbana') {
        if (urbanSubtype === 'residencial') return 'Residencial'
        if (urbanSubtype === 'comercial') return 'Comercial'
        if (urbanSubtype === 'misto') return 'Misto'
        return 'Urbana'
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

    const storageUrl = (path) => {
      if (!path) return ''
      return `/storage/${path}`.replace(/\/+/g, '/').replace('//storage', '/storage')
    }

    // Roles
    const isOwner = computed(() => {
      try {
        const uid = auth?.user?.id
        return Array.isArray(props.owners) && props.owners.some(o => o.user_id === uid || o.id === uid)
      } catch { return false }
    })

    const isEvaluatorAuthor = (evaluation) => {
      try {
        const uid = auth?.user?.id
        const rowUserId = evaluation?.user_id ?? evaluation?.user?.id
        return rowUserId === uid
      } catch { return false }
    }

    // Upload flow
    const openUpload = (evaluation) => {
      currentForUpload.value = evaluation
      fileInput.value?.click()
    }

    const doUpload = (e) => {
      const files = Array.from(e.target.files || [])
      if (!files.length || !currentForUpload.value) return
      const formData = new FormData()
      files.forEach(f => formData.append('images[]', f))
      router.post(route('properties.evaluations.media', { property: props.property.id, evaluation: currentForUpload.value.id }), formData, {
        forceFormData: true
      })
      e.target.value = ''
    }

    // PDF flow
    const openPdf = (evaluation) => {
      selectedEvaluation.value = evaluation
      pdfForm.value.appraiser_name = evaluation.appraiser || ''
      showPdf.value = true
    }

    const submitPdf = () => {
      if (!selectedEvaluation.value) return
      const fd = new FormData()
      Object.entries(pdfForm.value).forEach(([k, v]) => {
        if (v !== null && v !== '') fd.append(k, v)
      })
      router.post(route('properties.evaluations.pdf', { property: props.property.id, evaluation: selectedEvaluation.value.id }), fd, {
        forceFormData: true,
        onSuccess: () => { showPdf.value = false },
      })
    }

    // Acknowledge (owner PUT)
    const acknowledge = (evaluation) => {
      if (!evaluation) return
      router.put(route('properties.evaluations.acknowledge', { property: props.property.id, evaluation: evaluation.id }), {}, {
        onSuccess: () => {
          // best-effort update local flag if backend returns via flash; otherwise reload
          evaluation.owner_acknowledged = true
        }
      })
    }

    const destroyEval = (evaluation) => {
      if (!evaluation) return
      if (!confirm('Excluir esta avaliação? Esta ação não pode ser desfeita.')) return
      router.delete(route('properties.evaluations.destroy', { property: props.property.id, evaluation: evaluation.id }), {
        onSuccess: () => {
          window.__toast?.({ text: 'Avaliação excluída.', kind: 'success' })
          router.reload({ only: ['evaluations', 'property'] })
        },
        onError: () => window.__toast?.({ text: 'Não foi possível excluir.', kind: 'error' })
      })
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
  getConditionClass,
  // upload/pdf
      fileInput,
      openUpload,
      doUpload,
      openPdf,
      showPdf,
      pdfForm,
      submitPdf,
      storageUrl,
      // roles/actions
      isOwner,
      isEvaluatorAuthor,
      acknowledge,
      destroyEval
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