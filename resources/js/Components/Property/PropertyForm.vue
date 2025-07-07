<template>
  <AuthenticatedLayout>
    <Head :title="pageTitle" />
    
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ pageTitle }}
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="bg-white rounded-lg shadow p-8">
              
              <!-- Formulário Principal -->
              <form @submit.prevent="handleSubmit">
                <div class="space-y-12">
                  
                  <!-- Seção Principal -->
                  <div class="border-b border-gray-900/10 pb-12">
                    <div class="flex justify-between items-start mb-6">
                      <div>
                        <h2 class="text-base font-semibold text-gray-900">
                          {{ isEditMode ? 'Editar' : 'Cadastrar' }} Propriedade
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                          Preencha as informações da propriedade
                        </p>
                      </div>
                      
                      <!-- Toggle Ativo/Inativo -->
                      <label class="inline-flex items-center cursor-pointer">
                        <input 
                          type="checkbox" 
                          v-model="form.is_active"
                          class="sr-only peer"
                        >
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900">
                          {{ form.is_active ? "Propriedade Ativa" : "Propriedade Inativa" }}
                        </span>
                      </label>
                    </div>

                    <!-- Tabela de Proprietários -->
                    <OwnersTable
                      :owners="owners"
                      :type-owners="typeOwners"
                      @add-owner="showModalOwner = true"
                      @remove-owner="removeOwner"
                    />

                    <!-- Campos do Formulário -->
                    <PropertyFormFields 
                      :form="form"
                      :all-cities="allCities"
                      :filtered-cities="filteredCities"
                      :show-suggestions="showSuggestions"
                      @filter-cities="filterCities"
                      @select-city="selectCity"
                      @close-suggestions="closeSuggestions"
                    />
                  </div>

                  <!-- ✅ SEÇÃO DE FOTO MELHORADA -->
                  <div class="border-b border-gray-900/10 pb-12">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Foto da Propriedade</h3>
                    
                    <!-- Foto Atual (em modo de edição) -->
                    <div v-if="isEditMode && currentPhotoExists && !hasNewPhoto" class="mb-6">
                      <div class="flex items-start space-x-4">
                        <div class="relative">
                          <img 
                            :src="getCurrentPhotoUrl()" 
                            alt="Foto atual da propriedade"
                            class="w-32 h-32 object-cover rounded-lg border-2 border-blue-300 shadow-sm"
                            @error="handleImageError"
                          />
                          <div class="absolute -top-2 -left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                            Atual
                          </div>
                        </div>
                        <div class="flex-1">
                          <h4 class="text-sm font-medium text-gray-900 mb-2">Foto Atual</h4>
                          <p class="text-sm text-blue-600 mb-3">
                            ✅ Esta foto será mantida se você não selecionar uma nova
                          </p>
                          <button
                            type="button"
                            @click="handleRemoveCurrentPhoto"
                            class="text-sm text-red-600 hover:text-red-800 underline"
                          >
                            🗑️ Remover foto atual
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Nova Foto Selecionada -->
                    <div v-if="hasNewPhoto && !photoError" class="mb-6">
                      <div class="flex items-start space-x-4">
                        <div class="relative">
                          <img 
                            :src="form.file_photo" 
                            alt="Nova foto selecionada"
                            class="w-32 h-32 object-cover rounded-lg border-2 border-green-300 shadow-sm"
                          />
                          <div class="absolute -top-2 -left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                            Nova
                          </div>
                        </div>
                        <div class="flex-1">
                          <h4 class="text-sm font-medium text-gray-900 mb-2">Nova Foto</h4>
                          <p class="text-sm text-green-600 mb-1">
                            🔄 Esta nova foto será salva
                          </p>
                          <p class="text-xs text-gray-500 mb-3" v-if="selectedPhoto">
                            Arquivo: {{ selectedPhoto.name }} ({{ formatFileSize(selectedPhoto.size) }})
                          </p>
                          <button
                            type="button"
                            @click="handleCancelNewPhoto"
                            class="text-sm text-red-600 hover:text-red-800 underline"
                          >
                            ❌ Cancelar nova foto
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Upload de Nova Foto -->
                    <div class="space-y-4">
                      <label for="document-photo" class="block text-sm font-medium text-gray-700">
                        {{ getPhotoUploadLabel() }}
                      </label>
                      
                      <div class="flex items-center justify-center w-full">
                        <label for="document-photo" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:hover:bg-gray-800 dark:bg-gray-700 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500">
                          <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                              <span class="font-semibold">Clique para enviar</span> ou arraste e solte
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                              PNG, JPG, JPEG, GIF, WEBP (máx. 3MB)
                            </p>
                          </div>
                          <input 
                            id="document-photo" 
                            type="file" 
                            class="hidden" 
                            ref="fileInput"
                            @change="handlePhotoUpload" 
                            accept="image/*"
                          />
                        </label>
                      </div>
                    </div>

                    <!-- Status da Foto -->
                    <div class="mt-4">
                      <PhotoStatusIndicator 
                        :is-edit-mode="isEditMode"
                        :has-current-photo="currentPhotoExists"
                        :has-new-photo="hasNewPhoto"
                        :photo-error="photoError"
                      />
                    </div>

                    <!-- Erro de Upload -->
                    <div v-if="photoError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                      <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                          <h3 class="text-sm font-medium text-red-800">Erro no upload da foto</h3>
                          <p class="text-sm text-red-700 mt-1">{{ photoError }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Seção de Documentos -->
                  <DocumentsTable
                    :documents="documents"
                    :property-type="form.type_property"
                    :mode="isEditMode ? 'edit' : 'create'"
                    @add-document="showModalDocument = true"
                    @remove-document="removeDocument"
                    @view-document="viewDocument"
                  />
                </div>

                <!-- Botões de Ação -->
                <div class="mt-6 flex items-center justify-end gap-x-6">
                  <button 
                    type="button" 
                    @click="goBack"
                    class="text-sm font-semibold text-gray-900 hover:text-gray-700"
                  >
                    Cancelar
                  </button>
                  <button 
                    type="submit"
                    :disabled="form.processing || !!photoError"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:opacity-50"
                  >
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>{{ isEditMode ? 'Atualizar' : 'Salvar' }}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Proprietário -->
    <OwnerModal
      :show="showModalOwner"
      :selected-owner="selectedOwner"
      :search-term="searchTerm"
      :filtered-users="filteredUsers"
      :type-owners="typeOwners"
      :current-user="currentUser"
      :existing-owners="owners"
      :can-add-owners="canAddOwners"
      :user-filter-message="getUserFilterMessage"
      :available-users="availableUsers"
      @close="closeOwnerModal"
      @search="handleOwnerSearch"
      @select-user="selectOwner"
      @clear="clearOwner"
      @submit="handleAddOwner"
    />

    <!-- Modal de Documento -->
    <DocumentModal
      :show="showModalDocument"
      :new-document="newDocument"
      :doc-date="docDate"
      @close="closeDocumentModal"
      @submit="handleAddDocument"
      @upload="handleDocumentUpload"
      @toggle-date="docDate = $event"
    />

    <!-- Sistema de Alertas -->
    <div 
      v-if="alert.show" 
      :class="['fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg border transition-all duration-300', alertClass]"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <!-- Ícone baseado no tipo -->
          <svg v-if="alert.type === 'success'" class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <svg v-else-if="alert.type === 'error'" class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <svg v-else-if="alert.type === 'warning'" class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium">{{ alert.message }}</p>
        </div>
        <div class="ml-4 flex-shrink-0">
          <button 
            @click="alert.show = false"
            class="inline-flex text-sm hover:opacity-75"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePropertyForm } from '@/composables/usePropertyForm'

