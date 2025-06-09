<template>
  <div class="commercial-form space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Comerciais</h3>
      
      <!-- Linha 1: Pavimentos e Salas -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="floors" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Pavimentos *
          </label>
          <input
            id="floors"
            type="number"
            v-model.number="localForm.floors"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.parking_spaces }"
            placeholder="Ex: 10"
          >
          <p v-if="errors.parking_spaces" class="text-red-500 text-xs mt-1">{{ errors.parking_spaces }}</p>
        </div>

        <div>
          <label for="total_area_commercial" class="block text-sm font-medium text-gray-700 mb-2">
            Área Total (m²) *
          </label>
          <input
            id="total_area_commercial"
            type="number"
            step="0.01"
            v-model.number="localForm.total_area"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.total_area }"
            placeholder="Ex: 500.00"
          >
          <p v-if="errors.total_area" class="text-red-500 text-xs mt-1">{{ errors.total_area }}</p>
        </div>
      </div>

      <!-- Linha 3: Condições do Imóvel -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="property_condition_commercial" class="block text-sm font-medium text-gray-700 mb-2">
            Condições do Imóvel *
          </label>
          <select
            id="property_condition_commercial"
            v-model="localForm.property_condition"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.property_condition }"
          >
            <option value="">Selecione...</option>
            <option value="excelente">Excelente</option>
            <option value="bom">Bom</option>
            <option value="regular">Regular</option>
            <option value="ruim">Ruim</option>
            <option value="pessimo">Péssimo</option>
          </select>
          <p v-if="errors.property_condition" class="text-red-500 text-xs mt-1">{{ errors.property_condition }}</p>
        </div>

        <div>
          <label for="parking_spaces" class="block text-sm font-medium text-gray-700 mb-2">
            Vagas de estacionamento *
          </label>
          <input
            id="parking_spaces"
            type="number"
            v-model.number="localForm.parking_spaces"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.parking_spaces }"
            placeholder="Ex: 10"
          >
          <p v-if="errors.parking_spaces" class="text-red-500 text-xs mt-1">{{ errors.parking_spaces }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CommercialFormModal',
  
  props: {
    modelValue: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },

  emits: ['update:modelValue'],

  computed: {
    localForm: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  }
}
</script>