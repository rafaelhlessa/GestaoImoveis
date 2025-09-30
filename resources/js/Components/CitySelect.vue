<script setup>
  import { ref, onMounted, computed } from 'vue'
  import axios from 'axios'
  import InputLabel from '@/Components/InputLabel.vue'
  import TextInput from '@/Components/TextInput.vue'

  const props = defineProps({
    modelValue: String,
    modelValueId: {
      type: [String, Number],
      default: null
    },
    label: { type: String, default: 'Cidade' },
    placeholder: { type: String, default: 'Digite a cidade' },
    // ✅ Adicione esta propriedade para o ID do input
    id: { type: String, default: 'city-select' }
  })
  const emits = defineEmits(['update:modelValue', 'update:modelValueId'])

  const query = ref(props.modelValue || '')
  const showSuggestions = ref(false)
  const allCities = ref([])
  const filteredCities = ref([])

  // ✅ Computed para gerar um ID único se necessário
  const inputId = computed(() => props.id || `city-select-${Math.random().toString(36).substr(2, 9)}`)

  // Carrega lista de cidades na montagem
  onMounted(async () => {
    try {
      const { data } = await axios.get(
        'https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome'
      )
      allCities.value = data
    } catch (e) {
      console.error('Erro carregando cidades', e)
    }
  })

  // Filtra cidades com base na query
  const onInput = () => {
    const q = query.value.trim().toLowerCase()
    if (q.length >= 3) {
      filteredCities.value = allCities.value.filter(city =>
        city.nome.toLowerCase().includes(q)
      )
      showSuggestions.value = filteredCities.value.length > 0
    } else {
      showSuggestions.value = false
    }
    emits('update:modelValue', query.value)

    // ✅ Limpa o ID quando o usuário digita (cidade não selecionada)
    if (q.length < 3) {
      emits('update:modelValueId', null)
    }
  }

  // Seleciona cidade e emite valores
  const selectCity = city => {
    query.value = city.nome
    emits('update:modelValue', city.nome)
    emits('update:modelValueId', Number(city.id))
    showSuggestions.value = false
  }

  const closeSuggestions = () => setTimeout(() => (showSuggestions.value = false), 200)
</script>

<template>
    <div class="relative">
      <!-- ✅ Substitua idName por inputId -->
      <InputLabel :for="inputId" :value="label" />
      <TextInput
        :id="inputId"
        v-model="query"
        type="text"
        class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
        :placeholder="placeholder"
        @input="onInput"
        @focus="showSuggestions = filteredCities.length > 0"
        @blur="closeSuggestions"
        autocomplete="off"
      />
      <ul
        v-if="showSuggestions"
        class="absolute z-10 bg-white dark:bg-gray-800 shadow-lg mt-1 max-h-48 overflow-auto w-full rounded-lg border border-gray-300 dark:border-gray-600"
      >
        <li
          v-for="city in filteredCities"
          :key="city.id"
          @mousedown.prevent="selectCity(city)"
          class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-600 last:border-b-0"
        >
          {{ city.nome }}
        </li>
      </ul>
    </div>
</template>
