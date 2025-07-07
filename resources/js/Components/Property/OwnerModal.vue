<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="$emit('close')"
      >
        <div 
          class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Adicionar Proprietário
          </h3>
          
          <form @submit.prevent="handleSubmit">
            <!-- Mensagem de contexto -->
            <div v-if="userFilterMessage" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-blue-700">{{ userFilterMessage }}</p>
                </div>
              </div>
            </div>

            <!-- Seleção de Usuário -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ selectionLabel }}
              </label>

              <!-- Perfil Proprietário: Auto-seleção -->
              <div v-if="isOwnerProfile && availableUsers.length === 1">
                <div class="p-3 bg-green-50 border border-green-200 rounded-md">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <div class="text-sm font-medium text-green-900">
                        {{ availableUsers[0].name }}
                      </div>
                      <div class="text-sm text-green-700">
                        {{ applyCpfCnpjMask(availableUsers[0].cpf_cnpj) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              

              <!-- Outros perfis: Busca com sugestões -->
              <div v-else class="relative">
                <input 
                  type="text" 
                  v-model="searchInput"
                  @input="handleSearchInput"
                  @focus="showSuggestions = true"
                  @blur="handleBlur"
                  :disabled="!canAddOwners"
                  :placeholder="searchPlaceholder"
                  class="w-full px-3 py-2.5 text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                />
                
                <!-- Lista de Sugestões -->
                <div 
                  v-if="showSuggestions && searchSuggestions.length > 0"
                  class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-60 overflow-y-auto"
                >
                  <ul class="py-1">
                    <li 
                      v-for="user in searchSuggestions" 
                      :key="user.id" 
                      @click="selectUserFromSuggestion(user)"
                      class="px-3 py-2 text-sm text-gray-900 cursor-pointer hover:bg-gray-100"
                    >
                      <div class="font-medium">
                        {{ user.name }}
                        <span v-if="user.id === currentUser?.id" class="text-blue-600">(Você)</span>
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ applyCpfCnpjMask(user.cpf_cnpj) }}
                      </div>
                    </li>
                  </ul>
                </div>

                <!-- Mensagem quando não há resultados -->
                <div 
                  v-if="showSuggestions && searchInput.length >= 2 && searchSuggestions.length === 0" 
                  class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200"
                >
                  <div class="p-3 text-sm text-gray-500 text-center">
                    Nenhum usuário encontrado
                  </div>
                </div>
              </div>
            </div>

            <!-- Usuário Selecionado -->
            <div v-if="selectedOwner.id" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                  <div class="text-sm font-medium text-green-900">
                    Usuário Selecionado:
                  </div>
                  <div class="text-sm text-green-700">
                    {{ selectedOwner.name }} - {{ applyCpfCnpjMask(selectedOwner.cpf_cnpj) }}
                    <span v-if="selectedOwner.id === currentUser?.id" class="font-medium">(Você)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Percentual -->
            <div class="mb-4">
              <label for="percent" class="block text-sm font-medium text-gray-700">
                Percentual de Propriedade *
              </label>
              <div class="mt-1 flex items-center">
                <input 
                  type="number" 
                  id="percent" 
                  v-model="selectedOwner.percent"
                  max="100" 
                  min="0.01"
                  step="0.01"
                  required
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="0.00"
                />
                <span class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 rounded-r-md sm:text-sm">
                  %
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Disponível para proprietários: {{ availablePercentage.toFixed(2) }}%
              </p>
            </div>

            <!-- Tipo de Propriedade -->
            <div class="mb-4">
              <label for="type_ownership" class="block text-sm font-medium text-gray-700">
                Tipo de Propriedade *
              </label>
              <select 
                id="type_ownership" 
                v-model="selectedOwner.type_ownership"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">Selecione o tipo</option>
                <option 
                  v-for="type in availableTypes" 
                  :key="type.id" 
                  :value="type.id"
                  :disabled="type.disabled"
                  :class="{ 'text-gray-400': type.disabled }"
                >
                  {{ type.name }}
                  <span v-if="type.id === 1 && type.remainingPercent < 100">
                    ({{ type.remainingPercent.toFixed(2) }}% disponível)
                  </span>
                </option>
              </select>
              
              <!-- Aviso quando tipo Proprietário não disponível -->
              <div v-if="!canAddProprietario" class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-2">
                    <p class="text-sm text-yellow-700">
                      Tipo "Proprietário" indisponível: já existem proprietários com 100% de participação.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Observações -->
            <div class="mb-6">
              <label for="observations" class="block text-sm font-medium text-gray-700">
                Observações
              </label>
              <textarea 
                id="observations" 
                v-model="selectedOwner.observations"
                rows="3" 
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="Observações adicionais..."
              />
            </div>

            <!-- Validação de Formulário -->
            <div v-if="formErrors.length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">
                    Corrija os seguintes erros:
                  </h3>
                  <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                    <li v-for="error in formErrors" :key="error">{{ error }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end space-x-3">
              <button 
                type="button" 
                @click="clearForm"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Limpar
              </button>
              <button 
                type="button" 
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                Cancelar
              </button>
              <button 
                type="submit"
                :disabled="!isFormValid || formErrors.length > 0"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Adicionar Proprietário
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, nextTick, onMounted } from 'vue'