// Componentes
import OwnersTable from '@/Components/Property/OwnersTable.vue'
import OwnerModal from '@/Components/Property/OwnerModal.vue'
import DocumentsTable from '@/Components/Property/DocumentsTable.vue'
import DocumentModal from '@/Components/Property/DocumentModal.vue'
import PropertyFormFields from '@/Components/Property/PropertyFormFields.vue'
import PhotoStatusIndicator from '@/Components/Property/PhotoStatusIndicator.vue'

// ====================================
// PROPS (mantidos iguais)
// ====================================
const props = defineProps({
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  },
  property: {
    type: Object,
    default: null
  },
  owners: {
    type: Array,
    default: () => []
  },
  documents: {
    type: Array,
    default: () => []
  },
  typeOwners: {
    type: Array,
    default: () => []
  },
  users: {
    type: Array,
    default: () => []
  },
  authorizations: {
    type: Array,
    default: () => []
  },
  currentUser: {
    type: Object,
    default: null
  }
})

// ====================================
// ESTADO LOCAL (mantido com adições)
// ====================================
const photoError = ref('')
const selectedPhoto = ref(null)
const fileInput = ref(null) // ✅ Adicionado
const MAX_PHOTO_SIZE = 3 * 1024 * 1024

// ====================================
// COMPOSABLE PRINCIPAL (adicionados novos métodos)
// ====================================
const {
  // Estado principal
  form,
  isEditMode,
  currentUser,

  // Proprietários
  owners,
  selectedOwner,
  searchTerm,
  filteredUsers,
  availableUsers,
  canAddOwners,
  getUserFilterMessage,
  availableOwnershipTypes,

  // Documentos
  documents,
  newDocument,
  docDate,

  // Modais
  showModalOwner,
  showModalDocument,

  // Cidades
  allCities,
  filteredCities,
  showSuggestions,

  // Alertas
  alert,
  alertClass,

  // Métodos - Proprietários
  searchUsers,
  selectOwner,
  clearOwner,
  updateSelectedOwner,
  addOwner,
  removeOwner,

  // Métodos - Documentos
  handleDocumentUpload,
  handleAddDocument,
  removeDocument,

  // Métodos - Cidades
  filterCities,
  closeSuggestions,

  // ✅ Métodos de foto (adicionados)
  handleFileChange,
  removeCurrentPhoto,
  cancelNewPhoto,

  // Métodos - Formulário
  submitForm,
  showAlert
} = usePropertyForm(props)

