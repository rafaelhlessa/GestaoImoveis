<script setup>
import { ref, computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
  owners: {
    type: Array,
    required: true
  },
  users: {
    type: Array,
    required: true
  },
  typeOwners: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['add-owner', 'remove-owner', 'update:owners']);

// Reatividade local
const showModal = ref(false);
const searchTerm = ref("");
const selectedOwner = ref({ id: null, name: "", cpf_cnpj: "", percent: "", type_ownership: "", observations: "" });
const filteredUsers = ref([]);
const errorMessage = ref('');

// Função para limpar a seleção atual
const clearOwner = () => {
  selectedOwner.value = { id: null, name: "", cpf_cnpj: "", percent: "", type_ownership: "", observations: "" };
  searchTerm.value = "";
  errorMessage.value = '';
};

// Função para formatação de CPF/CNPJ
const applyCpfCnpjMask = (value) => {
  if (!value) return '';
  
  const numericValue = String(value).replace(/\D/g, '');

  if (numericValue.length <= 11) {
    // CPF: 000.000.000-00
    return numericValue
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  } else {
    // CNPJ: 00.000.000/0000-00
    return numericValue
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
  }
};

// Função para buscar proprietários pelo CPF/CNPJ ou nome
const searchOwners = () => {
  if (searchTerm.value.length >= 3) {
    const normalizedSearch = searchTerm.value.toLowerCase().replace(/\D/g, '');
    
    filteredUsers.value = props.users.filter((user) => {
      const normalizedCpfCnpj = user.cpf_cnpj.replace(/\D/g, '');
      return normalizedCpfCnpj.includes(normalizedSearch) || 
             user.name.toLowerCase().includes(searchTerm.value.toLowerCase());
    });
  } else {
    filteredUsers.value = [];
  }
};

// Selecionar um proprietário na busca
const selectOwner = (user) => {
  selectedOwner.value = { 
    id: user.id, 
    name: user.name, 
    cpf_cnpj: user.cpf_cnpj, 
    percent: "", 
    observations: "" 
  };
  filteredUsers.value = [];
  searchTerm.value = user.name + ' - ' + applyCpfCnpjMask(user.cpf_cnpj);
};

// Verificar se o percentual é válido
const validatePercent = (percent) => {
  const value = parseFloat(percent);
  return !isNaN(value) && value > 0 && value <= 100;
};

// Verificar se o tipo de propriedade é válido
const validateTypeOwnership = (typeId) => {
  return props.typeOwners.some(type => type.id === parseInt(typeId));
};

// Adicionar o proprietário
const addOwner = (event) => {
  // Prevenir qualquer comportamento padrão de formulário
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  // Validação dos campos
  if (!selectedOwner.value.id) {
    errorMessage.value = "Selecione um proprietário válido.";
    return false;
  }
  
  if (!validatePercent(selectedOwner.value.percent)) {
    errorMessage.value = "O percentual deve ser um valor numérico positivo entre 1 e 100.";
    return false;
  }
  
  if (!validateTypeOwnership(selectedOwner.value.type_ownership)) {
    errorMessage.value = "Selecione um tipo de propriedade válido.";
    return false;
  }
  
  // Converte os valores para os tipos corretos
  const ownerId = parseInt(selectedOwner.value.id);
  const enteredPercent = parseFloat(selectedOwner.value.percent);
  const typeOwnershipId = parseInt(selectedOwner.value.type_ownership);
  const typeOwnership = props.typeOwners.find(type => type.id === typeOwnershipId);
  
  // Verifica se o proprietário já está na lista
  const ownerExists = props.owners.some(owner => 
    owner.user?.id === ownerId && owner.type_ownership?.id === typeOwnershipId
  );
  
  if (ownerExists) {
    errorMessage.value = "Este proprietário já está na lista com o mesmo tipo de propriedade.";
    return false;
  }
  
  // Calcula o total de percentuais por tipo
  const totalPercentByType = props.owners
    .filter(owner => owner.type_ownership?.id === typeOwnershipId)
    .reduce((sum, owner) => sum + parseFloat(owner.percentage || 0), 0);
  
  // Verifica se o total excede 100%
  if (totalPercentByType + enteredPercent > 100) {
    errorMessage.value = `A soma dos percentuais para ${typeOwnership.name} não pode exceder 100%.`;
    return false;
  }
  
  // Cria o novo proprietário
  const newOwner = {
    user: {
      id: ownerId,
      name: selectedOwner.value.name,
      cpf_cnpj: selectedOwner.value.cpf_cnpj,
    },
    user_id: ownerId,
    percentage: enteredPercent,
    type_ownership: {
      id: typeOwnershipId,
      name: typeOwnership.name,
    },
    type_ownership_id: typeOwnershipId,
    observations: selectedOwner.value.observations || '',
  };
  
  // Emite o evento para adicionar o proprietário
  emit('add-owner', newOwner);
  
  // Limpa o formulário
  clearOwner();
  showModal.value = false;
  
  return true;
};

