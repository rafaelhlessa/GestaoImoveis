<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="handleClose"
      >
        <div 
          class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4"
          @click.stop
        >
          <!-- Cabeçalho com ícone de erro -->
          <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-lg font-medium text-gray-900">
                Acesso Não Autorizado
              </h3>
            </div>
          </div>

          <!-- Conteúdo principal -->
          <div class="mb-6">
            <div class="text-sm text-gray-700 space-y-3">
              <p class="font-medium">
                {{ errorMessage || 'Você não tem permissão para acessar esta página.' }}
              </p>
              
              <!-- Informações específicas baseadas no tipo de erro -->
              <div v-if="errorType === 'property_access'" class="bg-blue-50 border border-blue-200 rounded-md p-3">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800">
                      Para proprietários:
                    </h4>
                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                      <li>Você só pode acessar suas próprias propriedades</li>
                      <li>Verifique se você está logado com a conta correta</li>
                      <li>Confirme se você é proprietário desta propriedade</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div v-else-if="errorType === 'document_access'" class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-yellow-800">
                      Acesso ao documento negado:
                    </h4>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                      <li>Este documento não está disponível para visualização</li>
                      <li>Entre em contato com o proprietário da propriedade</li>
                      <li>Verifique suas autorizações de acesso</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div v-else-if="errorType === 'service_provider'" class="bg-purple-50 border border-purple-200 rounded-md p-3">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-purple-800">
                      Para prestadores de serviço:
                    </h4>
                    <ul class="mt-2 text-sm text-purple-700 list-disc list-inside space-y-1">
                      <li>Você precisa de autorização do proprietário</li>
                      <li>Verifique se suas permissões estão ativas</li>
                      <li>Solicite acesso ao proprietário da propriedade</li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Informação geral -->
              <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-gray-800">
                      Precisa de ajuda?
                    </h4>
                    <p class="mt-1 text-sm text-gray-600">
                      Entre em contato com o suporte ou consulte a documentação do sistema.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Botões de ação -->
          <div class="flex justify-end space-x-3">
            <button 
              type="button" 
              @click="goBack"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              Voltar
            </button>
            <button 
              type="button" 
              @click="goToDashboard"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              Ir para Dashboard
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  show: {
    type: Boolean,
    default: true
  },
  errorMessage: {
    type: String,
    default: 'Você não tem permissão para acessar esta página.'
  },
  errorType: {
    type: String,
    default: 'general', // 'property_access', 'document_access', 'service_provider', 'general'
    validator: (value) => ['property_access', 'document_access', 'service_provider', 'general'].includes(value)
  },
  redirectUrl: {
    type: String,
    default: '/dashboard'
  }
})

const emit = defineEmits(['close'])

const handleClose = () => {
  emit('close')
}

const goBack = () => {
  window.history.back()
}

const goToDashboard = () => {
  router.visit(props.redirectUrl)
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