<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

// Dados do usuário autenticado
const user = usePage().props.auth.user;

// Inicialização do formulário com valores padrão
const profileForm = useForm({
    name: user.name || '',
    email: user.email || '',
    cpf_cnpj: user.cpf_cnpj || '',
    phone: user.phone || '',
    address: user.address || '',
    city: user.city || '',
    city_id: user.city_id || null,
    type: user.type || '1',
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

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

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
    <div class="w-full">
    <section>
        
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Informações do Usuário
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Complete ou atualize suas informações.
            </p>
        </header>

        <form
            @submit.prevent="profileForm.patch(route('profile.update'))"
            class="mt-6"
        >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel for="name" value="Nome" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="profileForm.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="profileForm.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="profileForm.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="profileForm.errors.email" />
            </div>

            <div>
                <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" />

                <TextInput
                    id="cpf_cnpj"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="profileForm.cpf_cnpj"
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="profileForm.errors.cpf_cnpj" />
            </div>

            <div>
                <InputLabel for="phone" value="Telefone" />

                <TextInput
                    id="phone"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="profileForm.phone"
                    required
                    maxlength="15"
                    autocomplete="phone"
                />

                <InputError class="mt-2" :message="profileForm.errors.phone" />
            </div>

            <div>
                <InputLabel for="address" value="Endereço" />

                <TextInput
                    id="address"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="profileForm.address"
                    required
                    autocomplete="address"
                />

                <InputError class="mt-2" :message="profileForm.errors.address" />
            </div>

            <div>
                <InputLabel for="city" value="Cidade" />
                <div class="relative">
                    <TextInput
                        id="city"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="profileForm.city"
                        placeholder="Digite o nome da cidade"
                        autocomplete="off"
                        @focus="showSuggestions = true"
                        @blur="closeSuggestions"
                    />
                    <div
                        v-if="showSuggestions && filteredCities.length > 0"
                        class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md shadow-lg w-full max-h-48 overflow-auto"
                    >
                        <ul>
                            <li
                                v-for="city in filteredCities"
                                :key="city.id"
                                class="px-4 py-2 cursor-pointer hover:bg-gray-200"
                                @mousedown="() => {
                                    profileForm.city = city.nome;
                                    profileForm.city_id = city.id;
                                    showSuggestions = false;
                                }"
                            >
                                {{ city.nome }}
                            </li>
                        </ul>
                    </div>
                    <p v-if="isLoadingCities" class="text-sm text-gray-500">Carregando cidades...</p>
                </div>
                <InputError class="mt-2" :message="profileForm.errors.city" />
            </div>

            <div>
                <InputLabel for="type" value="Perfil" />

                <select
                    id="type"
                    class="mt-1 block rounded-md w-full"
                    v-model="profileForm.type"
                    required
                >
                    <option value="1">Proprietário</option>
                    <option value="2">Prestador de Serviço</option>
                    <option value="3">Proprietário/Prestador de Serviço</option>
                </select>

                <InputError class="mt-2" :message="profileForm.errors.type" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="profileForm.processing" @click="teste">Salvar</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="profileForm.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Salvo.
                    </p>
                </Transition>
            </div>
        </div>    
        </form>
        
    </section>
    </div>
</template>
