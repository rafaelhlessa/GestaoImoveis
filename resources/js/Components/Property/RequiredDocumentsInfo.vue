<template>
  <div class="mb-6">
    <div class="bg-red-600 border border-red-800 rounded-lg p-4">
      <h3 class="text-white font-semibold mb-2">
        📋 Documentos Obrigatórios para {{ propertyTypeText }}
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
  propertyType: {
    type: Number,
    required: true,
    validator: (value) => [1, 2].includes(value)
  }
})

const propertyTypeText = computed(() => {
  return props.propertyType === 2 ? 'Propriedades Rurais' : 'Imóveis Urbanos'
})

const requiredDocuments = computed(() => {
  if (props.propertyType === 2) {
    // Propriedades Rurais
    return [
      'Título de propriedade (matrícula/transcrição/outro)',
      'CCIR (Certificado de Cadastro de Imóvel Rural)',
      'ITR (Imposto sobre a Propriedade Territorial Rural)',
      'CAR (Cadastro Ambiental Rural)',
      'Georreferenciamento (obrigatório a partir de novembro de 2025)'
    ]
  } else {
    // Imóveis Urbanos
    return [
      'Título de propriedade (escritura ou matrícula)',
      'IPTU (Imposto Predial e Territorial Urbano)',
      'Certidão negativa de débitos municipais'
    ]
  }
})
</script>