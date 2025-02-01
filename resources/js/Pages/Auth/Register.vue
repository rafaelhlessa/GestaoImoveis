<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive, onMounted, watch } from 'vue'

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

// Controle para cidades
const allCities = ref([]);
const filteredCities = ref([]);
const isLoadingCities = ref(false);
const showSuggestions = ref(false);

// Busca inicial de todas as cidades
onMounted(async () => {
    try {
        isLoadingCities.value = true;
        const response = await axios.get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome');
        allCities.value = response.data.map(city => ({
            id: city.id,
            nome: `${city.nome} / ${city.microrregiao.mesorregiao.UF.sigla}`
        }));
    } catch (error) {
        console.error('Erro ao buscar cidades:', error);
    } finally {
        isLoadingCities.value = false;
    }
});

// Função para filtrar cidades localmente
const filterCities = (query) => {
    if (query.length >= 3) {
        filteredCities.value = allCities.value.filter((city) =>
            city.nome.toLowerCase().includes(query.toLowerCase())
        );
        showSuggestions.value = filteredCities.value.length > 0;
    } else {
        filteredCities.value = [];
        showSuggestions.value = false;
    }
};

// Observa mudanças no campo de cidade
watch(
    () => form.city,
    (newCity) => {
        filterCities(newCity);
    }
);

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

// Observa mudanças no valor do CPF ou CNPJ e aplica a máscara
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
            // form.profile_id = applyPhoneMask(newValue);
            activity.value = newValue
        }
    }
);

// computed: {
//   activity() {
//     return this.class ? 'mt-2 sm:col-span-4 col-span-full' : 'mt-2 sm:col-span-4 col-span-full';
//   }
// }

// Estado para controlar a exibição do modal
const showModal = ref(false);

// const submit = () => {
//     const sanitizeValue = (value) => value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

    // form.cpf_cnpj = sanitizeValue(form.cpf_cnpj);
    // form.phone = sanitizeValue(form.phone);

//     if (!form.name || !form.cpf_cnpj || !form.phone || !form.profile_id || !form.address || !form.city || !form.email || !form.password || !form.password_confirmation) {
//         form.message = 'Preencha todos os campos obrigatórios.';
//     }

//     form.post(route('register'), {
//         onFinish: () => {
//             form.reset('password', 'password_confirmation'),
//                 form.cpf_cnpj = ''; // Restaura o campo mascarado, se necessário
//             form.phone = '';    // Restaura o campo mascarado, se necessário
//         }
//     });
// };

const message = ref('');

const openModal = () => {
    // Verifica se todos os campos obrigatórios foram preenchidos
    if (!form.name || !form.cpf_cnpj || !form.phone || 
        !form.profile_id || !form.address || !form.city || 
        !form.email || !form.password || !form.password_confirmation) {
        
        message.value = 'Preencha todos os campos obrigatórios.';
        return; // Impede a abertura do modal
    }

    // Se tudo estiver correto, abre o modal
    message.value = '';
    showModal.value = true;
};

const submit = () => {
    // Envia apenas quando o modal estiver aberto e sem erros
    if (showModal.value && message.value === '') {
        const sanitizeValue = (value) => value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

        form.cpf_cnpj = sanitizeValue(form.cpf_cnpj);
        form.phone = sanitizeValue(form.phone);

        form.post(route('register'), {
            onFinish: () => {
                form.reset('password', 'password_confirmation');
                form.cpf_cnpj = '';
                form.phone = '';
                showModal.value = false;
            }
        });
    }
};

// Função para fechar o modal
const closeModal = () => {
    form.message = '';
    showModal.value = false;
};
</script>

<template>
    <GuestLayout class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">

        <Head title="Register" />
        <form @submit.prevent="submit">
        <div class="">
            
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
                    
                    <div class="mt-2 sm:col-span-4 col-span-full">
                        <InputLabel for="name" value="Nome" />

                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                            autocomplete="name" placeholder="Nome Completo" />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div :class="[activity === 0 ? 'mt-2 sm:col-span-4 col-span-full' : 'mt-2 sm:col-span-3 col-span-full']">
                    
                        <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" />

                        <TextInput id="cpf_cnpj" type="text" class="mt-1 block w-full" v-model="form.cpf_cnpj" required
                            autofocus autocomplete="cpf_cnpj" placeholder="CPF ou CNPJ"
                            minlength="14" maxlength="18" />

                        <InputError class="mt-2" :message="form.errors.cpf_cnpj" />
                    </div>

                    <div :class="[activity === 0 ? 'mt-2 sm:col-span-4 col-span-full' : 'mt-2 sm:col-span-3 col-span-full']">
                        <InputLabel for="type" value="Perfil" />

                        <SelectInput class="mt-1 block w-full" v-model="form.profile_id" required>
                            <option v-for="option in options" :value="option.value" :key="option.value">
                                {{ option.label }}
                            </option>
                        </SelectInput>
                        {{ props.activity }}
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div v-if="activity > 1" class="mt-2 sm:col-span-2 col-span-full">
                        <InputLabel for="type" value="Atividade" />

                        <SelectInput class="mt-1 block w-full" v-model="form.activity_id" required>
                            <option v-for="option in props.activities" :value="option.id" :key="option.id">
                                {{ option.name }}
                            </option>
                        </SelectInput>
                        
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div class="mt-2 sm:col-span-3 col-span-full">
                        <InputLabel for="phone" value="Telefone" />

                        <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" required autofocus
                            autocomplete="phone" placeholder="DDD e número (somente números)" maxlength="15" />

                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div class="mt-2 sm:col-span-3 col-span-full">
                        <InputLabel for="city" value="Cidade" class="label" />
                        <TextInput id="city" type="text" class="mt-1 block w-full" v-model="form.city" autocomplete="city"
                            @focus="showSuggestions = true" @blur="closeSuggestions" placeholder="Digite o nome da cidade" />

                        <InputError class="mt-2" :message="form.errors.city" />

                        <div v-if="showSuggestions" class="absolute z-10 w-100 mt-1 bg-white rounded-md shadow-lg">
                            <ul class="py-1">
                                <li v-for="city in filteredCities" :key="city.id" @click="{
                                    form.city = city.nome;
                                    form.city_id = city.id;
                                    showSuggestions = false;
                                }" class="px-3 py-1.5 text-base text-gray-900 w-200 cursor-pointer hover:bg-gray-100">
                                    {{ city.nome }}
                                </li>
                            </ul>
                        </div>
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
                <div>
                    <div class="mt-4 flex items-center justify-end space-x-4">
                        <!-- Link "Já possui registro?" alinhado à esquerda -->
                        <Link :href="route('login')"
                            class="rounded-md px-6 text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800">
                            Já possui registro?
                        </Link>

                        <!-- Botão "Salvar" alinhado à direita -->
                        <!-- <a @click="showModal = true"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Salvar
                        </a> -->
                        <PrimaryButton @click="openModal">Salvar</PrimaryButton>

                    </div>    
                    <!-- Modal -->
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
                                    :disabled="form.processing">
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
