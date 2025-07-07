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
    profile_id: user.profile_id || null,
});

// Controle para cidades
const allCities = ref([]);
const filteredCities = ref([]);
const isLoadingCities = ref(false);
const showSuggestions = ref(false);

// Lista de fallback com cidades principais do Brasil
const fallbackCities = [
    { id: 3550308, nome: 'São Paulo / SP' },
    { id: 3304557, nome: 'Rio de Janeiro / RJ' },
    { id: 3106200, nome: 'Belo Horizonte / MG' },
    { id: 4106902, nome: 'Curitiba / PR' },
    { id: 4314902, nome: 'Porto Alegre / RS' },
    { id: 2927408, nome: 'Salvador / BA' },
    { id: 2304400, nome: 'Fortaleza / CE' },
    { id: 5300108, nome: 'Brasília / DF' },
    { id: 2611606, nome: 'Recife / PE' },
    { id: 3518800, nome: 'Guarulhos / SP' },
    { id: 3509502, nome: 'Campinas / SP' },
    { id: 3547809, nome: 'Santo André / SP' },
    { id: 2507507, nome: 'João Pessoa / PB' },
    { id: 2704302, nome: 'Maceió / AL' },
    { id: 2111300, nome: 'São Luís / MA' },
    { id: 4204202, nome: 'Florianópolis / SC' },
    { id: 1501402, nome: 'Belém / PA' },
    { id: 5208707, nome: 'Goiânia / GO' },
    { id: 2800308, nome: 'Aracaju / SE' },
    { id: 1200401, nome: 'Rio Branco / AC' }
];

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

// Função para aplicar a máscara de Telefone
const applyPhoneMask = (value) => {
    if (!value) return '';
    
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 10) {
        // TELEFONE: (00) 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        // CELULAR: (00) 0 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{1})(\d{4})(\d)/, '$1 $2-$3');
    }
};

// Busca inicial de todas as cidades
onMounted(async () => {
    try {
        isLoadingCities.value = true;
        const response = await axios.get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome');
        
        const validCities = response.data
            .filter(city => {
                // Filtra cidades que tenham dados válidos
                return city && 
                       city.nome && 
                       city.microrregiao && 
                       city.microrregiao.mesorregiao && 
                       city.microrregiao.mesorregiao.UF &&
                       city.microrregiao.mesorregiao.UF.sigla;
            })
            .map(city => ({
                id: city.id,
                nome: `${city.nome} / ${city.microrregiao.mesorregiao.UF.sigla}`
            }));

        if (validCities.length > 0) {
            allCities.value = validCities;
            console.log(`${allCities.value.length} cidades carregadas com sucesso da API IBGE`);
        } else {
            throw new Error('Nenhuma cidade válida encontrada na API');
        }

        // Aplicar máscaras nos valores iniciais
        if (profileForm.cpf_cnpj) {
            profileForm.cpf_cnpj = applyCpfCnpjMask(profileForm.cpf_cnpj);
        }
        if (profileForm.phone) {
            profileForm.phone = applyPhoneMask(profileForm.phone);
        }
    } catch (error) {
        console.error('Erro ao buscar cidades da API IBGE:', error);
        console.log('Usando lista de fallback com cidades principais');
        
        // Em caso de erro, usar lista de fallback
        allCities.value = fallbackCities;
    } finally {
        isLoadingCities.value = false;
    }
});

