<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

// Dados do usuário autenticado
const pageUser = usePage().props.auth.user;

// Props do componente
const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        default: () => ({}),
    },
});

// Usa os dados do prop user ou fallback para pageUser
const userData = computed(() => ({
    ...pageUser,
    ...props.user,
}));

// Inicialização do formulário com valores padrão
const profileForm = useForm({
    name: userData.value.name || '',
    email: userData.value.email || '',
    cpf_cnpj: userData.value.cpf_cnpj || '',
    phone: userData.value.phone || '',
    address: userData.value.address || '',
    city: userData.value.city || '',
    city_id: userData.value.city_id || null,
    profiles: Array.isArray(userData.value.profiles) ?
        userData.value.profiles.map(p => String(p)) : // Garantir que todos são strings
        [],
});

// Perfis disponíveis
const availableProfiles = ref([
    { slug: 'proprietario', name: 'Proprietário', description: 'Gerencia propriedades próprias', icon: '🏠' },
    { slug: 'prestador', name: 'Prestador de Serviço', description: 'Presta serviços para proprietários', icon: '🔧' }
]);

// Computed para verificar se o formulário foi modificado
const isModified = computed(() => {
    return profileForm.isDirty;
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
        allCities.value = response.data
            .filter(city => city && city.nome && city.microrregiao && city.microrregiao.mesorregiao && city.microrregiao.mesorregiao.UF)
            .map(city => ({
                id: city.id,
                nome: `${city.nome} / ${city.microrregiao.mesorregiao.UF.sigla}`
            }));

        profileForm.cpf_cnpj = applyCpfCnpjMask(profileForm.cpf_cnpj);
        profileForm.phone = applyPhoneMask(profileForm.phone);
    } catch (error) {
        console.error('Erro ao buscar cidades:', error);
        // Em caso de erro, usar uma lista vazia
        allCities.value = [];
    } finally {
        isLoadingCities.value = false;
    }
});

// Função para filtrar cidades localmente
const filterCities = (query) => {
    if (query.length >= 3 && allCities.value.length > 0) {
        filteredCities.value = allCities.value.filter((city) =>
            city.nome && city.nome.toLowerCase().includes(query.toLowerCase())
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
    }, 200);
};

// Função para salvar o perfil
const saveProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Perfil atualizado com sucesso!');
        },
        onError: (errors) => {
            console.error('Erro ao atualizar perfil:', errors);
        }
    });
};

// Função para toggle de perfil
const toggleProfile = (profileSlug) => {
    const slug = String(profileSlug); // Garantir que é string

    if (profileForm.profiles.includes(slug)) {
        profileForm.profiles = profileForm.profiles.filter(p => p !== slug);
    } else {
        profileForm.profiles.push(slug);
    }
};

