<template>
  <!-- Modal de Avaliação -->
  <Teleport to="body">
    <transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] mx-4 overflow-hidden">
          <!-- Header do Modal -->
          <div class="flex justify-between items-center p-6 border-b bg-gray-50">
            <div>
              <h3 class="text-xl font-semibold text-gray-900">Fazer Avaliação</h3>
              <p class="text-sm text-gray-600 mt-1">
                {{ property.nickname }} - {{ property.city }}, {{ property.district }}
              </p>
            </div>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          
          <!-- Conteúdo do Modal -->
          <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 180px);">
            <form @submit.prevent="handleSubmit">
              <!-- Campos Básicos da Avaliação -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <label for="appraiser" class="block text-sm font-medium text-gray-700 mb-2">
                    Avaliador *
                  </label>
                  <input
                    id="appraiser"
                    type="text"
                    v-model="form.appraiser"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="{ 'border-red-500': errors.appraiser }"
                    placeholder="Nome do avaliador"
                    required
                  >
                  <p v-if="errors.appraiser" class="text-red-500 text-xs mt-1">
                    {{ errors.appraiser }}
                  </p>
                </div>

                <div>
                  <label for="valuation" class="block text-sm font-medium text-gray-700 mb-2">
                    Valor da Avaliação (R$) *
                  </label>
                  <input
                    id="valuation"
                    type="number"
                    step="0.01"
                    v-model.number="form.valuation"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="{ 'border-red-500': errors.valuation }"
                    placeholder="0,00"
                    required
                  >
                  <p v-if="errors.valuation" class="text-red-500 text-xs mt-1">
                    {{ errors.valuation }}
                  </p>
                </div>
              </div>

              <!-- Comentários Gerais -->
              <div class="mb-6">
                <label for="comments" class="block text-sm font-medium text-gray-700 mb-2">
                  Comentários da Avaliação
                </label>
                <textarea
                  id="comments"
                  v-model="form.comments"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.comments }"
                  placeholder="Comentários sobre a avaliação geral..."
                ></textarea>
                <p v-if="errors.comments" class="text-red-500 text-xs mt-1">
                  {{ errors.comments }}
                </p>
              </div>

              <!-- Tipo de Propriedade - Auto-detectado baseado na propriedade -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Tipo de Propriedade *
                </label>
                <div class="grid grid-cols-2 gap-4">
                  <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                         :class="form.property_type === 'urbana' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                    <input type="radio" v-model="form.property_type" value="urbana" class="sr-only">
                    <div class="flex items-center">
                      <div class="w-4 h-4 rounded-full border-2 mr-3"
                           :class="form.property_type === 'urbana' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                        <div v-if="form.property_type === 'urbana'" class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                      </div>
                      <span class="text-sm font-medium">Urbana</span>
                    </div>
                  </label>

                  <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                         :class="form.property_type === 'rural' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                    <input type="radio" v-model="form.property_type" value="rural" class="sr-only">
                    <div class="flex items-center">
                      <div class="w-4 h-4 rounded-full border-2 mr-3"
                           :class="form.property_type === 'rural' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                        <div v-if="form.property_type === 'rural'" class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                      </div>
                      <span class="text-sm font-medium">Rural</span>
                    </div>
                  </label>
                </div>
                <p v-if="errors.property_type" class="text-red-500 text-xs mt-1">
                  {{ errors.property_type }}
                </p>
              </div>

              <!-- Subtipo Urbano -->
              <div v-if="form.property_type === 'urbana'" class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Subtipo da Propriedade Urbana *
                </label>
                <div class="grid grid-cols-2 gap-4">
                  <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                         :class="form.urban_subtype === 'residencial' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                    <input type="radio" v-model="form.urban_subtype" value="residencial" class="sr-only">
                    <div class="flex items-center">
                      <div class="w-4 h-4 rounded-full border-2 mr-3"
                           :class="form.urban_subtype === 'residencial' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                        <div v-if="form.urban_subtype === 'residencial'" class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                      </div>
                      <span class="text-sm font-medium">Residencial</span>
                    </div>
                  </label>

                  <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                         :class="form.urban_subtype === 'comercial' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                    <input type="radio" v-model="form.urban_subtype" value="comercial" class="sr-only">
                    <div class="flex items-center">
                      <div class="w-4 h-4 rounded-full border-2 mr-3"
                           :class="form.urban_subtype === 'comercial' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                        <div v-if="form.urban_subtype === 'comercial'" class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                      </div>
                      <span class="text-sm font-medium">Comercial</span>
                    </div>
                  </label>
                </div>
                <p v-if="errors.urban_subtype" class="text-red-500 text-xs mt-1">
                  {{ errors.urban_subtype }}
                </p>
              </div>

              <!-- Formulário Residencial -->
              <ResidentialFormModal 
                v-if="form.property_type === 'urbana' && form.urban_subtype === 'residencial'"
                v-model="form"
                :errors="errors"
              />

              <!-- Formulário Comercial -->
              <CommercialFormModal 
                v-if="form.property_type === 'urbana' && form.urban_subtype === 'comercial'"
                v-model="form"
                :errors="errors"
              />

              <!-- Formulário Rural -->
              <RuralFormModal 
                v-if="form.property_type === 'rural'"
                v-model="form"
                :errors="errors"
              />

              <!-- Observações Técnicas -->
              <div class="mt-6">
                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                  Observações Técnicas
                </label>
                <textarea
                  id="observations"
                  v-model="form.observations"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.observations }"
                  placeholder="Observações técnicas detalhadas sobre a propriedade..."
                ></textarea>
                <p v-if="errors.observations" class="text-red-500 text-xs mt-1">
                  {{ errors.observations }}
                </p>
              </div>
            </form>
          </div>
          
          <!-- Footer do Modal -->
          <div class="flex justify-end space-x-4 p-6 border-t bg-gray-50">
            <button
              type="button"
              @click="closeModal"
              class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancelar
            </button>
            <button
              type="button"
              @click="handleSubmit"
              :disabled="loading"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Salvando...' : 'Salvar Avaliação' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { router } from '@inertiajs/vue3'
