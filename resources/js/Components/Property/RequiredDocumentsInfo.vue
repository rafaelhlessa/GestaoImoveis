<template>
  <div class="mb-6">
    <div class="bg-red-600 border border-red-800 rounded-lg p-4">
      <h3 class="text-white font-semibold mb-2">
        📋 Documentos Obrigatórios para {{ propertyCategoryText }}
      </h3>
      <ul class="text-white text-sm space-y-1">
        <li v-for="doc in requiredDocuments" :key="doc" class="flex items-start">
          <span class="text-red-200 mr-2">•</span>
          <span>{{ doc }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  propertyCategory: {
    type: [String, Number],
    required: true
  }
})

const category = computed(() => {
  if (typeof props.propertyCategory === 'number') {
    return props.propertyCategory === 2 ? 'rural' : 'urban'
  }
  return props.propertyCategory || 'urban'
})

const requiredDocuments = computed(() => {
  if (category.value === 'rural') {
    return [
      'Título de propriedade (matrícula/transcrição/outro)',
      'CCIR (Certificado de Cadastro de Imóvel Rural)',
      'ITR (Imposto sobre a Propriedade Territorial Rural)',
      'CAR (Cadastro Ambiental Rural)',
      'Georreferenciamento (obrigatório a partir de novembro de 2025)'
    ]
  }
  if (category.value === 'industrial') {
    return [
      'Título de propriedade (escritura ou matrícula)',
      'Licença de Operação (se aplicável)',
      'Alvará de Funcionamento',
      'Laudos e Certificações ambientais (quando exigidos)'
    ]
  }
  // Urbanas por padrão
  return [
    'Título de propriedade (escritura ou matrícula)',
    'IPTU (Imposto Predial e Territorial Urbano)',
    'Certidão negativa de débitos municipais'
  ]
})

const propertyCategoryText = computed(() => {
  if (category.value === 'rural') return 'Propriedades Rurais'
  if (category.value === 'industrial') return 'Propriedades Industriais'
  return 'Imóveis Urbanos'
})
</script>