// Função para filtrar cidades localmente
const filterCities = (query) => {
    if (!query || query.length < 3) {
        filteredCities.value = [];
        showSuggestions.value = false;
        return;
    }

    try {
        const searchTerm = query.toLowerCase().trim();
        filteredCities.value = allCities.value.filter((city) => {
            // Verificar se city e city.nome existem
            if (!city || !city.nome) return false;
            
            return city.nome.toLowerCase().includes(searchTerm);
        });
        
        showSuggestions.value = filteredCities.value.length > 0;
    } catch (error) {
        console.error('Erro ao filtrar cidades:', error);
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

const submitForm = () => {
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

</script>

<template>
    <div class="w-full max-w-6xl mx-auto">
        <div class="bg-white shadow-xl rounded-2xl border border-gray-100">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            Editar Perfil
                        </h2>
                        <p class="text-gray-600">
                            Complete ou atualize suas informações pessoais
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submitForm" class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Nome -->
                    <div class="space-y-2">
                        <InputLabel for="name" value="Nome Completo" class="text-sm font-semibold text-gray-700" />
                        <TextInput
                            id="name"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            v-model="profileForm.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Digite seu nome completo"
                        />
                        <InputError class="mt-1" :message="profileForm.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <InputLabel for="email" value="E-mail" class="text-sm font-semibold text-gray-700" />
                        <TextInput
                            id="email"
                            type="email"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            v-model="profileForm.email"
                            required
                            autocomplete="username"
                            placeholder="Digite seu e-mail"
                        />
                        <InputError class="mt-1" :message="profileForm.errors.email" />
                    </div>

                    <!-- CPF/CNPJ -->
                    <div class="space-y-2">
                        <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" class="text-sm font-semibold text-gray-700" />
                        <TextInput
                            id="cpf_cnpj"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            v-model="profileForm.cpf_cnpj"
                            autocomplete="off"
                            placeholder="000.000.000-00"
                            maxlength="18"
                            @input="(event) => {
                                profileForm.cpf_cnpj = applyCpfCnpjMask(event.target.value);
                            }"
                        />
                        <InputError class="mt-1" :message="profileForm.errors.cpf_cnpj" />
                    </div>

                    <!-- Telefone -->
                    <div class="space-y-2">
                        <InputLabel for="phone" value="Telefone" class="text-sm font-semibold text-gray-700" />
                        <TextInput
                            id="phone"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            v-model="profileForm.phone"
                            required
                            maxlength="15"
                            autocomplete="phone"
                            placeholder="(00) 00000-0000"
                            @input="(event) => {
                                profileForm.phone = applyPhoneMask(event.target.value);
                            }"
                        />
                        <InputError class="mt-1" :message="profileForm.errors.phone" />
                    </div>

                    <!-- Endereço -->
                    <div class="space-y-2">
                        <InputLabel for="address" value="Endereço" class="text-sm font-semibold text-gray-700" />
                        <TextInput
                            id="address"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            v-model="profileForm.address"
                            required
                            autocomplete="address"
                            placeholder="Digite seu endereço completo"
                        />
                        <InputError class="mt-1" :message="profileForm.errors.address" />
                    </div>

                    <!-- Cidade -->
                    <div class="space-y-2">
                        <InputLabel for="city" value="Cidade" class="text-sm font-semibold text-gray-700" />
                        <div class="relative">
                            <TextInput
                                id="city"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                v-model="profileForm.city"
                                placeholder="Digite o nome da cidade"
                                autocomplete="off"
                                @focus="showSuggestions = true"
                                @blur="closeSuggestions"
                            />
                            <div
                                v-if="showSuggestions && filteredCities.length > 0"
                                class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg w-full max-h-48 overflow-auto"
                            >
                                <ul>
                                    <li
                                        v-for="city in filteredCities"
                                        :key="city.id || Math.random()"
                                        class="px-4 py-3 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150"
                                        @mousedown="() => {
                                            if (city && city.nome && city.id) {
                                                profileForm.city = city.nome;
                                                profileForm.city_id = city.id;
                                                showSuggestions = false;
                                            }
                                        }"
                                    >
                                        {{ city?.nome || 'Cidade não identificada' }}
                                    </li>
                                </ul>
                            </div>
                            <div v-else-if="showSuggestions && filteredCities.length === 0 && profileForm.city.length >= 3" class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg w-full p-4">
                                <p class="text-sm text-gray-500 text-center">Nenhuma cidade encontrada</p>
                            </div>
                            <p v-if="isLoadingCities" class="text-sm text-blue-500 mt-2">Carregando cidades...</p>
                        </div>
                        <InputError class="mt-1" :message="profileForm.errors.city" />
                    </div>

                    <!-- Perfil (apenas para administradores) -->
                    <div v-if="user.is_admin" class="space-y-2">
                        <InputLabel for="type" value="Perfil de Usuário" class="text-sm font-semibold text-gray-700" />
                        <select
                            id="type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white"
                            v-model="profileForm.profile_id"
                            required
                        >
                            <option value="1">Proprietário</option>
                            <option value="2">Prestador de Serviço</option>
                            <option value="3">Proprietário/Prestador de Serviço</option>
                        </select>
                        <InputError class="mt-1" :message="profileForm.errors.type" />
                    </div>

                    <!-- Verificação de Email -->
                    <div v-if="mustVerifyEmail && user.email_verified_at === null" class="lg:col-span-2">
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-sm text-amber-800">
                                Seu endereço de e-mail não foi verificado.
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="ml-2 text-amber-700 underline hover:text-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 font-medium"
                                >
                                    Clique aqui para reenviar o e-mail de verificação.
                                </Link>
                            </p>

                            <div
                                v-show="status === 'verification-link-sent'"
                                class="mt-2 text-sm font-medium text-green-600"
                            >
                                Um novo link de verificação foi enviado para seu e-mail.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de ação -->
                <div class="flex items-center justify-between pt-8 border-t border-gray-100 mt-8">
                    <div class="flex items-center space-x-4">
                        <PrimaryButton 
                            :disabled="profileForm.processing" 
                            @click="submitForm"
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white font-semibold rounded-lg transition-all duration-200 flex items-center space-x-2"
                        >
                            <svg v-if="!profileForm.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8l-8 8z"></path>
                            </svg>
                            <span>{{ profileForm.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                        </PrimaryButton>

                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0 transform scale-95"
                            enter-to-class="opacity-100 transform scale-100"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-from-class="opacity-100 transform scale-100"
                            leave-to-class="opacity-0 transform scale-95"
                        >
                            <div
                                v-if="profileForm.recentlySuccessful"
                                class="flex items-center space-x-2 text-green-600 bg-green-50 px-4 py-2 rounded-lg"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm font-medium">Perfil atualizado com sucesso!</span>
                            </div>
                        </Transition>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
