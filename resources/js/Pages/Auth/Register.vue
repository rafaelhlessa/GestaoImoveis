<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted, watch, computed } from 'vue';
import CitySelect from '@/Components/CitySelect.vue';

const props = defineProps({
    activities: Array,
    modelValue: {
        type: [String, Number],
        default: null
    },
    modelValueId: {
        type: [String, Number],
        default: null
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    // ✅ Adicione esta propriedade se estiver sendo usada no template
    idName: {
        type: String,
        default: 'id' // ou o valor padrão apropriado
    }
});

const form = useForm({
    name: '',
    cpf_cnpj: '',
    phone: '',
    address: '',
    city: '',
    city_id: null,
        profiles: [],
    email: '',
    password: '',
    password_confirmation: '',
    message: '',
    activity_id: null,
});

const options = ref([
    { value: 1, label: 'Proprietario' },
    { value: 2, label: 'Prestador de Serviço' },
    { value: 3, label: 'Proprietario e Prestador de Serviço' },
]);

const selectedOption = ref(null);
const showSuggestions = ref(false);

// Fechar sugestões com atraso seguro
const closeSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200); // Timeout para permitir clique nas sugestões
};

// Função para aplicar a máscara de CPF ou CNPJ
const applyCpfCnpjMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

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

// Observa mudanças no valor do CPF ou CNPJ e aplica a máscara
watch(
    () => form.cpf_cnpj,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            form.cpf_cnpj = applyCpfCnpjMask(newValue);
        }
    }
);

// Função para aplicar a máscara de Telefone
const applyPhoneMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 10) {
        // TELEFONE: (00) 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2')
    } else {
        // CELULAR: (00) 0 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{1})(\d{4})(\d)/, '$1 $2-$3');
    }
};

// Observa mudanças no valor do Telefone e aplica a máscara
watch(
    () => form.phone,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            form.phone = applyPhoneMask(newValue);
        }
    }
);

const activity = ref(0)
// Observa mudanças no Perfil e aplica a mudança
watch(
    () => form.profiles,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue && newValue !== '') {
            activity.value = parseInt(newValue[0]) || 0; // Assuming the first profile is used for activity
        }
    }
);

/// Computed de erros e estado
const errors = computed(() => form.errors)
const processing = computed(() => form.processing)

const message = ref('');
const showModal = ref(false);

// Validação mínima no cliente
function validateClient() {
  if (!form.name || !form.email || !form.password || !form.password_confirmation) {
    message.value = 'Preencha todos os campos obrigatórios.'
    return false
  }

    if (!form.profiles || form.profiles.length === 0) {
        message.value = 'Selecione pelo menos um perfil.';
    return false;
  }

    if (form.profiles && form.profiles.includes('prestador') && !form.activity_id) {
    message.value = 'Selecione uma atividade.';
    return false;
  }

  if (form.password !== form.password_confirmation) {
    message.value = 'As senhas não conferem.'
    return false
  }

  message.value = ''
  return true
}

// Envio
function handleSubmit() {
  if (!validateClient()) return

  // Garantir dados "puros" no backend
  const formData = {
    ...form
  };

  formData.cpf_cnpj = formData.cpf_cnpj.replace(/\D/g, '');
  formData.phone = formData.phone.replace(/\D/g, '');

  // Usando router.post ao invés de form.post para melhor controle do redirecionamento
  router.post(route('register'), formData, {
    onError: (errors) => {
      message.value = 'Verifique os campos e tente novamente.';
      console.error('Erros de validação:', errors);
    },
    onSuccess: () => {
      // Redirecionamento será tratado automaticamente pelo backend
      console.log('Registro realizado com sucesso!');
    },
    preserveScroll: true,
  });
}

// Funções para o modal (que estava incompleto no código original)
function closeModal() {
  showModal.value = false;
}

function submit() {
  // Implementação não estava completa no código original
  handleSubmit();
}
</script>

