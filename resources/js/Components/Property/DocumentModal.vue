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
            Adicionar Documento
          </h3>
          
          <form @submit.prevent="handleSubmit">
            <!-- Nome do Documento -->
            <div class="mb-4">
              <label for="document-name" class="block text-sm font-medium text-gray-700">
                Nome do Documento *
              </label>
              <input 
                type="text" 
                id="document-name" 
                v-model="documentData.name"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="Ex: Escritura, IPTU, etc."
              />
            </div>

            <!-- Documento possui validade -->
            <div class="mb-4">
              <fieldset>
                <legend class="text-sm font-semibold text-gray-900">
                  Documento possui data de vencimento?
                </legend>
                <div class="mt-2 space-y-2">
                  <div class="flex items-center">
                    <input 
                      id="has-date-yes" 
                      name="has-date" 
                      type="radio" 
                      :checked="localDocDate === true"
                      @change="updateDocDate(true)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    />
                    <label for="has-date-yes" class="ml-2 text-sm text-gray-700">
                      Sim
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input 
                      id="has-date-no" 
                      name="has-date" 
                      type="radio" 
                      :checked="localDocDate === false"
                      @change="updateDocDate(false)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    />
                    <label for="has-date-no" class="ml-2 text-sm text-gray-700">
                      Não
                    </label>
                  </div>
                </div>
              </fieldset>
            </div>

            <!-- Data de Vencimento -->
            <div v-if="localDocDate === true" class="mb-4">
              <label for="document-date" class="block text-sm font-medium text-gray-700">
                Data de Vencimento
              </label>
              <input 
                type="date" 
                id="document-date" 
                v-model="documentData.date"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <!-- Disponibilizar Documento -->
            <div class="mb-4">
              <fieldset>
                <legend class="text-sm font-semibold text-gray-900">
                  Disponibilizar para prestadores de serviço?
                </legend>
                <p class="mt-1 text-sm text-gray-600">
                  Marque <strong>Sim</strong> para permitir que prestadores de serviço autorizados vejam este documento.
                </p>
                <div class="mt-3 space-y-2">
                  <div class="flex items-center">
                    <input 
                      id="show-yes" 
                      name="show-document" 
                      type="radio" 
                      :checked="documentData.show === true"
                      @change="updateDocument('show', true)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    />
                    <label for="show-yes" class="ml-2 text-sm text-gray-700">
                      Sim
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input 
                      id="show-no" 
                      name="show-document" 
                      type="radio" 
                      :checked="documentData.show === false"
                      @change="updateDocument('show', false)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    />
                    <label for="show-no" class="ml-2 text-sm text-gray-700">
                      Não
                    </label>
                  </div>
                </div>
              </fieldset>
            </div>

            <!-- Upload de Arquivo -->
            <div class="mb-6">
              <label for="document-file" class="block text-sm font-medium text-gray-700">
                Arquivo *
              </label>
              <div class="mt-1">
                <input 
                  type="file" 
                  id="document-file" 
                  @change="handleFileUpload"
                  accept=".pdf,.doc,.docx,.kml,.kmz"
                  required
                  class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Formatos aceitos: PDF, DOC, DOCX, KML, KMZ (máx. 10MB)
              </p>
              
              <!-- Mensagem de erro para arquivo muito grande -->
              <div v-if="fileError" class="mt-2 p-2 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-center">
                  <svg class="h-4 w-4 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-sm text-red-800">{{ fileError }}</span>
                </div>
              </div>
              
              <!-- Preview do arquivo selecionado -->
              <div v-if="documentData.file_name && !fileError" class="mt-2 p-2 bg-green-50 border border-green-200 rounded-md">
                <div class="flex items-center">
                  <DocumentIcon class="h-4 w-4 text-green-600 mr-2" />
                  <span class="text-sm text-green-800">{{ documentData.file_name }}</span>
                  <span class="text-xs text-green-600 ml-2">({{ formatFileSize(documentData.file?.size) }})</span>
                </div>
              </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end space-x-3">
              <button 
                type="button" 
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                Cancelar
              </button>
              <button 
                type="submit"
                :disabled="!isFormValid"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Adicionar Documento
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { DocumentIcon } from '@heroicons/vue/24/outline'
import { useCurrentUser } from '@/composables/useCurrentUser'

const currentUser = useCurrentUser()

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  newDocument: {
    type: Object,
    required: true
  },
  docDate: {
    type: [Boolean, String, Number],
    default: false
  }
})

const emit = defineEmits(['close', 'submit', 'upload', 'toggle-date'])

// Estado local para evitar modificar props
const localDocDate = ref(props.docDate)
const documentData = ref({
  name: '',
  date: '',
  show: true,
  file: null,
  file_name: ''
})

// Estado para controle de erro de arquivo
const fileError = ref('')

// Constante para tamanho máximo do arquivo (10MB em bytes)
const MAX_FILE_SIZE = 10 * 1024 * 1024

// Sincroniza com props quando mudarem
watch(() => props.newDocument, (newValue) => {
  documentData.value = { ...newValue }
}, { immediate: true, deep: true })

watch(() => props.docDate, (newValue) => {
  localDocDate.value = newValue
}, { immediate: true })

const isFormValid = computed(() => {
  return documentData.value.name && 
         documentData.value.file && 
         documentData.value.file_name &&
         !fileError.value
})

const updateDocument = (field, value) => {
  documentData.value[field] = value
}

const updateDocDate = (value) => {
  localDocDate.value = value
  emit('toggle-date', value)
  
  // Se mudou para false, limpa a data
  if (!value) {
    documentData.value.date = ''
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  
  // Limpa erro anterior
  fileError.value = ''
  
  if (!file) {
    // Se não há arquivo, limpa os dados
    documentData.value.file = null
    documentData.value.file_name = ''
    return
  }
  
  // Valida o tamanho do arquivo
  if (file.size > MAX_FILE_SIZE) {
    fileError.value = `Arquivo muito grande. Tamanho máximo permitido: 10MB. Arquivo selecionado: ${formatFileSize(file.size)}`
    
    // Limpa o input
    event.target.value = ''
    documentData.value.file = null
    documentData.value.file_name = ''
    return
  }
  
  // Se passou na validação, atualiza os dados
  documentData.value.file = file
  documentData.value.file_name = file.name
  
  // Emite para o componente pai tratar o upload
  emit('upload', event)
}

const handleSubmit = () => {
  if (isFormValid.value) {
    // Emite o documento completo para o pai
    emit('submit', { ...documentData.value })
    
    // Limpa o formulário
    documentData.value = {
      name: '',
      date: '',
      show: true,
      file: null,
      file_name: ''
    }
    fileError.value = ''
    
    // Fecha o modal
    emit('close')
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