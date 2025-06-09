<template>
  <!-- Modal de Detalhes da Avaliação -->
  <Teleport to="body">
    <transition name="modal">
      <div v-if="show && evaluation" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] mx-4 overflow-hidden">
          <!-- Header do Modal -->
          <div class="flex justify-between items-center p-6 border-b">
            <div>
              <h3 class="text-xl font-medium text-gray-900">Detalhes da Avaliação</h3>
              <p class="text-sm text-gray-600 mt-1">{{ property.nickname }} - {{ property.city }}</p>
            </div>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          
          <!-- Conteúdo do Modal -->
          <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            
            <!-- Informações Básicas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
              
              <!-- Dados da Avaliação -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Dados da Avaliação</h3>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Avaliador</dt>
                    <dd class="text-sm text-gray-900">{{ evaluation.appraiser }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Valor da Avaliação</dt>
                    <dd class="text-lg font-semibold text-green-600">
                      {{ formatCurrency(evaluation.valuation) }}
                    </dd>
                  </div>
                  <div v-if="evaluation.comments">
                    <dt class="text-sm font-medium text-gray-500">Comentários</dt>
                    <dd class="text-sm text-gray-900">{{ evaluation.comments }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Data da Avaliação</dt>
                    <dd class="text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                  </div>
                  <div v-if="evaluation.user">
                    <dt class="text-sm font-medium text-gray-500">Cadastrado por</dt>
                    <dd class="text-sm text-gray-900">{{ evaluation.user.name }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Tipo de Propriedade -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Características</h3>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Tipo de Propriedade</dt>
                    <dd>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getTypeClass(evaluation.property_type, evaluation.urban_subtype)">
                        {{ getTypeLabel(evaluation.property_type, evaluation.urban_subtype) }}
                      </span>
                    </dd>
                  </div>
                  <div v-if="evaluation.property_condition_label">
                    <dt class="text-sm font-medium text-gray-500">Condições do Imóvel</dt>
                    <dd>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getConditionClass(evaluation.property_condition)">
                        {{ evaluation.property_condition_label }}
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Detalhes Específicos por Tipo -->
            
            <!-- Propriedade Residencial -->
            <div v-if="evaluation.property_type === 'urbana' && evaluation.urban_subtype === 'residencial'" class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Detalhes Residenciais</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.rooms || 0 }}</div>
                  <div class="text-sm text-gray-500">Cômodos</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.bedrooms || 0 }}</div>
                  <div class="text-sm text-gray-500">Dormitórios</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.bathrooms || 0 }}</div>
                  <div class="text-sm text-gray-500">Banheiros</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.garage_spaces || 0 }}</div>
                  <div class="text-sm text-gray-500">Vagas</div>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                  <div class="text-lg font-semibold text-blue-900">{{ evaluation.built_area || 0 }} m²</div>
                  <div class="text-sm text-blue-700">Área Construída</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                  <div class="text-lg font-semibold text-green-900">{{ evaluation.total_area || 0 }} m²</div>
                  <div class="text-sm text-green-700">Área Total</div>
                </div>
              </div>

              <div v-if="evaluation.furniture_status_label" class="mt-4">
                <span class="text-sm font-medium text-gray-500">Status da Mobília: </span>
                <span class="text-sm text-gray-900">{{ evaluation.furniture_status_label }}</span>
              </div>
            </div>

            <!-- Propriedade Comercial -->
            <div v-if="evaluation.property_type === 'urbana' && evaluation.urban_subtype === 'comercial'" class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Detalhes Comerciais</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.floors || 0 }}</div>
                  <div class="text-sm text-gray-500">Pavimentos</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.office_rooms || 0 }}</div>
                  <div class="text-sm text-gray-500">Salas</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ evaluation.parking_spaces || 0 }}</div>
                  <div class="text-sm text-gray-500">Vagas</div>
                </div>
              </div>
              
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-lg font-semibold text-blue-900">{{ evaluation.total_area || 0 }} m²</div>
                <div class="text-sm text-blue-700">Área Total</div>
              </div>
            </div>

            <!-- Propriedade Rural -->
            <div v-if="evaluation.property_type === 'rural'" class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Detalhes Rurais</h3>
              
              <div class="bg-green-50 p-6 rounded-lg mb-6">
                <div class="text-2xl font-bold text-green-900">{{ evaluation.rural_total_area || 0 }} hectares</div>
                <div class="text-sm text-green-700">Área Total Rural</div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <h4 class="font-medium text-gray-900 mb-2">Construções</h4>
                  <div v-if="evaluation.has_construction && evaluation.construction_types_text">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      Possui construções
                    </span>
                    <p class="text-sm text-gray-600 mt-1">{{ evaluation.construction_types_text }}</p>
                  </div>
                  <div v-else>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      Sem construções
                    </span>
                  </div>
                </div>

                <div>
                  <h4 class="font-medium text-gray-900 mb-2">Lavoura</h4>
                  <div v-if="evaluation.has_farming && evaluation.farming_types_text">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      Possui lavoura
                    </span>
                    <p class="text-sm text-gray-600 mt-1">{{ evaluation.farming_types_text }}</p>
                  </div>
                  <div v-else>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      Sem lavoura
                    </span>
                  </div>
                </div>
              </div>

              <div v-if="evaluation.water_source_label">
                <h4 class="font-medium text-gray-900 mb-2">Fonte de Água</h4>
                <div class="bg-blue-50 p-4 rounded-lg">
                  <div class="font-medium text-blue-900">{{ evaluation.water_source_label }}</div>
                  <div v-if="evaluation.water_source_details" class="text-sm text-blue-700 mt-1">
                    {{ evaluation.water_source_details }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Observações Técnicas -->
            <div v-if="evaluation.observations" class="pt-6 border-t border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 mb-3">Observações Técnicas</h3>
              <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ evaluation.observations }}</p>
              </div>
            </div>
          </div>
          
          <!-- Footer do Modal -->
          <div class="flex justify-end p-4 border-t bg-gray-50">
            <button @click="closeModal" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script>
import { XMarkIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'EvaluationDetailsModal',
  
  components: {
    XMarkIcon
  },

  props: {
    show: {
      type: Boolean,
      required: true
    },
    evaluation: {
      type: Object,
      default: null
    },
    property: {
      type: Object,
      required: true
    }
  },

  emits: ['close'],

  methods: {
    closeModal() {
      this.$emit('close')
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(value)
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString('pt-BR')
    },

    getTypeClass(propertyType, urbanSubtype) {
      if (propertyType === 'rural') {
        return 'bg-green-100 text-green-800'
      }
      if (propertyType === 'urbana') {
        return urbanSubtype === 'residencial' 
          ? 'bg-blue-100 text-blue-800'
          : 'bg-purple-100 text-purple-800'
      }
      return 'bg-gray-100 text-gray-800'
    },

    getTypeLabel(propertyType, urbanSubtype) {
      if (propertyType === 'rural') return 'Rural'
      if (propertyType === 'urbana') {
        return urbanSubtype === 'residencial' ? 'Residencial' : 'Comercial'
      }
      return 'N/A'
    },

    getConditionClass(condition) {
      const classes = {
        'excelente': 'bg-green-100 text-green-800',
        'bom': 'bg-blue-100 text-blue-800',
        'regular': 'bg-yellow-100 text-yellow-800',
        'ruim': 'bg-orange-100 text-orange-800',
        'pessimo': 'bg-red-100 text-red-800'
      }
      return classes[condition] || 'bg-gray-100 text-gray-800'
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