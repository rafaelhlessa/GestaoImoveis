<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive, onMounted, watch } from 'vue'

const form = useForm({
    name: '',
    cpf_cnpj: '',
    phone: '',
    address: '',
    city: '',
    city_id: null,
    type: '',
    email: '',
    password: '',
    password_confirmation: '',
    message: '',
});

const options = ref([
  { value: '1', label: 'Opção 1' },
  { value: '2', label: 'Opção 2' },
  { value: '3', label: 'Opção 3' },
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

// Estado para controlar a exibição do modal
const showModal = ref(false);



const submit = () => {
    const sanitizeValue = (value) => value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

    form.cpf_cnpj = sanitizeValue(form.cpf_cnpj);
    form.phone = sanitizeValue(form.phone);

    if (!form.name || !form.cpf_cnpj || !form.phone || !form.address || !form.city || !form.type || !form.email || !form.password || !form.password_confirmation) {
        form.message = 'Preencha todos os campos obrigatórios.';
    }
    
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation'),
            form.cpf_cnpj = ''; // Restaura o campo mascarado, se necessário
            form.phone = '';    // Restaura o campo mascarado, se necessário
        }           
    });
};

// Função para fechar o modal
const closeModal = () => {
    form.message = '';
    showModal.value = false;
};
</script>

<template>
    <GuestLayout>

        <Head title="Register" />

        <form @submit.prevent="submit">
            <div class="mt-4">
                <InputLabel for="name" value="Nome" />

                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                    autocomplete="name" placeholder="Nome Completo"/>

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4 mb-4">
                <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" />

                <TextInput id="cpf_cnpj" type="text" class="mt-1 block w-full" v-model="form.cpf_cnpj" required
                    autofocus autocomplete="cpf_cnpj" placeholder="Prestador de serviço preencher com CNPJ" minlength="14" maxlength="18"/>

                <InputError class="mt-2" :message="form.errors.cpf_cnpj"/>
            </div>

            <div class="mt-4 mb-4">
                <InputLabel for="phone" value="Telefone" />

                <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" required autofocus
                    autocomplete="phone" placeholder="DDD e número (somente números)" maxlength="15" />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4 mb-4">
                <InputLabel for="address" value="Endereço" />

                <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" required autofocus
                    autocomplete="address" placeholder="Rua, nº, Bairro"/>

                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div class="mt-4 mb-4">
                <InputLabel for="city" value="Cidade" class="label" />
                <TextInput id="city" type="text" class="mt-1 block w-full" v-model="form.city" autocomplete="city"
                    @focus="showSuggestions = true" @blur="closeSuggestions" placeholder="Digite o nome da cidade" />

                <InputError class="mt-2" :message="form.errors.city" />

                <div v-if="showSuggestions" class="absolute z-10 w-100 mt-1 bg-white rounded-md shadow-lg">
                    <ul class="py-1">
                        <li v-for="city in filteredCities" :key="city.id"
                            @click="{
                                form.city = city.nome;
                                form.city_id = city.id;
                                showSuggestions = false;
                            }"
                            class="px-3 py-1.5 text-base text-gray-900 w-200 cursor-pointer hover:bg-gray-100">
                            {{ city.nome }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-4 mb-4">
                <InputLabel for="type" value="Perfil" />
                
                <SelectInput class="mt-1 block w-full" v-model="form.type">
                    <option value="option1">Opção 1</option>
                    <option value="option2">Opção 2</option>
                    <option value="option3">Opção 3</option>
                </SelectInput>
                <!-- <TextInput id="type" type="text" class="mt-1 block w-full" v-model="form.type" required autofocus
                    autocomplete="type" /> -->

                <InputError class="mt-2" :message="form.errors.type" />
            </div>

            <div class="relative py-4">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    autocomplete="username" placeholder="Email utilizado para acessar o sistema" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Senha" />

                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="new-password" placeholder="Mínimo 8 digitos"/>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirme a Senha" />

                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
                    v-model="form.password_confirmation" required autocomplete="new-password" placeholder="Repita a senha"/>

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link :href="route('login')"
                    class="rounded-md px-6 text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800">
                Já possui registro?
                </Link>

                <!-- <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Cadastrar
                </PrimaryButton> -->
                <a @click="showModal = true" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"> Salvar
                </a>
            </div>

            <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white rounded-lg p-6 shadow-lg max-w-md w-full">
                    <h2 class="text-lg font-bold mb-4">Confirmação necessária</h2>
                    <p class="text-gray-600">
                        Por favor, verifique seu email para confirmar a inscrição.
                    </p>
                    <div v-if="form.message" class="mt-4 p-2 bg-red-100 text-red-600 rounded">
                        {{ form.message }}
                    </div>
                    <div class="mt-4 flex justify-end gap-4">
                        <a @click="submit" v-show="form.message === ''" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                            :disabled="form.processing">
                            Cadastrar
                        </a>
                        <a @click="closeModal" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-blue-600">
                            Fechar
                        </a>
                    </div>
                </div>
            </div>
        </form>

    </GuestLayout>

</template>
