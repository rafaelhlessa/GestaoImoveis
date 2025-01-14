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
    profile_id: null,
    email: '',
    password: '',
    password_confirmation: '',
    message: '',
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

// Estado para controlar a exibição do modal
const showModal = ref(false);



const submit = () => {
    const sanitizeValue = (value) => value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

    form.cpf_cnpj = sanitizeValue(form.cpf_cnpj);
    form.phone = sanitizeValue(form.phone);

    if (!form.name || !form.cpf_cnpj || !form.phone || !form.profile_id || !form.address || !form.city || !form.email || !form.password || !form.password_confirmation) {
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
    <Head title="Register" />
    
        <div class="flex min-h-screen min-w-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
            <div class="pt-6">
                <img src="/storage/logo2.png" class="h-auto w-40" alt="Logo" />
                <!-- <Link href="/">
                    <Logo class="h-20 w-20 fill-current text-gray-500" />
                </Link> -->
            </div>

            <div class="bg-gray-900 dark:bg-gray-800 shadow-md sm:rounded-lg w-full max-w-6xl mx-auto">
                <div class="w-full grid grid-cols-1 sm:grid-cols-1 gap-6 px-6">
                    <div class="">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nome" />
                                <TextInput v-model="form.name" id="name" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="cpf_cnpj" value="CPF/CNPJ" />
                                <TextInput v-model="form.cpf_cnpj" id="cpf_cnpj" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.cpf_cnpj" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Telefone" />
                                <TextInput v-model="form.phone" id="phone" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Endereço" />
                                <TextInput v-model="form.address" id="address" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.address" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="city" value="Cidade" />
                                <TextInput v-model="form.city" id="city" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.city" class="mt-1" />
                                <div v-if="showSuggestions" @click="closeSuggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <ul class="py-1">
                                        <li v-for="city in filteredCities" :key="city.id" @click="form.city = city.nome" class="px-3 py-1 cursor-pointer hover:bg-gray-100">
                                            {{ city.nome }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="email" value="E-mail" />
                                <TextInput v-model="form.email" id="email" type="email" class="mt-1 block w-full" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>    
                        </div>
                    </div>        
                </div>
            </div>
        </div>
    
    
</template>    