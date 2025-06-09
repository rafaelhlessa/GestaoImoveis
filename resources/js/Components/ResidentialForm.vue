<template>
  <div class="residential-form space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Residenciais</h3>
      
      <!-- Linha 1: Cômodos e Dormitórios -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="rooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Cômodos *
          </label>
          <input
            id="rooms"
            type="number"
            v-model.number="localForm.rooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': $page.props.errors.rooms }"
            placeholder="Ex: 8"
          >
          <p v-if="$page.props.errors.rooms" class="text-red-500 text-xs mt-1">{{ $page.props.errors.rooms }}</p>
        </div>

        <div>
          <label for="bedrooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Dormitórios *
          </label>
          <input
            id="bedrooms"
            type="number"
            v-model.number="localForm.bedrooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.bedrooms }"
            placeholder="Ex: 3"
          >
          <p v-if="errors.bedrooms" class="text-red-500 text-xs mt-1">{{ errors.bedrooms[0] }}</p>
        </div>
      </div>

      <!-- Linha 2: Banheiros e Garagem -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="bathrooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Banheiros *
          </label>
          <input
            id="bathrooms"
            type="number"
            v-model.number="localForm.bathrooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.bathrooms }"
            placeholder="Ex: 2"
          >
          <p v-if="errors.bathrooms" class="text-red-500 text-xs mt-1">{{ errors.bathrooms[0] }}</p>
        </div>

        <div>
          <label for="garage_spaces" class="block text-sm font-medium text-gray-700 mb-2">
            Vagas de Garagem
          </label>
          <input
            id="garage_spaces"
            type="number"
            v-model.number="localForm.garage_spaces"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.garage_spaces }"
            placeholder="Ex: 2"
          >
          <p v-if="errors.garage_spaces" class="text-red-500 text-xs mt-1">{{ errors.garage_spaces[0] }}</p>
        </div>
      </div>

      <!-- Linha 3: Áreas -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="built_area" class="block text-sm font-medium text-gray-700 mb-2">
            Área Construída (m²) *
          </label>
          <input
            id="built_area"
            type="number"
            step="0.01"
            v-model.number="localForm.built_area"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.built_area }"
            placeholder="Ex: 120.50"
          >
          <p v-if="errors.built_area" class="text-red-500 text-xs mt-1">{{ errors.built_area[0] }}</p>
        </div>

        <div>
          <label for="total_area" class="block text-sm font-medium text-gray-700 mb-2">
            Área Total (m²) *
          </label>
          <input
            id="total_area"
            type="number"
            step="0.01"
            v-model.number="localForm.total_area"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.total_area }"
            placeholder="Ex: 250.00"
          >
          <p v-if="errors.total_area" class="text-red-500 text-xs mt-1">{{ errors.total_area[0] }}</p>
        </div>
      </div>

      <!-- Linha 4: Condições e Mobília -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="property_condition" class="block text-sm font-medium text-gray-700 mb-2">
            Condições do Imóvel *
          </label>
          <select
            id="property_condition"
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
          <p v-if="errors.property_condition" class="text-red-500 text-xs mt-1">{{ errors.property_condition[0] }}</p>
        </div>

        <div>
          <label for="furniture_status" class="block text-sm font-medium text-gray-700 mb-2">
            Status da Mobília
          </label>
          <select
            id="furniture_status"
            v-model="localForm.furniture_status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.furniture_status }"
          >
            <option value="">Selecione...</option>
            <option value="mobiliado">Mobiliado</option>
            <option value="semi_mobiliado">Semi-mobiliado</option>
            <option value="nao_mobiliado">Não mobiliado</option>
          </select>
          <p v-if="errors.furniture_status" class="text-red-500 text-xs mt-1">{{ errors.furniture_status[0] }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResidentialForm',
  
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