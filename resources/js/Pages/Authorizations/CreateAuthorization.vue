<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch, reactive } from 'vue';
import { CheckCircleIcon, XMarkIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/20/solid'

const page = usePage();
const message = ref(page.props.flash?.message || '');

watch(() => page.props.flash?.message, (newMessage) => {
    if (newMessage) {
        alert(newMessage);
        message.value = newMessage;
    }
});

const alert = reactive({
    show: false,
    message: '',
    type: '',
    color: '',
});

const colors = {
    red: "bg-red-200 text-red-800",
    green: "bg-green-200 text-green-800",
    blue: "bg-blue-200 text-blue-800",
    yellow: "bg-yellow-100 text-yellow-800",
    gray: "bg-gray-200 text-gray-800",
};     

const alertClass = computed(() => colors[alert.color] || "bg-gray-200 text-gray-800");

const form = useForm({
    owner_id: '',
    service_provider_id: '',
    service_input: '',
    can_view_documents: false,
    can_create_properties: false,
    evaluation_permission: false, // ✅ Novo campo
});

const props = defineProps({
    serviceProviders: {
        type: Array,
        default: () => []
    },
    user: {
        type: Object,
        default: () => ({})
    },
    message: {
        type: String,
        default: ''
    }
});

const applyPhoneMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 10) {
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2')
    } else {
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{1})(\d{4})(\d)/, '$1 $2-$3');
    }
};

const applyCpfCnpjMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 11) {
        return numericValue
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        return numericValue
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
};

const showDropdown = ref(false); 
const searchQuery = ref("");
const selectedProvider = ref(null);

const filteredServiceProviders = computed(() => {
    console.log(props.serviceProviders)
    if (!searchQuery.value.trim()) {
        return [];
    }
    return (props.serviceProviders || []).filter(provider => {
        const query = searchQuery.value.toLowerCase();
        return (
            provider.name?.toLowerCase().includes(query) || 
            provider.phone?.toLowerCase().includes(query) ||
            provider.cpf_cnpj?.toLowerCase().includes(query)
        );
    });
});

const showModal = ref(false);

const confirmAuthorization = (provider) => {
    selectedProvider.value = provider;
    console.log(provider);
    searchQuery.value = provider.name;

    form.service_input = `${provider.name} - ${applyPhoneMask(provider.phone)} - ${applyCpfCnpjMask(provider.cpf_cnpj)}`;
    form.service_provider_id = provider.id;
    
    setTimeout(() => {
        searchQuery.value = provider.name;
        filteredServiceProviders.value = [];
        showDropdown.value = false;
    }, 100);
};

watch(
    () => props.message,
    (newMessage) => {
        if (newMessage) {
            alert.message = "O prestador de serviço ja esta autorizado.";
            alert.show = true;
            alert.type = "error";
            alert.color = "red";
            setTimeout(() => {
                alert.show = false;
            }, 4000);
            return;
        }
    }
);

onMounted(() => {
    console.log(props);
    if (page.props.flash?.message) {
        alert(page.props.flash.message);
    }
});

const clear = () => {
    searchQuery.value = "";
    showDropdown.value = false;
    form.service_provider_id = "";
    form.service_input = "";
    props.message = "";
};

function handleInputClick() {
    showDropdown.value = true;
}

const authorizeProvider = () => {
    if (!form.service_provider_id) {
        alert.message = "Selecione um prestador de serviço.";
        alert.show = true;
        alert.type = "error";
        alert.color = "red";
        setTimeout(() => {
            alert.show = false;
        }, 4000);
        return;
    }
    
    form.owner_id = props.user.id;
    form.service_provider_id = form.service_provider_id;
    form.can_view_documents = form.can_view_documents;
    form.can_create_properties = form.can_create_properties;
    form.evaluation_permission = form.evaluation_permission; // ✅ Novo campo
    showModal.value = false;
    submit();
};

const submit = () => {
    form.post('/authorizations');
};
</script>

