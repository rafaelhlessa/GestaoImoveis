<template>
  <div class="property-evaluation-form">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-medium text-gray-900">Avaliação da Propriedade</h2>
        <p class="text-sm text-gray-600 mt-1">Preencha os detalhes para avaliação da propriedade</p>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6">
        <!-- Tipo de Propriedade -->
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
          <p v-if="$page.props.errors.property_type" class="text-red-500 text-xs mt-1">
            {{ $page.props.errors.property_type }}
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
          <p v-if="$page.props.errors.urban_subtype" class="text-red-500 text-xs mt-1">
            {{ $page.props.errors.urban_subtype }}
          </p>
        </div>

        <!-- Formulário Residencial -->
        <ResidentialForm 
          v-if="form.property_type === 'urbana' && form.urban_subtype === 'residencial'"
          v-model="form"
        />

        <!-- Formulário Comercial -->
        <CommercialForm 
          v-if="form.property_type === 'urbana' && form.urban_subtype === 'comercial'"
          v-model="form"
        />

        <!-- Formulário Rural -->
        <RuralForm 
          v-if="form.property_type === 'rural'"
          v-model="form"
        />

        <!-- Observações -->
        <div class="mt-6">
          <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
            Observações Gerais
          </label>
          <textarea
            id="observations"
            v-model="form.observations"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': $page.props.errors.observations }"
            placeholder="Informações adicionais sobre a propriedade..."
          ></textarea>
          <p v-if="$page.props.errors.observations" class="text-red-500 text-xs mt-1">
            {{ $page.props.errors.observations }}
          </p>
        </div>

        <!-- Botões -->
        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t">
          <button
            type="button"
            @click="handleCancel"
            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Salvando...' : (isEditing ? 'Atualizar Avaliação' : 'Salvar Avaliação') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import ResidentialForm from './ResidentialForm.vue'
import CommercialForm from './CommercialForm.vue'
import RuralForm from './RuralForm.vue'

export default {
  name: 'PropertyEvaluationForm',
  
  components: {
    ResidentialForm,
    CommercialForm,
    RuralForm
  },

  props: {
    property: {
      type: Object,
      default: null
    },
    evaluation: {
      type: Object,
      default: () => null
    },
    isEditing: {
      type: Boolean,
      default: false
    }
  },

  emits: ['cancel'],

  setup(props) {
    const form = useForm({
      property_id: props.property?.id || null,
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
      observations: ''
    })

    // Se estiver editando, preencher o formulário
    if (props.evaluation) {
      Object.keys(form.data()).forEach(key => {
        if (props.evaluation[key] !== undefined) {
          form[key] = props.evaluation[key]
        }
      })
    }

    return { form }
  },

  watch: {
    'form.property_type'(newValue) {
      if (newValue !== 'urbana') {
        this.form.urban_subtype = ''
      }
      this.resetFormFields()
    },

    'form.urban_subtype'() {
      this.resetFormFields()
    }
  },

  methods: {
    resetFormFields() {
      // Reset campos específicos baseado no tipo
      const fieldsToReset = [
        'rooms', 'bedrooms', 'bathrooms', 'built_area', 'total_area',
        'property_condition', 'garage_spaces', 'furniture_status',
        'floors', 'office_rooms', 'parking_spaces',
        'rural_total_area', 'has_construction', 'construction_types',
        'has_farming', 'farming_types', 'water_source', 'water_source_details'
      ]

      fieldsToReset.forEach(field => {
        if (Array.isArray(this.form[field])) {
          this.form[field] = []
        } else if (typeof this.form[field] === 'boolean') {
          this.form[field] = null
        } else {
          this.form[field] = typeof this.form[field] === 'number' ? null : ''
        }
      })
    },

    handleSubmit() {
      if (this.isEditing && this.evaluation) {
        this.form.put(route('properties.evaluations.update', [this.property.id, this.evaluation.id]))
      } else {
        this.form.post(route('properties.evaluations.store', this.property.id))
      }
    },

    handleCancel() {
      if (this.isEditing && this.evaluation) {
        this.$inertia.visit(route('properties.evaluations.show', [this.property.id, this.evaluation.id]))
      } else {
        this.$inertia.visit(route('properties.evaluations.index', this.property.id))
      }
    }
  }
}
</script>

<style scoped>
.property-evaluation-form {
  max-width: 800px;
  margin: 0 auto;
}
</style>