<template>
    <GuestLayout class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-4">
        <Head title="Cadastro" />

        <div class="w-full max-w-7xl mx-auto">
            <!-- Header do Formulário -->
            <div class="text-center mb-6 pt-2">
                <img
                    src="/storage/logo2.png"
                    class="h-auto w-28 mx-auto mb-2 drop-shadow-lg"
                    alt="Logo Propriedades na Mão"
                />
                <div class="text-center">
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        Propriedades na Mão
                    </h2>

                </div>
                <p class="text-gray-600 dark:text-gray-400 mt-4 text-sm">Preencha os dados abaixo para se cadastrar na plataforma</p>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Card de Dados Pessoais e de Acesso -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-3">
                        <h3 class="text-base font-semibold text-white">
                            Dados Pessoais
                        </h3>
                        <p class="text-blue-100 text-xs mt-0.5">Informações pessoais e credenciais de acesso</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Nome -->
                            <div class="lg:col-span-1">
                                <InputLabel for="name" value="Nome Completo" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Digite seu nome completo"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- CPF/CNPJ -->
                            <div class="lg:col-span-1">
                                <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="cpf_cnpj"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.cpf_cnpj"
                                    required
                                    autocomplete="cpf_cnpj"
                                    placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                    minlength="14"
                                    maxlength="18"
                                />
                                <InputError class="mt-2" :message="form.errors.cpf_cnpj" />
                            </div>

                            <!-- Telefone -->
                            <div class="lg:col-span-1">
                                <InputLabel for="phone" value="Telefone" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.phone"
                                    required
                                    autocomplete="phone"
                                    placeholder="(48) 99999-9999"
                                    maxlength="15"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <!-- Email -->
                            <div class="lg:col-span-1">
                                <InputLabel for="email" value="Email" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="seu@email.com"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <!-- Senha -->
                            <div class="lg:col-span-1">
                                <InputLabel for="password" value="Senha" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Mínimo 8 caracteres"
                                />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <!-- Confirmação de Senha -->
                            <div class="lg:col-span-1">
                                <InputLabel for="password_confirmation" value="Confirmar Senha" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Repita a senha"
                                />
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <!-- Cidade -->
                            <div class="lg:col-span-1">
                                <InputLabel for="city" value="Cidade" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <CitySelect
                                    v-model="form.city"
                                    v-model:modelValueId="form.city_id"
                                    label=""
                                    placeholder="Digite sua cidade..."
                                    class="mt-2"
                                />
                                <InputError class="mt-2" :message="form.errors.city" />
                            </div>

                            <!-- Endereço -->
                            <div class="lg:col-span-1">
                                <InputLabel for="address" value="Endereço" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm py-3 px-4"
                                    v-model="form.address"
                                    required
                                    autocomplete="address"
                                    placeholder="Rua, número, bairro"
                                />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Perfis -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-blue-500 px-6 py-3">
                        <h3 class="text-base font-semibold text-white">
                            Perfis de Usuário
                        </h3>
                        <p class="text-green-100 text-xs mt-0.5">Selecione como você pretende usar a plataforma</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Perfil Proprietário -->
                            <label class="relative">
                                <input
                                    type="checkbox"
                                    value="proprietario"
                                    v-model="form.profiles"
                                    class="sr-only"
                                />
                                <div class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-lg"
                                     :class="{
                                         'border-blue-500 bg-blue-50 shadow-lg': form.profiles.includes('proprietario'),
                                         'border-gray-200 bg-white hover:border-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-blue-400': !form.profiles.includes('proprietario')
                                     }">
                                    <div class="flex-shrink-0 mr-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-lg">
                                            🏠
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium transition-colors duration-200"
                                                :class="{
                                                    'text-blue-900': form.profiles.includes('proprietario'),
                                                    'text-gray-900 dark:text-gray-100': !form.profiles.includes('proprietario')
                                                }">
                                                Proprietário
                                            </h4>
                                            <div v-if="form.profiles.includes('proprietario')" class="ml-2">
                                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-sm transition-colors duration-200"
                                           :class="{
                                               'text-blue-700': form.profiles.includes('proprietario'),
                                               'text-gray-600 dark:text-gray-400': !form.profiles.includes('proprietario')
                                           }">
                                            Gerencia propriedades próprias
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Perfil Prestador -->
                            <label class="relative">
                                <input
                                    type="checkbox"
                                    value="prestador"
                                    v-model="form.profiles"
                                    class="sr-only"
                                />
                                <div class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-lg"
                                     :class="{
                                         'border-green-500 bg-green-50 shadow-lg': form.profiles.includes('prestador'),
                                         'border-gray-200 bg-white hover:border-green-300 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-green-400': !form.profiles.includes('prestador')
                                     }">
                                    <div class="flex-shrink-0 mr-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-500 rounded-xl flex items-center justify-center text-white text-lg">
                                            🔧
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium transition-colors duration-200"
                                                :class="{
                                                    'text-green-900': form.profiles.includes('prestador'),
                                                    'text-gray-900 dark:text-gray-100': !form.profiles.includes('prestador')
                                                }">
                                                Prestador de Serviço
                                            </h4>
                                            <div v-if="form.profiles.includes('prestador')" class="ml-2">
                                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-sm transition-colors duration-200"
                                           :class="{
                                               'text-green-700': form.profiles.includes('prestador'),
                                               'text-gray-600 dark:text-gray-400': !form.profiles.includes('prestador')
                                           }">
                                            Presta serviços para proprietários
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Seleção de Atividade (condicional) -->
                        <div v-if="form.profiles && form.profiles.includes('prestador')" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <InputLabel for="activity_id" value="Atividade Profissional" class="text-gray-700 dark:text-gray-300 font-medium text-sm" />
                            <SelectInput
                                id="activity_id"
                                v-model="form.activity_id"
                                class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 focus:border-green-500 focus:ring-green-500 text-sm py-3 px-4"
                                required
                            >
                                <option value="" disabled>Selecione sua atividade</option>
                                <option v-for="act in props.activities" :key="act.id" :value="act.id">
                                    {{ act.name }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="form.errors.activity_id" />
                        </div>

                        <InputError class="mt-3" :message="form.errors.profiles" />
                    </div>
                </div>

                <!-- Mensagem de Erro Global -->
                <div v-if="message" class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div class="flex">
                        <svg class="w-4 h-4 text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-red-700 text-sm">{{ message }}</p>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6">
                    <Link :href="route('login')"
                        class="text-sm text-gray-600 hover:text-gray-900 underline transition-colors duration-200 dark:text-gray-400 dark:hover:text-gray-100">
                        Já possui uma conta? Fazer login
                    </Link>

                    <PrimaryButton
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition-all duration-200 text-sm"
                    >
                        <svg v-if="processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ processing ? 'Criando conta...' : 'Criar Conta' }}
                    </PrimaryButton>
                </div>
            </form>

            <!-- Modal de Confirmação -->
            <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-lg w-full mx-4">
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Confirmação Necessária</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">
                            Por favor, verifique seu email para confirmar a inscrição.
                        </p>
                    </div>

                    <div v-if="form.message" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm">{{ form.message }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="submit"
                            v-show="form.message === ''"
                            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 text-sm"
                            :disabled="processing"
                        >
                            Cadastrar
                        </button>
                        <button
                            @click="closeModal"
                            class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200 text-sm"
                        >
                            Fechar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    © 2025 Propriedades na Mão. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