<template>
    <Head title="Autorização" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Conceder Autorização
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow p-8">
                            <form @submit.prevent="submit" enctype="multipart/form-data">
                                <div class="space-y-12">
                                    <div class="border-b border-gray-900/10 pb-12">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                                            <div class="gap-x-4 grid grid-rows-2">
                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">
                                                        Autorização de Registro e Visualização
                                                    </h2>
                                                    <div class="flex justify-end">
                                                        <div class="mt-2 relative">
                                                            <div class="relative w-full">
                                                                <input 
                                                                    type="text" 
                                                                    id="search-dropdown" 
                                                                    v-model="searchQuery" 
                                                                    @click="handleInputClick" 
                                                                    placeholder="Busque por Nome, CPF/CNPJ ou Telefone..." 
                                                                    class="min-w-96 block w-1/3 p-2.5 z-20 text-sm text-gray-900 bg-white rounded-l-lg rounded-e-lg rounded-s-gray-100 rounded-s-2 border border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                                                />
                                                                <button 
                                                                    @click="clear" 
                                                                    class="absolute top-0 end-0 p-2.5 h-full text-sm font-medium text-white bg-gray-700 rounded-e-lg border border-gray-700 hover:bg-gray-800 focus:ring-1 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                    </svg>
                                                                </button>
                                                            </div>

                                                            <!-- Dropdown List -->
                                                            <ul v-if="filteredServiceProviders.length && searchQuery.trim() && showDropdown" class="absolute z-10 mt-2 w-full bg-white shadow-md rounded-md">
                                                                <li 
                                                                    v-for="provider in filteredServiceProviders" 
                                                                    :key="provider.id" 
                                                                    @click="confirmAuthorization(provider)"
                                                                    class="cursor-pointer px-4 py-2 hover:bg-indigo-100 text-sm/6 text-gray-900"
                                                                >
                                                                    {{ provider.name }} - {{ provider.phone }}
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="row-start-2 mt-1 text-sm/6 text-gray-600">
                                                        Este formulário será utilizado para fornecer autorização as informações da propriedade ao prestador de serviço.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                            <div class="col-span-3">
                                                <label for="propriety-name" class="block text-sm/6 font-medium text-gray-900">
                                                    Proprietário
                                                </label>
                                                <div class="mt-2">
                                                    <input 
                                                        v-if="$page.props.auth.user.profile_id === 1 || $page.props.auth.user.profile_id === 3" 
                                                        type="text" 
                                                        name="propriety-name" 
                                                        id="propriety-name" 
                                                        autocomplete="propriety-name" 
                                                        :value="$page.props.auth.user.name" 
                                                        readonly 
                                                        required 
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                                    />
                                                </div>
                                            </div>
                                            
                                            <!-- Service Provider Search -->
                                            <div class="col-span-3 relative">
                                                <label class="block text-sm/6 font-medium text-gray-900">
                                                    Prestador de Serviço
                                                </label>
                                                <div class="mt-2 relative">
                                                    <input 
                                                        type="text" 
                                                        v-model="form.service_input" 
                                                        readonly
                                                        required
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm/6"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- ✅ Seção de Permissões Atualizada -->
                                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">   
                                            <div class="col-span-4">
                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <p class="text-gray-800">Pode visualizar documentos?</p>
                                                    <input type="checkbox" v-model="form.can_view_documents" class="sr-only peer">
                                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                </label>
                                            </div>
                                            <div class="col-span-4">
                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <p class="text-gray-800">Pode cadastrar propriedades?</p>
                                                    <input type="checkbox" v-model="form.can_create_properties" class="sr-only peer">
                                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                </label>
                                            </div>
                                            <!-- ✅ Nova permissão de avaliação -->
                                            <div class="col-span-4">
                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <p class="text-gray-800">Pode avaliar propriedades?</p>
                                                    <input type="checkbox" v-model="form.evaluation_permission" class="sr-only peer">
                                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-10">        
                                            <div class="col-span-6 flex justify-end">
                                                <button type="button" @click="showModal = true" class="text-gray-100 border border-gray-800 rounded p-2 bg-gray-800">
                                                    Conceder Permissão
                                                </button>
                                            </div>    
                                        </div>

                                        <!-- ✅ Modal Confirmação Atualizado -->
                                        <transition name="modal">
                                            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                                    <h1 class="text-lg font-lg text-gray-900 mb-4">Atenção</h1>
                                                    <form @submit.prevent>
                                                        <div class="mb-4">
                                                            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                                                                Você autoriza que o prestador de serviço?
                                                            </h2>
                                                            <ul class="mt-4 space-y-2">
                                                                <li class="text-gray-800">
                                                                    Visualize documentos: <b>{{ form.can_view_documents === true ? 'Sim' : 'Não' }}</b>
                                                                </li>
                                                                <li class="text-gray-800">
                                                                    Cadastre propriedades: <b>{{ form.can_create_properties === true ? 'Sim' : 'Não' }}</b>
                                                                </li>
                                                                <li class="text-gray-800">
                                                                    Avalie propriedades: <b>{{ form.evaluation_permission === true ? 'Sim' : 'Não' }}</b>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        
                                                        <div class="flex justify-end">
                                                            <button type="button" @click="showModal = false"
                                                                class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-gray-500">
                                                                Cancelar
                                                            </button>
                                                            <button @click="authorizeProvider" 
                                                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                                Autorizar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                </div>                
                            </form>
                        </div>
                        
                        <!-- Alert -->
                        <div v-if="alert.show === true" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                            <div :class="`rounded-md px-14 py-8 ${alertClass}`">
                                <div class="flex">
                                    <div class="shrink-0">
                                        <ExclamationTriangleIcon v-if="alert.type === 'warning'" class="size-5 text-yellow-400" aria-hidden="true" />
                                        <XCircleIcon v-if="alert.type === 'error'" class="size-5 text-red-400" aria-hidden="true" />
                                        <CheckCircleIcon v-if="alert.type === 'success'" class="size-5 text-green-400" aria-hidden="true" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium">{{ alert.message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>