// ====================================
// PROPS
// ====================================
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  selectedOwner: {
    type: Object,
    required: true
  },
  searchTerm: {
    type: String,
    default: ''
  },
  filteredUsers: {
    type: Array,
    default: () => []
  },
  typeOwners: {
    type: Array,
    default: () => []
  },
  canAddOwners: {
    type: Boolean,
    default: true
  },
  userFilterMessage: {
    type: String,
    default: ''
  },
  currentUser: {
    type: Object,
    default: null
  },
  existingOwners: {
    type: Array,
    default: () => []
  },
  availableUsers: {
    type: Array,
    default: () => []
  }
})


onMounted(() => {
  // Inicializa o campo de busca com o termo passado por props
  props.currentUser
})

// ====================================
// EMITS
// ====================================
const emit = defineEmits([
  'close', 
  'search', 
  'select-user', 
  'clear', 
  'submit'
])

// ====================================
// ESTADO LOCAL
// ====================================
const showSuggestions = ref(false)
const searchInput = ref('')

// ====================================
// COMPUTED PROPERTIES
// ====================================

// Verifica se é perfil proprietário
const isOwnerProfile = computed(() => {
  return props.currentUser?.profile_id === 1
})

// Label para seleção
const selectionLabel = computed(() => {
  const profile = props.currentUser?.profile_id
  
  switch (profile) {
    case 1:
      return 'Proprietário'
    case 2:
      return 'Buscar Proprietário Autorizado'
    case 3:
      return 'Buscar Proprietário'
    default:
      return 'Selecionar Usuário'
  }
})

// Placeholder para busca
const searchPlaceholder = computed(() => {
  return isOwnerProfile.value 
    ? 'Selecione da lista' 
    : 'Digite o nome para buscar'
})

// Texto de exibição na busca
const searchDisplayText = computed(() => {
  if (props.selectedOwner.id && props.selectedOwner.name) {
    return props.selectedOwner.name
  }
  return searchInput.value
})

// Sugestões de busca
const searchSuggestions = computed(() => {
  const term = searchInput.value.toLowerCase().trim()
  
  if (!term || term.length < 2) {
    return []
  }

  return props.availableUsers
    .filter(user => user.name.toLowerCase().includes(term))
    .slice(0, 10)
})

// Percentual disponível para proprietários
const availablePercentage = computed(() => {
  const usedPercentage = props.existingOwners.reduce((total, owner) => {
    const ownerTypeId = owner.type_ownership?.id || owner.type_ownership_id
    if (ownerTypeId === 1) { // Apenas tipo "Proprietário"
      return total + parseFloat(owner.percentage || owner.percent || 0)
    }
    return total
  }, 0)
  
  return Math.max(0, 100 - usedPercentage)
})