// Função para aplicar a máscara de CPF ou CNPJ
const applyCpfCnpjMask = (value) => {
    if (!value) return '';
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

// Função para aplicar a máscara de telefone
const applyPhoneMask = (value) => {
    if (!value) return '';
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 10) {
        // Telefone fixo: (00) 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    } else {
        // Celular: (00) 00000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    }
};

// Observa mudanças no CPF/CNPJ para aplicar máscara
watch(
    () => profileForm.cpf_cnpj,
    (newValue, oldValue) => {
        if (newValue && typeof newValue === 'string' && newValue !== oldValue) {
            const maskedValue = applyCpfCnpjMask(newValue);
            if (maskedValue !== newValue) {
                profileForm.cpf_cnpj = maskedValue;
            }
        }
    },
    { immediate: false }
);

// Observa mudanças no telefone para aplicar máscara
watch(
    () => profileForm.phone,
    (newValue, oldValue) => {
        if (newValue && typeof newValue === 'string' && newValue !== oldValue) {
            const maskedValue = applyPhoneMask(newValue);
            if (maskedValue !== newValue) {
                profileForm.phone = maskedValue;
            }
        }
    },
    { immediate: false }
);
</script>

<template>
    <section class="bg-white shadow-lg rounded-xl p-8">
        <!-- Header com gradiente -->
        <header class="border-b border-gray-200 pb-6 mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                <h2 class="text-2xl font-bold">Informações do Perfil</h2>
            </div>
            <p class="mt-2 text-gray-600">
                Atualize as informações do seu perfil e endereço de e-mail.
            </p>
        </header>

        <form @submit.prevent="saveProfile" class="space-y-8">
            <!-- Grid responsivo para campos básicos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div>
                    <InputLabel for="name" value="Nome Completo" class="text-gray-700 font-medium" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.name"
                        required
                        autocomplete="name"
                        placeholder="Digite seu nome completo"
                    />
                    <InputError class="mt-2" :message="profileForm.errors.name" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="E-mail" class="text-gray-700 font-medium" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.email"
                        required
                        autocomplete="username"
                        placeholder="seu@email.com"
                    />
                    <InputError class="mt-2" :message="profileForm.errors.email" />
                </div>

                <!-- CPF/CNPJ -->
                <div>
                    <InputLabel for="cpf_cnpj" value="CPF/CNPJ" class="text-gray-700 font-medium" />
                    <TextInput
                        id="cpf_cnpj"
                        type="text"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.cpf_cnpj"
                        placeholder="000.000.000-00 ou 00.000.000/0000-00"
                    />
                    <InputError class="mt-2" :message="profileForm.errors.cpf_cnpj" />
                </div>

                <!-- Telefone -->
                <div>
                    <InputLabel for="phone" value="Telefone" class="text-gray-700 font-medium" />
                    <TextInput
                        id="phone"
                        type="text"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.phone"
                        placeholder="(00) 00000-0000"
                    />
                    <InputError class="mt-2" :message="profileForm.errors.phone" />
                </div>

                <!-- Endereço -->
                <div>
                    <InputLabel for="address" value="Endereço" class="text-gray-700 font-medium" />
                    <TextInput
                        id="address"
                        type="text"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.address"
                        placeholder="Rua, número, bairro"
                    />
                    <InputError class="mt-2" :message="profileForm.errors.address" />
                </div>

                <!-- Cidade -->
                <div class="relative">
                    <InputLabel for="city" value="Cidade" class="text-gray-700 font-medium" />
                    <TextInput
                        id="city"
                        type="text"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-200"
                        v-model="profileForm.city"
                        @focus="showSuggestions = true"
                        @blur="closeSuggestions"
                        placeholder="Digite o nome da cidade"
                        autocomplete="off"
                    />

                    <!-- Indicador de carregamento -->
                    <div v-if="isLoadingCities" class="absolute right-3 top-9 text-gray-400">
                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Sugestões de cidades -->
                    <div
                        v-if="showSuggestions && filteredCities.length > 0"
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                    >
                        <button
                            v-for="city in filteredCities.slice(0, 10)"
                            :key="city.id"
                            type="button"
                            class="w-full px-4 py-2 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none transition-colors duration-150"
                            @click="
                                profileForm.city = city.nome;
                                profileForm.city_id = city.id;
                                showSuggestions = false;
                            "
                        >
                            {{ city.nome }}
                        </button>
                    </div>

                    <!-- Mensagem quando não há cidades ou erro -->
                    <div v-if="showSuggestions && !isLoadingCities && filteredCities.length === 0 && profileForm.city.length >= 3"
                         class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-gray-500 text-sm">
                        Nenhuma cidade encontrada
                    </div>

                    <InputError class="mt-2" :message="profileForm.errors.city" />
                </div>
            </div>

            <!-- Seção de Perfis de Usuário -->
            <div class="border-t border-gray-200 pt-8">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Perfis de Usuário</h3>
                    <p class="text-gray-600 text-sm">
                        Selecione os perfis que melhor descrevem seu uso da plataforma.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="profile in availableProfiles"
                        :key="profile.slug"
                        class="relative"
                    >
                        <label
                            :for="`profile-${profile.slug}`"
                            class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-300 hover:shadow-md"
                            :class="{
                                'border-blue-500 bg-blue-50 shadow-md': profileForm.profiles.includes(profile.slug),
                                'border-gray-200 bg-white': !profileForm.profiles.includes(profile.slug)
                            }"
                        >
                            <input
                                :id="`profile-${profile.slug}`"
                                type="checkbox"
                                :checked="profileForm.profiles.includes(profile.slug)"
                                @change="toggleProfile(profile.slug)"
                                class="sr-only"
                            />

                            <div class="flex-shrink-0 mr-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-xl">
                                    {{ profile.icon }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-lg font-medium text-gray-900">
                                        {{ profile.name }}
                                    </h4>
                                    <div
                                        v-if="profileForm.profiles.includes(profile.slug)"
                                        class="ml-2 flex-shrink-0"
                                    >
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ profile.description }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <InputError class="mt-2" :message="profileForm.errors.profiles" />
            </div>

            <!-- Verificação de e-mail -->
            <div
                v-if="props.mustVerifyEmail && userData.email_verified_at === null"
                class="border border-yellow-200 bg-yellow-50 rounded-lg p-4"
            >
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Verificação de e-mail pendente
                        </h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            Seu endereço de e-mail não foi verificado.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="underline text-yellow-800 hover:text-yellow-900 font-medium focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded-sm"
                            >
                                Clique aqui para reenviar o e-mail de verificação.
                            </Link>
                        </p>
                        <div
                            v-show="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            Um novo link de verificação foi enviado para seu endereço de e-mail.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-200">
                <div class="flex items-center gap-4">
                    <PrimaryButton
                        :disabled="profileForm.processing"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:transform-none"
                    >
                        <span v-if="profileForm.processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Salvando...
                        </span>
                        <span v-else>Salvar Alterações</span>
                    </PrimaryButton>

                    <span
                        v-if="isModified"
                        class="text-sm text-gray-600 flex items-center"
                    >
                        <span class="w-2 h-2 bg-orange-400 rounded-full mr-2"></span>
                        Alterações não salvas
                    </span>
                </div>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 transform translate-x-4"
                    enter-to-class="opacity-100 transform translate-x-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-from-class="opacity-100 transform translate-x-0"
                    leave-to-class="opacity-0 transform translate-x-4"
                >
                    <p v-if="profileForm.recentlySuccessful" class="text-sm text-green-600 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Perfil atualizado com sucesso!
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
