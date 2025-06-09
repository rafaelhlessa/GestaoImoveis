<template>
  <div class="grid grid-cols-1 sm:grid-cols-12 gap-x-6 gap-y-8">
    <!-- Tipo de Propriedade -->
    <div class="sm:col-span-2 col-span-full">
      <label for="type_property" class="block text-sm font-medium text-gray-900">
        Tipo de Propriedade *
      </label>
      <select 
        id="type_property" 
        v-model.number="form.type_property" 
        required
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">Selecione</option>
        <option value="1">Urbana</option>
        <option value="2">Rural</option>
      </select>
    </div>

    <!-- Título de Propriedade -->
    <div class="sm:col-span-3 col-span-full">
      <label for="title_deed" class="block text-sm font-medium text-gray-900">
        Título de Propriedade *
      </label>
      <select 
        id="title_deed" 
        v-model.number="form.title_deed" 
        required
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">Selecione</option>
        <option value="1">Matrícula</option>
        <option value="2">Transcrição</option>
        <option value="3">Posse</option>
      </select>
    </div>

    <!-- Número/Descrição do Título -->
    <div class="sm:col-span-4 col-span-full">
      <label for="title_number" class="block text-sm font-medium text-gray-900">
        {{ getTitleLabel }}
      </label>
      
      <!-- Para Matrícula (com cartório) -->
      <div v-if="form.title_deed === 1" class="mt-2 flex">
        <input 
          type="text" 
          v-model="form.title_deed_number"
          placeholder="Número da Matrícula"
          class="flex-1 rounded-l-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 border-r-0 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
        <input 
          type="text" 
          v-model="form.other"
          placeholder="Cartório"
          class="flex-1 rounded-r-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 border-l-0 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>
      
      <!-- Para outros tipos -->
      <div v-else class="mt-2">
        <input 
          type="text" 
          v-model="form.title_deed_number"
          :placeholder="getTitlePlaceholder"
          class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>
    </div>

    <!-- Área -->
    <div class="sm:col-span-3 col-span-full">
      <label for="area" class="block text-sm font-medium text-gray-900">
        Área
      </label>
      <div class="mt-2 flex">
        <input 
          type="number" 
          v-model="form.area"
          step="0.01"
          placeholder="0.00"
          class="flex-1 rounded-l-md bg-white px-2.5 py-1.5 text-base text-gray-900 border border-gray-300 border-r-0 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
        <select 
          v-model="form.unit"
          class="w-48 rounded-r-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 border-l-0 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">Unidade</option>
          <option value="m2">m²</option>
          <option value="ha">Ha</option>
        </select>
      </div>
    </div>

    <!-- Endereço -->
    <div class="sm:col-span-6 col-span-full">
      <label for="address" class="block text-sm font-medium text-gray-900">
        Endereço
      </label>
      <input 
        type="text" 
        id="address" 
        v-model="form.address"
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        placeholder="Digite o endereço completo"
      />
    </div>

    <!-- Município/Estado -->
    <div class="sm:col-span-6 col-span-full">
      <label for="city" class="block text-sm font-medium text-gray-900">
        Município/Estado
      </label>
      <div class="mt-2 relative">
        <input 
          id="city" 
          type="text" 
          v-model="form.city"
          @focus="showSuggestions = true" 
          @blur="$emit('close-suggestions')"
          @input="$emit('filter-cities', $event.target.value)"
          placeholder="Digite o nome da cidade"
          class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
        
        <!-- Sugestões de Cidades -->
        <div 
          v-if="showSuggestions && filteredCities.length > 0"
          class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-60 overflow-y-auto"
        >
          <ul class="py-1">
            <li 
              v-for="city in filteredCities" 
              :key="city.id" 
              @click="$emit('select-city', city)"
              class="px-3 py-2 text-base text-gray-900 cursor-pointer hover:bg-gray-100"
            >
              {{ city.nome }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Distrito -->
    <div class="sm:col-span-4 col-span-full">
      <label for="district" class="block text-sm font-medium text-gray-900">
        Distrito
      </label>
      <input 
        type="text" 
        id="district" 
        v-model="form.district"
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      />
    </div>

    <!-- Bairro/Localidade -->
    <div class="sm:col-span-4 col-span-full">
      <label for="locality" class="block text-sm font-medium text-gray-900">
        {{ form.type_property === 1 ? 'Bairro' : 'Localidade' }}
      </label>
      <input 
        type="text" 
        id="locality" 
        v-model="form.locality"
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      />
    </div>

    <!-- Apelido -->
    <div class="sm:col-span-4 col-span-full">
      <label for="nickname" class="block text-sm font-medium text-gray-900">
        Apelido/Nome Popular
      </label>
      <input 
        type="text" 
        id="nickname" 
        v-model="form.nickname"
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        placeholder="Como a propriedade é conhecida"
      />
    </div>

    <!-- Observações -->
    <div class="col-span-full">
      <label for="about" class="block text-sm font-medium text-gray-900">
        Observações
      </label>
      <textarea 
        id="about" 
        v-model="form.about"
        rows="3"
        class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        placeholder="Informações adicionais sobre a propriedade..."
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  allCities: {
    type: Array,
    default: () => []
  },
  filteredCities: {
    type: Array,
    default: () => []
  },
  showSuggestions: {
    type: Boolean,
    default: false
  }
})

defineEmits(['filter-cities', 'select-city', 'close-suggestions'])

const getTitleLabel = computed(() => {
  if (!props.form.title_deed) return 'Número/Descrição'
  
  switch (props.form.title_deed) {
    case 1: return 'Nº Matrícula / Cartório'
    case 2: return 'Nº Transcrição'
    case 3: return 'Descrição da Posse'
    default: return 'Número/Descrição'
  }
})

const getTitlePlaceholder = computed(() => {
  if (!props.form.title_deed) return 'Digite o número ou descrição'
  
  switch (props.form.title_deed) {
    case 2: return 'Digite o número da transcrição'
    case 3: return 'Descreva a situação de posse'
    default: return 'Digite o número ou descrição'
  }
})
</script>