<template>
  <AuthenticatedLayout>
    <Head title="Acesso Negado" />
    
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Acesso Negado
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <!-- Conteúdo da página vazio - o modal será exibido por cima -->
            <div class="min-h-96 flex items-center justify-center">
              <div class="text-center">
                <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                  Processando...
                </h3>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Erro -->
    <UnauthorizedModal
      :show="showModal"
      :error-message="errorMessage"
      :error-type="errorType"
      :redirect-url="redirectUrl"
      @close="handleModalClose"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import UnauthorizedModal from '@/Components/UnauthorizedModal.vue'

const props = defineProps({
  message: {
    type: String,
    default: 'Você não tem permissão para acessar esta página.'
  },
  type: {
    type: String,
    default: 'general'
  },
  redirectTo: {
    type: String,
    default: '/dashboard'
  },
  userProfile: {
    type: Number,
    default: null
  }
})

const showModal = ref(false)
const errorMessage = ref(props.message)
const errorType = ref(props.type)
const redirectUrl = ref(props.redirectTo)

// Determina o tipo de erro baseado no perfil do usuário e contexto
const determineErrorType = () => {
  // Se já foi especificado, mantém
  if (props.type !== 'general') {
    return props.type
  }

  // Determina baseado no perfil do usuário
  switch (props.userProfile) {
    case 1:
      return 'property_access'
    case 2:
      return 'service_provider'
    case 3:
      return 'property_access'
    default:
      return 'general'
  }
}

// Personaliza a mensagem baseada no tipo
const determineErrorMessage = () => {
  if (props.message !== 'Você não tem permissão para acessar esta página.') {
    return props.message
  }

  const type = determineErrorType()
  
  switch (type) {
    case 'property_access':
      return 'Você não tem permissão para acessar esta propriedade. Verifique se você é o proprietário ou possui autorização adequada.'
    case 'document_access':
      return 'Este documento não está disponível para visualização ou você não possui permissão para acessá-lo.'
    case 'service_provider':
      return 'Como prestador de serviço, você precisa de autorização específica do proprietário para acessar esta funcionalidade.'
    default:
      return 'Você não tem permissão para acessar esta página. Verifique suas credenciais e tente novamente.'
  }
}

onMounted(() => {
  // Configura os valores corretos
  errorType.value = determineErrorType()
  errorMessage.value = determineErrorMessage()
  
  // Mostra o modal após um pequeno delay para melhor UX
  setTimeout(() => {
    showModal.value = true
  }, 100)
})

const handleModalClose = () => {
  showModal.value = false
  
  // Redireciona após fechar o modal
  setTimeout(() => {
    router.visit(redirectUrl.value)
  }, 300)
}
</script>