<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  accept: {
    type: String,
    default: "*"
  },
  label: {
    type: String,
    default: "Selecionar arquivo"
  },
  required: {
    type: Boolean,
    default: false
  },
  modelValue: {
    type: [Object, null],
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'file-selected', 'error']);

const inputKey = ref(0);
const isUploading = ref(false);
const errorMessage = ref('');

// Converter arquivo para Base64 com segurança
const convertToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    // Verificar tamanho do arquivo (limitar a 5MB para segurança)
    if (file.size > 5 * 1024 * 1024) {
      reject(new Error('Arquivo muito grande. O tamanho máximo permitido é 5MB.'));
      return;
    }
    
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

// Validar o tipo de arquivo
const validateFileType = (file) => {
  if (!props.accept) return true;
  
  const acceptedTypes = props.accept.split(',').map(type => type.trim());
  
  // Verifica se é um tipo MIME específico
  if (acceptedTypes.includes(file.type)) return true;
  
  // Verifica extensões (para .kml, .kmz, etc.)
  const extension = '.' + file.name.split('.').pop().toLowerCase();
  return acceptedTypes.some(type => type === extension);
};

// Manipulador de upload de arquivo
const handleFileChange = async (event) => {
  if (!event.target.files || event.target.files.length === 0) return;
  
  errorMessage.value = '';
  const file = event.target.files[0];
  
  if (!validateFileType(file)) {
    errorMessage.value = `Por favor, selecione um arquivo com formato válido (${props.accept})`;
    emit('error', errorMessage.value);
    resetFileInput();
    return;
  }
  
  isUploading.value = true;
  
  try {
    const base64Data = await convertToBase64(file);
    
    const fileObject = {
      file: base64Data,
      file_name: file.name,
      type: file.type,
      size: file.size
    };
    
    emit('update:modelValue', fileObject);
    emit('file-selected', fileObject);
    
  } catch (error) {
    console.error("Erro ao converter arquivo:", error);
    errorMessage.value = error.message || 'Erro ao processar o arquivo.';
    emit('error', errorMessage.value);
  } finally {
    isUploading.value = false;
  }
};

const resetFileInput = () => {
  inputKey.value += 1;
};

// Resetar o input quando o modelo externo é nulo
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal === null) {
      resetFileInput();
    }
  }
);
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
    <div class="relative">
      <input 
        :key="inputKey" 
        type="file" 
        :accept="accept" 
        @change="handleFileChange" 
        :required="required"
        class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-indigo-500" 
      />
      <div v-if="isUploading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
    <p v-if="errorMessage" class="mt-1 text-sm text-red-600">{{ errorMessage }}</p>
    <p v-if="modelValue && modelValue.file_name" class="mt-1 text-sm text-gray-500">
      Arquivo selecionado: {{ modelValue.file_name }}
    </p>
  </div>
</template>