// Tipos de propriedade disponíveis
const availableTypes = computed(() => {
  return props.typeOwners.map(type => {
    if (type.id === 1) { // Tipo "Proprietário"
      const remainingPercent = availablePercentage.value
      return {
        ...type,
        remainingPercent,
        disabled: remainingPercent <= 0
      }
    }
    return { ...type, disabled: false }
  })
})

// Verifica se pode adicionar tipo proprietário
const canAddProprietario = computed(() => {
  return availablePercentage.value > 0
})

// Validação do formulário
const isFormValid = computed(() => {
  return props.selectedOwner.id && 
         props.selectedOwner.percent && 
         props.selectedOwner.type_ownership &&
         parseFloat(props.selectedOwner.percent) > 0 &&
         parseFloat(props.selectedOwner.percent) <= 100
})

// Erros de validação
const formErrors = computed(() => {
  const errors = []
  
  if (!props.selectedOwner.id) {
    errors.push('Selecione um usuário')
  }
  
  if (!props.selectedOwner.percent) {
    errors.push('Informe o percentual de propriedade')
  } else {
    const percent = parseFloat(props.selectedOwner.percent)
    if (percent <= 0) {
      errors.push('Percentual deve ser maior que 0')
    }
    if (percent > 100) {
      errors.push('Percentual não pode ser maior que 100%')
    }
    
    // Validação específica para tipo proprietário
    const typeId = parseInt(props.selectedOwner.type_ownership)
    if (typeId === 1 && percent > availablePercentage.value) {
      errors.push(`Percentual máximo para proprietários: ${availablePercentage.value.toFixed(2)}%`)
    }
  }
  
  if (!props.selectedOwner.type_ownership) {
    errors.push('Selecione o tipo de propriedade')
  }

  // Verifica duplicação
  if (props.selectedOwner.id) {
    const alreadyExists = props.existingOwners.some(owner => {
      const ownerId = owner.user?.id || owner.user_id || owner.id
      return ownerId == props.selectedOwner.id
    })
    
    if (alreadyExists) {
      errors.push('Este usuário já foi adicionado como proprietário')
    }
  }
  
  return errors
})

// ====================================
// WATCHERS
// ====================================

// Auto-seleciona usuário para perfil proprietário
watch(() => [props.show, isOwnerProfile.value], ([isShown, isOwner]) => {
  if (isShown && isOwner && props.availableUsers.length === 1 && !props.selectedOwner.id) {
    nextTick(() => {
      selectUser(props.availableUsers[0])
    })
  }
}, { immediate: true })

// Limpa busca quando modal fecha
watch(() => props.show, (isShown) => {
  if (!isShown) {
    searchInput.value = ''
    showSuggestions.value = false
  }
})

// ====================================
// MÉTODOS
// ====================================

const handleSearchInput = () => {
  // Se limpar o campo, limpa a seleção
  if (!searchInput.value.trim() && props.selectedOwner.id) {
    clearForm()
  }
  
  showSuggestions.value = searchInput.value.length >= 2
}

const handleBlur = () => {
  // Delay para permitir clique na sugestão
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const selectUserFromSuggestion = (user) => {
  selectUser(user)
  showSuggestions.value = false
  searchInput.value = ''
}

const selectUser = (user) => {
  emit('select-user', user)
}

const clearForm = () => {
  searchInput.value = ''
  showSuggestions.value = false
  emit('clear')
}

const handleSubmit = () => {
  if (isFormValid.value && formErrors.value.length === 0) {
    emit('submit')
  }
}

const applyCpfCnpjMask = (value) => {
  if (!value) return ''
  const numericValue = value.replace(/\D/g, '')

  if (numericValue.length <= 11) {
    return numericValue
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  } else {
    return numericValue
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>