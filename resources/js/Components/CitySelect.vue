<script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'
  import InputLabel from '@/Components/InputLabel.vue'
  import TextInput from '@/Components/TextInput.vue'
  
  const props = defineProps({
    modelValue: String,
    modelValueId: Number,
    label: { type: String, default: 'Cidade' },
    placeholder: { type: String, default: 'Digite a cidade' }
  })
  const emits = defineEmits(['update:modelValue', 'update:modelValueId'])
  
  const query = ref(props.modelValue || '')
  const showSuggestions = ref(false)
  const allCities = ref([])
  const filteredCities = ref([])
  
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
  }
  
  // Seleciona cidade e emite valores
  const selectCity = city => {
    query.value = city.nome
    emits('update:modelValue', city.nome)
    emits('update:modelValueId', city.id)
    showSuggestions.value = false
  }
  
  const closeSuggestions = () => setTimeout(() => (showSuggestions.value = false), 200)
</script>
  
<template>
    <div class="relative">
      <InputLabel :for="idName" :value="label" />
      <TextInput
        :id="idName"
        v-model="query"
        type="text"
        class="mt-1 block w-full"
        :placeholder="placeholder"
        @input="onInput"
        @focus="showSuggestions = filteredCities.length > 0"
        @blur="closeSuggestions"
        autocomplete="off"
      />
      <ul
        v-if="showSuggestions"
        class="absolute z-10 bg-white shadow mt-1 max-h-40 overflow-auto w-full rounded"
      >
        <li
          v-for="city in filteredCities"
          :key="city.id"
          @mousedown.prevent="selectCity(city)"
          class="px-4 py-2 hover:bg-gray-200 cursor-pointer"
        >
          {{ city.nome }}
        </li>
      </ul>
    </div>
  </template>
  