<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="$emit('close')"
      >
        <div 
          class="bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">
              Adicionar Co-proprietário
            </h3>
            <button 
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <!-- Informação sobre co-proprietários -->
          <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800">Sobre Co-proprietários</h4>
                <div class="mt-2 text-sm text-blue-700">
                  <p>• Co-proprietários são pessoas que não estão cadastradas no sistema</p>
                  <p>• Você pode adicionar múltiplos co-proprietários de uma vez</p>
                  <p>• O percentual total (proprietários + co-proprietários) não pode exceder 100%</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Gerenciador de Co-proprietários -->
          <CoOwnersManager 
            v-model="localCoOwners"
            :type-owners="typeOwners"
            :used-percentage="usedPercentage"
            :compact="false"
          />

          <!-- Botões de Ação -->
          <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <button
              @click="$emit('close')"
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancelar
            </button>
            <button
              @click="handleSubmit"
              type="button"
              :disabled="localCoOwners.length === 0"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Adicionar {{ localCoOwners.length }} Co-proprietário{{ localCoOwners.length !== 1 ? 's' : '' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import CoOwnersManager from '@/Components/Property/CoOwnersManager.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  typeOwners: {
    type: Array,
    default: () => []
  },
  existingOwners: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submit'])

const localCoOwners = ref([])

// Computed para calcular percentual usado pelos proprietários existentes
const usedPercentage = computed(() => {
  return props.existingOwners.reduce((total, owner) => {
    const percentage = owner.percentage || owner.percent || owner.pivot?.percentage || 0
    return total + parseFloat(percentage)
  }, 0)
})

// Método para submeter os co-proprietários
const handleSubmit = () => {
  if (localCoOwners.value.length > 0) {
    emit('submit', {
      type: 'co-owners',
      data: localCoOwners.value
    })
    
    // Limpa os co-proprietários locais
    localCoOwners.value = []
  }
}

// Limpa os dados quando o modal fecha
watch(() => props.show, (newValue) => {
  if (!newValue) {
    localCoOwners.value = []
  }
})
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white, .modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white, .modal-leave-to .bg-white {
  transform: scale(0.9);
}
</style>
