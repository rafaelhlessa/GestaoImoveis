<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/20/solid'
import axios from 'axios';
import { ref, watch, onMounted, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    message: String,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });

};

// Dados do usuário autenticado
const user = usePage().props.auth.user;

// Inicialização do formulário com valores padrão
const profileForm = useForm({
    name: '',
    email: '',
    cpf_cnpj: '',
    phone: '',
    address: '',
    city: '',
    city_id: null,
    type: '',
});

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
    () => profileForm.city,
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


const teste = () => {
    console.log(profileForm.state);
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
    () => profileForm.cpf_cnpj,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            profileForm.cpf_cnpj = applyCpfCnpjMask(newValue);
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
    () => profileForm.phone,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            profileForm.phone = applyPhoneMask(newValue);
        }
    }
);
</script>

<template>
    <GuestLayout>

        <Head title="Register" />

        <!-- <form @submit.prevent="profileForm.patch(route('profile.update'))"> -->
        <form @submit.prevent="emitProfile">    
            <div class="space-y-12 bg-white p-6 rounded-lg shadow-md">
                <div class="border-b border-gray-900/10 pb-12">
                    <h2 class="text-base/7 font-semibold text-gray-900">Cadastro</h2>
                    <p class="mt-1 text-sm/6 text-gray-800">Cadastre as informações</p>

                    <div class="relative mt-6 mb-6">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center">
                            <span
                                class="bg-white px-3 text-base rounded font-semibold text-gray-900">Informações</span>
                        </div>
                    </div>


                    <div>

                    </div>
                    <div class="space-y-6 space-x-6">
                        <div class="row">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="">
                                    <InputLabel for="name" value="Nome" class="label"  />
                                    <input id="name" type="text"
                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-400 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                        v-model="profileForm.name" required autofocus autocomplete="name" placeholder="Nome Completo"/>

                                    <InputError class="mt-2" :message="profileForm.errors.name" />
                                </div>

                                <div class="">
                                    <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" class="label" />
                                    <input id="cpf_cnpj" type="text"
                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-400 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                        v-model="profileForm.cpf_cnpj" autocomplete="off" placeholder="Prestador de serviço inserir CNPJ"/>

                                    <InputError class="mt-2" :message="profileForm.errors.cpf_cnpj"/>
                                </div>

                                <div class="">
                                    <InputLabel for="phone" value="Telefone" class="label"/>
                                    <input id="phone" type="text"
                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-400 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                        v-model="profileForm.phone" required maxlength="15" autocomplete="phone" placeholder="DDD e numero" />

                                    <InputError class="mt-2" :message="profileForm.errors.phone" />
                                </div>

                                <div class="">
                                    <InputLabel for="address" value="Endereço" class="label"/>
                                    <input id="address" type="text"
                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-400 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                        v-model="profileForm.address" autocomplete="address" placeholder="Rua, nº, bairro" />

                                    <InputError class="mt-2" :message="profileForm.errors.address" />
                                </div>

                                <div class="">
                                    <InputLabel for="city" value="Cidade" class="label"/>
                                    <TextInput id="city" type="text"
                                        class="mt-1 block w-full"
                                        v-model="profileForm.city" autocomplete="city" @focus="showSuggestions = true"
                                        @blur="closeSuggestions" 
                                        placeholder="Digite o nome da cidade"
                                        />

                                    <InputError class="mt-2" :message="profileForm.errors.city" />

                                    <div v-if="showSuggestions"
                                        class="absolute z-10 w-100 mt-1 bg-white rounded-md shadow-lg">
                                        <ul class="py-1">
                                            <li v-for="city in filteredCities" :key="city.id"
                                                @click="profileForm.city = city.nome; showSuggestions = false"
                                                class="px-3 py-1.5 text-base text-gray-900 w-200 cursor-pointer hover:bg-gray-100">
                                                {{ city.nome }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-900/10">
                    <h2 class="text-base/7 font-semibold text-gray-900">Perfil</h2>
                    <p class="mt-1 text-sm/6 text-gray-600">Escolha o perfil de sua assinatura.</p>

                    <div class="mt-2 space-y-2">

                        <fieldset>
                            <div class="mt-6 space-y-6">
                                <select id="type" class="mt-1 block rounded-md w-full" v-model="profileForm.type"
                                    required>
                                    <option value="proprietario">Proprietário</option>
                                    <option value="prestador">Prestador de Serviço</option>
                                    <option value="prestador_prop">Proprietário e Prestador de Serviço</option>
                                </select>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <div class="px-4">
                    <a type="button"
                        class="text-sm/6 bg-red-500 font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 my-2 shadow-md">Voltar</a>

                </div>
                <div class="px-4">
                    <!-- <button type="submit"
                        class="text-sm/6 bg-white font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 shadow-md">Avançar</button> -->
                    <a href="register" class="text-sm/6 bg-white font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 shadow-md">Avançar</a>    
                </div>
                <!-- <button type="button" class="text-sm/6 font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 my-2">Cancelar</button>
                                <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold text-gray-400 shadow-sm">Salvar</button> -->
            </div>
        </form>
    </GuestLayout>
    <div v-if="showSuccess" class="rounded-md bg-green-50 p-4 mt-4">
        <div class="flex">
            <div class="shrink-0">
                <CheckCircleIcon class="size-5 text-green-400" aria-hidden="true" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ message }}</p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button"
                        class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50"
                        @click="showSuccess = false">
                        <span class="sr-only">Dismiss</span>
                        <XMarkIcon class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }

    .w-200 {
        width: 200px;
    }

    .size-5 {
        width: 1.25rem;
        height: 1.25rem;
    }

    .label {
    color: #161414af !important;
}
</style>
