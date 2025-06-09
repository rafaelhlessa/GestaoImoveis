<template>
  <div class="text-sm">
    <!-- Modo Criação -->
    <div v-if="!isEditMode" class="flex items-center space-x-2">
      <div v-if="!hasNewPhoto && !photoError" class="text-gray-500 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>Nenhuma foto selecionada</span>
      </div>
      <div v-else-if="hasNewPhoto && !photoError" class="text-green-600 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>Foto pronta para ser salva</span>
      </div>
      <div v-else-if="photoError" class="text-red-600 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>Erro na foto selecionada</span>
      </div>
    </div>

    <!-- Modo Edição -->
    <div v-else class="space-y-2">
      <!-- Sem foto atual e sem nova foto -->
      <div v-if="!hasCurrentPhoto && !hasNewPhoto && !photoError" class="flex items-center text-gray-500">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.44-1.01-5.904-2.617"/>
        </svg>
        <span>Nenhuma foto será salva</span>
      </div>

      <!-- Tem foto atual e não tem nova foto -->
      <div v-else-if="hasCurrentPhoto && !hasNewPhoto && !photoError" class="flex items-center text-blue-600">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>Foto atual será mantida</span>
      </div>

      <!-- Tem nova foto (substituindo atual ou adicionando) -->
      <div v-else-if="hasNewPhoto && !photoError" class="flex items-center text-green-600">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
        </svg>
        <span>{{ hasCurrentPhoto ? 'Nova foto será salva (substituindo atual)' : 'Nova foto será salva' }}</span>
      </div>

      <!-- Erro na nova foto -->
      <div v-else-if="photoError" class="flex items-center text-red-600">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>Erro na nova foto - {{ hasCurrentPhoto ? 'foto atual será mantida' : 'nenhuma foto será salva' }}</span>
      </div>
    </div>

    <!-- Badge adicional com resumo -->
    <div class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="badgeClass">
      {{ badgeText }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isEditMode: {
    type: Boolean,
    default: false
  },
  hasCurrentPhoto: {
    type: Boolean,
    default: false
  },
  hasNewPhoto: {
    type: Boolean,
    default: false
  },
  photoError: {
    type: String,
    default: ''
  }
})

// Computed para determinar classe do badge
const badgeClass = computed(() => {
  if (props.photoError) {
    return 'bg-red-100 text-red-800'
  }
  
  if (!props.isEditMode) {
    return props.hasNewPhoto ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
  }
  
  // Modo edição
  if (props.hasNewPhoto) {
    return 'bg-green-100 text-green-800'
  } else if (props.hasCurrentPhoto) {
    return 'bg-blue-100 text-blue-800'
  } else {
    return 'bg-gray-100 text-gray-800'
  }
})

// Computed para texto do badge
const badgeText = computed(() => {
  if (props.photoError) {
    return 'Erro'
  }
  
  if (!props.isEditMode) {
    return props.hasNewPhoto ? 'Nova foto' : 'Sem foto'
  }
  
  // Modo edição
  if (props.hasNewPhoto) {
    return props.hasCurrentPhoto ? 'Substituindo' : 'Adicionando'
  } else if (props.hasCurrentPhoto) {
    return 'Mantendo atual'
  } else {
    return 'Sem foto'
  }
})
</script>