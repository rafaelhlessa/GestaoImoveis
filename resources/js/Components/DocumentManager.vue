<script setup>
import { ref, computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/20/solid';
import FileUploader from './FileUploader.vue';

const props = defineProps({
  documents: {
    type: Array,
    default: () => []
  },
  propertyType: {
    type: Number,
    default: null
  }
});

const emit = defineEmits(['update:documents', 'add-document', 'remove-document']);

const showModal = ref(false);
const errorMessage = ref('');
const isAdding = ref(false);
const docDate = ref(false);

// Novo documento padrão
const newDocument = ref({
  name: '',
  date: null,
  show: true,
  file: null,
  file_name: '',
});

// Lista de tipos de arquivo aceitos
const allowedFileTypes = '.pdf,.doc,.docx,.kml,.kmz,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.google-earth.kml+xml,application/vnd.google-earth.kmz,application/octet-stream,application/zip';

// Documentos obrigatórios por tipo de propriedade
const requiredDocuments = computed(() => {
  if (props.propertyType === 1) { // Urbana
    return [
      'Título de propriedade'
    ];
  } else if (props.propertyType === 2) { // Rural
    return [
      'Título de propriedade (matrícula/transcrição/outro)',
      'CCIR',
      'ITR',
      'CAR',
      'Georreferenciamento (a partir de novembro de 2025)'
    ];
  }
  return [];
});

// Adicionar novo documento
const addDocument = () => {
  if (isAdding.value) return;
  isAdding.value = true;
  errorMessage.value = '';

  try {
    // Validação do formulário
    if (!newDocument.value.name.trim()) {
      errorMessage.value = "Por favor, insira um nome para o documento.";
      return;
    }

    if (!newDocument.value.file) {
      errorMessage.value = "Por favor, selecione um arquivo válido.";
      return;
    }

    // Criar objeto do documento
    const documentToAdd = {
      name: newDocument.value.name.trim(),
      date: docDate.value ? newDocument.value.date : null,
      show: newDocument.value.show,
      file: newDocument.value.file,
      file_name: newDocument.value.file_name,
    };

    // Emitir evento para adicionar documento
    emit('add-document', documentToAdd);
    
    // Limpar o formulário
    resetForm();
    showModal.value = false;
  } catch (error) {
    errorMessage.value = `Erro ao adicionar documento: ${error.message}`;
    console.error("Erro ao adicionar documento:", error);
  } finally {
    isAdding.value = false;
  }
};

// Remover documento
const removeDocument = (index) => {
  emit('remove-document', index);
};

// Resetar o formulário
const resetForm = () => {
  newDocument.value = {
    name: '',
    date: null,
    show: true,
    file: null,
    file_name: '',
  };
  docDate.value = false;
  errorMessage.value = '';
};

// Manipular a seleção de arquivo
const handleFileSelected = (fileObj) => {
  if (fileObj) {
    newDocument.value.file = fileObj.file;
    newDocument.value.file_name = fileObj.file_name;
  }
};

// Manipular erro de arquivo
const handleFileError = (error) => {
  errorMessage.value = error;
};

// Fechar o modal
const closeModal = () => {
  resetForm();
  showModal.value = false;
}
</script>

<template>
  <div>
    <div class="flex justify-between">
      <div>
        <h2 class="text-base/7 font-semibold text-gray-900">Cadastro de Documentação</h2>
        <p class="mt-1 text-sm/6 text-gray-600">Inclua os documentos da propriedade.</p>
      </div>
      <button 
        type="button" 
        @click="showModal = true" 
        class="rounded-md bg-gray-700 px-3 py-2 my-4 text-sm font-semibold text-white shadow-xs hover:bg-gray-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        Adicionar Documento
      </button>
    </div>
    
    <!-- Documentos obrigatórios -->
    <div v-if="propertyType" class="mb-6">
      <h2 class="text-white border border-red-800 p-2 rounded bg-red-600">
        <b>Documentos Obrigatórios para {{ propertyType === 2 ? 'Propriedades Rurais' : 'Imóveis Urbanos' }}</b>
      </h2>
      <div class="mt-2 bg-red-600 border border-red-800 rounded p-2">
        <p 
          v-for="(doc, index) in requiredDocuments" 
          :key="index" 
          class="mt-1 text-sm/6 text-white"
        >
          <b>* {{ doc }}</b>
        </p>
      </div>
    </div>
    
    <!-- Tabela de documentos -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
              Nome do Documento
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
              Data de Vencimento
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
              Visualizar
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
              Arquivo
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
              Ações
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="documents.length === 0" class="bg-white border-b dark:bg-gray-600 dark:border-gray-700">
            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
              Nenhum documento cadastrado.
            </td>
          </tr>
          <tr 
            v-for="(document, index) in documents" 
            :key="index" 
            class="bg-white border-b dark:bg-gray-600 dark:border-gray-700"
          >
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.name }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              {{ document.date ? new Date(document.date).toLocaleDateString('pt-BR') : "Sem Data" }}
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.show ? 'Sim' : 'Não' }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.file_name }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">
              <button 
                type="button" 
                @click="removeDocument(index)" 
                class="bg-red-500 border border-gray-600 rounded p-1 text-sm/6 font-semibold text-gray-50 hover:bg-red-700"
              >
                Excluir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal para adicionar documento -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Adicionar Documento</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <form @submit.prevent="addDocument">
          <!-- Mensagem de erro -->
          <div v-if="errorMessage" class="mb-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ errorMessage }}
          </div>
          
          <!-- Nome do documento -->
          <div class="mb-4">
            <label for="document-name" class="block text-sm font-medium text-gray-700">Nome do Documento</label>
            <input 
              type="text" 
              id="document-name" 
              v-model="newDocument.name"
              class="mt-1 block w-full text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
              required
            >
          </div>
          
          <!-- Possui validade -->
          <div class="mb-4">
            <legend class="text-sm/6 font-semibold text-gray-900">Documento possui validade?</legend>
            <div class="mt-3 space-y-3">
              <div class="flex items-center gap-x-3">
                <input 
                  id="has-date" 
                  type="radio" 
                  v-model="docDate" 
                  :value="true"
                  class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600" 
                />
                <label for="has-date" class="block text-sm/6 font-medium text-gray-900">Sim</label>
              </div>
              <div class="flex items-center gap-x-3">
                <input 
                  id="no-date" 
                  type="radio" 
                  v-model="docDate" 
                  :value="false"
                  class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600" 
                />
                <label for="no-date" class="block text-sm/6 font-medium text-gray-900">Não</label>
              </div>
            </div>
          </div>
          
          <!-- Data de vencimento -->
          <div v-if="docDate" class="mb-4">
            <label for="document-date" class="block text-sm font-medium text-gray-700">Data de Vencimento</label>
            <input 
              type="date" 
              id="document-date" 
              v-model="newDocument.date"
              class="mt-1 block w-full text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
          </div>
          
          <!-- Visibilidade do documento -->
          <div class="mb-4">
            <legend class="text-sm/6 font-semibold text-gray-900">Disponibilizar Documento</legend>
            <p class="mt-1 text-sm/6 text-gray-600">
              Marque <b>SIM</b> para os documentos que deseja disponibilizar para prestadores de serviços.
            </p>
            <div class="mt-3 space-y-3">
              <div class="flex items-center gap-x-3">
                <input 
                  id="show-doc" 
                  type="radio" 
                  v-model="newDocument.show" 
                  :value="true"
                  class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600" 
                />
                <label for="show-doc" class="block text-sm/6 font-medium text-gray-900">Sim</label>
              </div>
              <div class="flex items-center gap-x-3">
                <input 
                  id="hide-doc" 
                  type="radio" 
                  v-model="newDocument.show" 
                  :value="false"
                  class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600" 
                />
                <label for="hide-doc" class="block text-sm/6 font-medium text-gray-900">Não</label>
              </div>
            </div>
          </div>
          
          <!-- Upload de arquivo -->
          <div class="mb-4">
            <FileUploader
              label="Arquivo"
              :accept="allowedFileTypes"
              required
              @file-selected="handleFileSelected"
              @error="handleFileError"
            />
            <p class="mt-1 text-xs text-gray-500">
              Formatos aceitos: PDF, Word, KML e KMZ
            </p>
          </div>
          
          <!-- Botões de ação -->
          <div class="flex justify-end">
            <button 
              type="button" 
              @click="closeModal"
              class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              :disabled="isAdding"
            >
              {{ isAdding ? 'Adicionando...' : 'Adicionar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>