// ====================================
// COMPUTED (adicionados novos)
// ====================================
const pageTitle = computed(() => {
  return isEditMode.value ? 'Editar Propriedade' : 'Cadastrar Propriedade'
})

const typeOwners = computed(() => props.typeOwners || [])

// ✅ Novos computeds para foto
const currentPhotoExists = computed(() => {
  return isEditMode.value && props.property?.file_photo && props.property.file_photo.trim() !== ''
})

const hasNewPhoto = computed(() => {
  return form.file_photo && form.file_photo !== props.property?.file_photo
})

// ====================================
// MÉTODOS PARA FOTO (novos métodos organizados)
// ====================================

/**
 * Gera URL da foto atual
 */
const getCurrentPhotoUrl = () => {
  const photo = props.property?.file_photo
  if (!photo) return null
  
  if (photo.startsWith('data:image/')) {
    return photo
  }
  
  return `/storage/${photo}`
}

/**
 * Determina o label do upload baseado no estado
 */
const getPhotoUploadLabel = () => {
  if (isEditMode.value) {
    if (currentPhotoExists.value && !hasNewPhoto.value) {
      return 'Alterar Foto da Propriedade'
    } else if (hasNewPhoto.value) {
      return 'Substituir Nova Foto'
    } else {
      return 'Adicionar Foto da Propriedade'
    }
  }
  return 'Adicionar Foto da Propriedade'
}

/**
 * Manipula erro de carregamento de imagem
 */
const handleImageError = (event) => {
  console.error('Erro ao carregar imagem:', event)
  showAlert('Erro ao carregar foto atual', 'warning')
}

/**
 * Remove foto atual
 */
const handleRemoveCurrentPhoto = () => {
  removeCurrentPhoto() // Chama método da composable
  selectedPhoto.value = null
  photoError.value = ''
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

/**
 * Cancela nova foto
 */
const handleCancelNewPhoto = () => {
  cancelNewPhoto() // Chama método da composable
  selectedPhoto.value = null
  photoError.value = ''
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

/**
 * Formata tamanho do arquivo
 */
const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * ✅ Manipula upload de foto (versão simplificada e corrigida)
 */
const handlePhotoUpload = (event) => {
  const file = event.target.files[0]
  
  // Limpa estado anterior
  photoError.value = ''
  selectedPhoto.value = null
  
  if (!file) {
    // Se não há arquivo, usa método da composable
    handleFileChange(event)
    return
  }
  
  // Validações
  if (file.size > MAX_PHOTO_SIZE) {
    photoError.value = `Foto muito grande. Tamanho máximo: 3MB. Arquivo: ${formatFileSize(file.size)}`
    event.target.value = ''
    showAlert(photoError.value, 'error', 5000)
    return
  }
  
  if (!file.type.startsWith('image/')) {
    photoError.value = 'Selecione apenas arquivos de imagem (JPG, PNG, GIF, WEBP)'
    event.target.value = ''
    showAlert(photoError.value, 'error', 5000)
    return
  }
  
  // Se passou na validação
  selectedPhoto.value = file
  handleFileChange(event) // Chama método da composable
  
  showAlert(`Foto "${file.name}" selecionada com sucesso!`, 'success', 3000)
}

// ====================================
// MÉTODOS DO COMPONENTE (mantidos iguais)
// ====================================

const handleOwnerSearch = (term) => {
  searchTerm.value = term
  searchUsers(term)
}

const handleAddOwner = () => {
  const success = addOwner()
  if (success) {
    showModalOwner.value = false
  }
}

const closeOwnerModal = () => {
  showModalOwner.value = false
  clearOwner()
}

const closeDocumentModal = () => {
  showModalDocument.value = false
  Object.assign(newDocument, {
    name: '',
    date: '',
    show: true,
    file: null,
    file_name: ''
  })
}

const selectCity = (city) => {
  form.city = city.nome
  form.city_id = city.id
  showSuggestions.value = false
}

const viewDocument = (documentId) => {
  window.open(`/property/document/${documentId}`, '_blank')
}

const handleSubmit = () => {
  console.log('Validando foto antes de submeter...')
  if (photoError.value) {
    showAlert('Por favor, corrija os erros antes de continuar.', 'error')
    return
  }
  
  const propertyId = props.property?.id
  submitForm(propertyId)
}

const goBack = () => {
  if (isEditMode.value) {
    router.visit(`/property/${props.property.id}`)
  } else {
    router.visit('/property')
  }
}
</script>

<style scoped>
/* Estilos específicos do componente se necessário */
</style>