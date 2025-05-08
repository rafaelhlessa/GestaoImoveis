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
});

const form = useForm({
    name: '',
    cpf_cnpj: '',
    phone: '',
    address: '',
    city: '',
    city_id: null,
    profile_id: null,
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
    () => form.profile_id,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            activity.value = newValue
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
    <GuestLayout class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
        <Head title="Register" />
        <form @submit.prevent="handleSubmit">
            <div class="">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
                    <div class="mt-2 sm:col-span-4 col-span-full">
                        <InputLabel for="name" value="Nome" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                            autocomplete="name" placeholder="Nome Completo" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div :class="['mt-2', activity < 2 ? 'sm:col-span-4' : 'sm:col-span-3']">
                        <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" />
                        <TextInput id="cpf_cnpj" type="text" class="mt-1 block w-full" v-model="form.cpf_cnpj" required
                            autofocus autocomplete="cpf_cnpj" placeholder="CPF ou CNPJ"
                            minlength="14" maxlength="18" />
                        <InputError class="mt-2" :message="form.errors.cpf_cnpj" />
                    </div>

                    <div :class="['mt-2 col-span-full', activity < 2 ? 'sm:col-span-4' : 'sm:col-span-3']">
                        <InputLabel for="profile_id" value="Perfil" />
                        <SelectInput id="profile_id" v-model="form.profile_id" class="mt-1 block w-full" required>
                            <option value="" disabled>Escolha uma opção</option>
                            <option v-for="opt in options" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </SelectInput>
                        <InputError class="mt-2" :message="form.errors.profile_id" />
                    </div>

                    <div v-if="form.profile_id > 1" class="mt-2 sm:col-span-2 col-span-full">
                        <InputLabel for="activity_id" value="Atividade" />
                        <SelectInput id="activity_id" v-model="form.activity_id" class="mt-1 block w-full" required>
                            <option value="" disabled>Selecione a atividade</option>
                            <option v-for="act in props.activities" :key="act.id" :value="act.id">
                                {{ act.name }}
                            </option>
                        </SelectInput>
                        <InputError class="mt-2" :message="form.errors.activity_id" />
                    </div>

                    <div class="mt-2 sm:col-span-3 col-span-full">
                        <InputLabel for="phone" value="Telefone" />
                        <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" required autofocus
                            autocomplete="phone" placeholder="DDD e número (somente números)" maxlength="15" />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div class="mt-2 sm:col-span-3 col-span-full">
                        <CitySelect
                            v-model="form.city"
                            v-model:modelValueId="form.city_id"
                            label="Cidade"
                            placeholder="Digite a cidade aqui..."
                        />
                        <InputError class="mt-2" :message="form.errors.city" />
                    </div>

                    <div class="mt-2 sm:col-span-6 col-span-full">
                        <InputLabel for="address" value="Endereço" />
                        <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" required autofocus
                            autocomplete="address" placeholder="Rua, nº, Bairro" />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div class="relative py-4 col-span-full">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                    </div>

                    <div class="mt-1 sm:col-span-4 col-span-full">
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="username" placeholder="Email utilizado para acessar o sistema" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-1 sm:col-span-4 col-span-full">
                        <InputLabel for="password" value="Senha" />
                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                            autocomplete="new-password" placeholder="Mínimo 8 digitos" />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="mt-1 sm:col-span-4 col-span-full">
                        <InputLabel for="password_confirmation" value="Confirme a Senha" />
                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
                            v-model="form.password_confirmation" required autocomplete="new-password"
                            placeholder="Repita a senha" />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <!-- Exibir mensagem de erro global aqui -->
                <div v-if="message" class="mt-4 p-2 bg-red-100 text-red-600 rounded">
                    {{ message }}
                </div>
                
                <div>
                    <div class="mt-4 flex items-center justify-end space-x-4">
                        <!-- Link "Já possui registro?" alinhado à esquerda -->
                        <Link :href="route('login')"
                            class="rounded-md px-6 text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800">
                            Já possui registro?
                        </Link>

                        <PrimaryButton :class="{ 'opacity-25': processing }" :disabled="processing">
                            Salvar
                        </PrimaryButton>
                    </div>
                    
                    <!-- Modal de confirmação -->
                    <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <div class="bg-white rounded-lg p-6 shadow-lg max-w-md w-full">
                            <h2 class="text-lg font-bold mb-4 sm:col-span-2 col-span-full">Confirmação necessária</h2>
                            <p class="text-gray-600">
                                Por favor, verifique seu email para confirmar a inscrição.
                            </p>
                            <div v-if="form.message" class="mt-4 p-2 bg-red-100 text-red-600 rounded">
                                {{ form.message }}
                            </div>
                            <div class="mt-4 flex justify-end gap-4">
                                <button @click="submit" v-show="form.message === ''"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                    :disabled="processing">
                                    Cadastrar
                                </button>
                                <button @click="closeModal" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-blue-600">
                                    Fechar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>