// Fechar o modal
const closeModal = () => {
  clearOwner();
  showModal.value = false;
};

// Remove um proprietário
const removeOwner = (index) => {
  emit('remove-owner', index);
};
</script>

<template>
  <div>
    <!-- Botão para adicionar proprietário -->
    <div class="flex justify-start mb-4">
      <button 
        type="button"
        @click.stop="showModal = true"
        class="rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-gray-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        Adicionar Proprietário
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block ml-2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
        </svg>
      </button>
    </div>
    
    <!-- Tabela de proprietários -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-6 py-3">Nome</th>
            <th class="px-6 py-3">CPF/CNPJ</th>
            <th class="px-6 py-3">Percentual</th>
            <th class="px-6 py-3">Relação</th>
            <th class="px-6 py-3">Notas</th>
            <th class="px-6 py-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(owner, index) in owners" :key="index" class="bg-white border-b dark:bg-gray-700 dark:border-gray-500">
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.user.name }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ applyCpfCnpjMask(owner.user.cpf_cnpj) }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.percentage }}%</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.type_ownership.name }}</td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.observations }}</td>
            <td class="px-6 py-4">
              <button 
                type="button" 
                @click="removeOwner(index)" 
                class="bg-red-500 border border-gray-600 rounded p-1 text-sm font-semibold text-white hover:bg-red-700"
              >
                Excluir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal para adicionar proprietário -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Adicionar Proprietário</h3>
          <button 
            type="button" 
            @click="closeModal" 
            class="text-gray-400 hover:text-gray-500"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <!-- Importante: usar type="button" para prevenir submissão acidental -->
        <div @submit.prevent="addOwner" class="px-0">
          <!-- Mensagem de erro -->
          <div v-if="errorMessage" class="mb-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ errorMessage }}
          </div>
          
          <!-- Campo de busca -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Buscar CPF/CNPJ ou Nome</label>
            <div class="flex">
              <input 
                type="text" 
                v-model="searchTerm" 
                @input="searchOwners" 
                placeholder="Digite CPF, CNPJ ou nome"
                class="w-full px-3 py-2.5 text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
              />
            </div>
            <ul v-if="filteredUsers.length > 0" class="mt-2 bg-gray-50 p-2 rounded-md max-h-32 overflow-y-auto">
              <li 
                v-for="user in filteredUsers" 
                :key="user.id" 
                @click="selectOwner(user)" 
                class="cursor-pointer py-1 px-2 hover:bg-gray-200 rounded-md"
              >
                {{ user.name }} - {{ applyCpfCnpjMask(user.cpf_cnpj) }}
              </li>
            </ul>
          </div>
          
          <!-- Campo de percentual -->
          <div class="mb-4">
            <label for="percent" class="block text-sm font-medium text-gray-700">Percentual</label>
            <div class="flex items-center">
              <div class="relative w-full">
                <input 
                  type="number" 
                  id="percent" 
                  v-model="selectedOwner.percent" 
                  max="100" 
                  min="0.01" 
                  step="0.01"
                  class="bg-gray-50 border border-e-0 border-gray-300 text-gray-900 text-sm rounded-s-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-white dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-700 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                />
              </div>
              <div class="flex-shrink-0 h-10 z-10 inline-flex items-center py-1 px-4 text-sm font-medium text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-e-lg hover:bg-gray-200">
                %
              </div>
            </div>
          </div>
          
          <!-- Campo de tipo de propriedade -->
          <div class="mb-4">
            <label for="type_ownership" class="block text-sm font-medium text-gray-900">Relação com a Propriedade</label>
            <div class="mt-2">
              <select 
                id="type_ownership" 
                name="type_ownership" 
                v-model="selectedOwner.type_ownership"
                class="block w-full rounded-md bg-white px-3 py-2.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm"
              >
                <option value="" disabled selected>Selecione um tipo</option>
                <option v-for="type in typeOwners" :key="type.id" :value="type.id">{{type.name}}</option>
              </select>
            </div>
          </div>
          
          <!-- Campo de observações -->
          <div class="mb-4">
            <label for="observations" class="block text-sm font-medium text-gray-700">Observações</label>
            <textarea 
              id="observations" 
              v-model="selectedOwner.observations"
              rows="3"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm border-gray-300"
            ></textarea>
          </div>
          
          <!-- Botões de ação -->
          <div class="flex justify-end">
            <button 
              @click="closeModal" 
              type="button"
              class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400"
            >
              Cancelar
            </button>
            <button 
              @click="clearOwner" 
              type="button"
              class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400"
            >
              Limpar
            </button>
            <button 
              @click="addOwner($event)" 
              type="button"
              class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
            >
              Adicionar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>