import ResidentialFormModal from './ResidentialFormModal.vue'
import CommercialFormModal from './CommercialFormModal.vue'
import RuralFormModal from './RuralFormModal.vue'

export default {
  name: 'PropertyEvaluationModal',
  
  components: {
    XMarkIcon,
    ResidentialFormModal,
    CommercialFormModal,
    RuralFormModal
  },

  props: {
    show: {
      type: Boolean,
      required: true
    },
    property: {
      type: Object,
      required: true
    }
  },

  emits: ['close', 'success'],

  setup(props, { emit }) {
    const loading = ref(false)
    const errors = ref({})
    
    const form = ref({
      property_id: props.property.id,
      user_id: null,
      appraiser: '',
      valuation: null,
      comments: '',
      property_type: '',
      urban_subtype: '',
      // Residencial
      rooms: null,
      bedrooms: null,
      bathrooms: null,
      built_area: null,
      total_area: null,
      property_condition: '',
      garage_spaces: null,
      furniture_status: '',
      // Residencial - novos campos
      proximidade_servicos: '',
      transporte: '',
      seguranca: '',
      terreno_topografia: '',
      terreno_posicao: '',
      construcao_padrao: '',
      construcao_idade: null,
      estado_reformas: '',
      suites: null,
      salas: null,
      cozinha: '',
      area_servico: '',
      external_quintal: false,
      external_jardim: false,
      external_piscina: false,
      external_churrasqueira: false,
      estado_instalacoes_eletricas: '',
      estado_instalacoes_hidraulicas: '',
      // Comercial
      floors: null,
      office_rooms: null,
      parking_spaces: null,
      // Rural
      rural_total_area: null,
      has_construction: null,
      construction_types: [],
      has_farming: null,
      farming_types: [],
      water_source: '',
      water_source_details: '',
      observations: '',
      // Rebanho (nova estrutura)
      has_herd: null,
      herds: []
    })

    // Auto-detectar tipo baseado na propriedade
    watch(() => props.property, (newProperty) => {
      if (newProperty) {
        // Detectar tipo baseado no type_property da propriedade
        if (newProperty.type_property === 1) {
          form.value.property_type = 'urbana'
        } else if (newProperty.type_property === 2) {
          form.value.property_type = 'rural'
        }
      }
    }, { immediate: true })

    // Reset form when modal closes
    watch(() => props.show, (show) => {
      if (!show) {
        resetForm()
      }
    })

    const resetForm = () => {
      form.value = {
        property_id: props.property.id,
        user_id: null,
        appraiser: '',
        valuation: null,
        comments: '',
        property_type: props.property.type_property === 1 ? 'urbana' : props.property.type_property === 2 ? 'rural' : '',
        urban_subtype: '',
        // Reset other fields
        rooms: null,
        bedrooms: null,
        bathrooms: null,
        built_area: null,
        total_area: null,
        property_condition: '',
        garage_spaces: null,
        furniture_status: '',
        proximidade_servicos: '',
        transporte: '',
        seguranca: '',
        terreno_topografia: '',
        terreno_posicao: '',
        construcao_padrao: '',
        construcao_idade: null,
        estado_reformas: '',
        suites: null,
        salas: null,
        cozinha: '',
        area_servico: '',
        external_quintal: false,
        external_jardim: false,
        external_piscina: false,
        external_churrasqueira: false,
        estado_instalacoes_eletricas: '',
        estado_instalacoes_hidraulicas: '',
        floors: null,
        office_rooms: null,
        parking_spaces: null,
        rural_total_area: null,
        has_construction: null,
        construction_types: [],
        has_farming: null,
        farming_types: [],
        water_source: '',
        water_source_details: '',
        observations: '',
        // Rebanho (nova estrutura)
        has_herd: null,
        herds: []
      }
      errors.value = {}
    }

    const closeModal = () => {
      emit('close')
    }

    const handleSubmit = async () => {
      loading.value = true
      errors.value = {}

      try {
        // Monta objeto de detalhes conforme o tipo selecionado
        const details = {}
        // Comum a residenciais (casas, sobrados, apartamentos)
        if (form.value.property_type === 'urbana') {
          if (form.value.urban_subtype === 'residencial') {
            details.category = 'residencial'
            details.localizacao = {
              bairro: props.property.district || null,
              cidade: props.property.city || null,
              proximidade_servicos: form.value.proximidade_servicos || null,
              transporte: form.value.transporte || null,
              seguranca: form.value.seguranca || null
            }
            details.terreno = {
              metragem_total: form.value.total_area,
              topografia: form.value.terreno_topografia || null,
              posicao: form.value.terreno_posicao || null
            }
            details.construcao = {
              metragem_construida: form.value.built_area,
              padrao_construtivo: form.value.construcao_padrao || null,
              idade_imovel: form.value.construcao_idade
            }
            details.distribuicao_interna = {
              quartos: form.value.bedrooms,
              suites: form.value.suites,
              banheiros: form.value.bathrooms,
              salas: form.value.salas,
              cozinha: form.value.cozinha || null,
              area_servico: form.value.area_servico || null
            }
            details.areas_externas = {
              garagem: form.value.garage_spaces,
              quintal: !!form.value.external_quintal,
              jardim: !!form.value.external_jardim,
              piscina: !!form.value.external_piscina,
              churrasqueira: !!form.value.external_churrasqueira
            }
            details.estado_conservacao = {
              reformas: form.value.estado_reformas || null,
              acabamentos: form.value.property_condition,
              instalacoes: {
                eletricas: form.value.estado_instalacoes_eletricas || null,
                hidraulicas: form.value.estado_instalacoes_hidraulicas || null
              }
            }
          } else if (form.value.urban_subtype === 'comercial') {
            // Sala comercial / prédio comercial (simplificado aqui)
            details.category = 'comercial'
            details.localizacao = {
              regiao: props.property.city || null,
              fluxo: null,
              visibilidade: null
            }
            details.metragem = {
              total: form.value.total_area
            }
            details.infraestrutura_predial = {
              elevadores: null,
              estacionamento: form.value.parking_spaces,
              acessibilidade: null,
              portaria: null
            }
            details.custos_fixos = {
              condominio: null,
              iptu: null,
              energia: null,
              agua: null
            }
            details.uso_permitido = null
          }
        } else if (form.value.property_type === 'rural') {
          details.category = 'rural'
          details.area_total = form.value.rural_total_area
          details.georreferenciamento = null
          details.solo_topografia = {
            aptidao: null,
            fertilidade: null,
            drenagem: null
          }
          details.recursos_hidricos = {
            fonte: form.value.water_source,
            detalhes: form.value.water_source_details
          }
          details.infraestruturas = {
            construcao: form.value.has_construction,
            tipos_construcao: form.value.construction_types,
            sede: null,
            casas_empregados: null,
            currais_silos_armazens: null
          }
          details.producao_existente = {
            lavouras: form.value.farming_types,
            criacao_animais: null,
            benfeitorias: null
          }
          if (form.value.has_herd) {
            const herds = Array.isArray(form.value.herds) ? form.value.herds : []
            details.rebanho = herds.map(h => ({
              especie: h.especie || null,
              raca: h.raca || null,
              faixas: (Array.isArray(h.faixas) ? h.faixas : []).map(f => ({
                faixa_etaria: f.faixa_etaria || null,
                quantidade_machos: f.quantidade_machos ?? null,
                quantidade_femeas: f.quantidade_femeas ?? null,
              }))
            }))
          } else {
            details.rebanho = []
          }
          details.acessos = {
            estradas: null,
            distancia_centros: null,
            transporte: null
          }
  }

  const payload = { ...form.value, details }
        // Fazer requisição para salvar avaliação
        await router.post(route('properties.evaluations.store', props.property.id), payload, {
          onSuccess: () => {
            emit('success')
            closeModal()
          },
          onError: (responseErrors) => {
            errors.value = responseErrors
          },
          onFinish: () => {
            loading.value = false
          }
        })
      } catch (error) {
        console.error('Erro ao salvar avaliação:', error)
        loading.value = false
      }
    }

    // Watch para resetar campos quando muda o tipo
    watch(() => form.value.property_type, (newValue) => {
      if (newValue !== 'urbana') {
        form.value.urban_subtype = ''
      }
      resetTypeSpecificFields()
    })

    watch(() => form.value.urban_subtype, () => {
      resetTypeSpecificFields()
    })

    const resetTypeSpecificFields = () => {
      const fieldsToReset = [
        'rooms', 'bedrooms', 'bathrooms', 'built_area', 'total_area',
        'property_condition', 'garage_spaces', 'furniture_status',
        'proximidade_servicos', 'transporte', 'seguranca',
        'terreno_topografia', 'terreno_posicao',
        'construcao_padrao', 'construcao_idade', 'estado_reformas',
        'suites', 'salas', 'cozinha', 'area_servico',
        'estado_instalacoes_eletricas', 'estado_instalacoes_hidraulicas',
        'floors', 'office_rooms', 'parking_spaces',
        'rural_total_area', 'has_construction', 'construction_types',
        'has_farming', 'farming_types', 'water_source', 'water_source_details',
        // Herd (nova estrutura)
        'has_herd', 'herds'
      ]

      fieldsToReset.forEach(field => {
        if (Array.isArray(form.value[field])) {
          form.value[field] = []
        } else if (typeof form.value[field] === 'boolean') {
          form.value[field] = false
        } else {
          form.value[field] = typeof form.value[field] === 'number' ? null : ''
        }
      })

  // Reset checkboxes explicitly if undefined
      form.value.external_quintal = false
      form.value.external_jardim = false
      form.value.external_piscina = false
      form.value.external_churrasqueira = false
  // Herd explicit reset
  form.value.has_herd = null
  form.value.herds = []
    }

    return {
      form,
      loading,
      errors,
      closeModal,
      handleSubmit,
      resetForm
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