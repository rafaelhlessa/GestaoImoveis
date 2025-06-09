<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/20/solid'
import { computed, defineProps, reactive, ref, watch } from 'vue';

const props = defineProps({
  authorizations: Array,
});

const search = ref("");

const newAuthorization = () => {
    router.get(route('authorizations.create'));
}

// ✅ CORREÇÃO 1: Form deve conter apenas os campos necessários para o update
const form = useForm({
    can_view_documents: false,
    can_create_properties: false,
    evaluation_permission: false,
});

// Computed property to filter service providers based on search input
const filteredAuthorizations = computed(() => {
    if (!search.value.trim()) {
        return props.authorizations;
    }

    return props.authorizations.filter((authorization) => {
        const serviceProvider = authorization.service_provider;
        const searchTerm = search.value.toLowerCase();

        return (
            serviceProvider.name.toLowerCase().includes(searchTerm) ||
            serviceProvider.email.toLowerCase().includes(searchTerm) ||
            serviceProvider.phone.toLowerCase().includes(searchTerm) ||
            serviceProvider.cpf_cnpj.toLowerCase().includes(searchTerm) ||
            serviceProvider.city.toLowerCase().includes(searchTerm)
        );
    });
});

// Função para aplicar a máscara de Telefone
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

// Função para aplicar a máscara de CPF ou CNPJ
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

const showModalAuthorization = ref(false);

// ✅ CORREÇÃO 2: Provider específico para o modal
const provider = reactive({
    id: '',
    owner_id: '',
    service_provider_id: '',
    can_view_documents: false,
    can_create_properties: false,
    evaluation_permission: false,
});

// ✅ CORREÇÃO 3: Função para carregar dados no modal
const authProvider = (id) => {
    console.log('=== ABRINDO MODAL ===');
    console.log('ID recebido:', id);
    
    const authorization = props.authorizations.find((auth) => auth.id === id);
    
    if (!authorization) {
        console.error('Autorização não encontrada para ID:', id);
        return;
    }
    
    console.log('Autorização encontrada:', authorization);
    
    // Carregar dados no provider
    provider.id = authorization.id;
    provider.owner_id = authorization.owner_id;
    provider.service_provider_id = authorization.service_provider_id;
    
    // ✅ CORREÇÃO: Conversão robusta para booleano
    provider.can_view_documents = Boolean(
        authorization.can_view_documents === 1 || 
        authorization.can_view_documents === true || 
        authorization.can_view_documents === "1" ||
        authorization.can_view_documents === "true"
    );
    
    provider.can_create_properties = Boolean(
        authorization.can_create_properties === 1 || 
        authorization.can_create_properties === true || 
        authorization.can_create_properties === "1" ||
        authorization.can_create_properties === "true"
    );
    
    provider.evaluation_permission = Boolean(
        authorization.evaluation_permission === 1 || 
        authorization.evaluation_permission === true || 
        authorization.evaluation_permission === "1" ||
        authorization.evaluation_permission === "true"
    );
    
    console.log('Provider após conversão:', {
        id: provider.id,
        can_view_documents: provider.can_view_documents,
        can_create_properties: provider.can_create_properties,
        evaluation_permission: provider.evaluation_permission
    });
    
    showModalAuthorization.value = true;
};

// ✅ CORREÇÃO 4: Função de atualização simplificada
const updateAuthorization = () => {
    console.log('=== SALVANDO AUTORIZAÇÃO ===');
    console.log('Provider atual:', provider);
    
    // Converter booleanos para números (0 ou 1) para o backend
    const dataToSend = {
        can_view_documents: provider.can_view_documents ? 1 : 0,
        can_create_properties: provider.can_create_properties ? 1 : 0,
        evaluation_permission: provider.evaluation_permission ? 1 : 0,
    };
    
    console.log('Dados enviados para o backend:', dataToSend);
    
    router.patch(route('authorizations.updateAuthChange', provider.id), dataToSend, {
        onSuccess: () => {
            console.log('Autorização atualizada com sucesso');
            showModalAuthorization.value = false;
        },
        onError: (errors) => {
            console.error('Erro ao atualizar autorização:', errors);
        }
    });
};

// ✅ CORREÇÃO 5: Função para verificar se valor é verdadeiro (para exibição na tabela)
const isTrue = (value) => {
    return value === 1 || value === true || value === "1" || value === "true";
};

</script>

<template>
    <Head title="Lista de Autorizações" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                  Lista de Autorizações
                </h1>
                <div class="flex justify-end">
                    <button @click="newAuthorization" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Adicionar Autorização
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow p-8">
                            <form @submit.prevent="submit" enctype="multipart/form-data">
                                <div class="space-y-12">
                                    <div class="border-b border-gray-900/10">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                                            <div class="gap-x-4 grid grid-rows-2">
                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">
                                                        Prestadores de Serviço Autorizados
                                                    </h2>
                                                    <div class="flex justify-end">
                                                        <input 
                                                            type="text" 
                                                            v-model="search" 
                                                            placeholder="Pesquisar" 
                                                            class="min-w-64 block w-1/4 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                                        />
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="row-start-2 mt-1 text-sm/6 text-gray-600">
                                                        Esta lista mostra os prestadores de serviço autorizados pelo proprietário e a respectiva autorização.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                      
                                        <div class="mt-8 flow-root">
                                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                    <table class="min-w-full divide-y divide-gray-300">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Nome</th>
                                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Contatos</th>
                                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cidade/Atividade</th>
                                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Autorização</th>
                                                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                                                    <span class="sr-only">Edit</span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 bg-white">
                                                            <tr v-for="person in filteredAuthorizations" :key="person.id">
                                                                <td class="whitespace-nowrap py-5 pl-4 pr-3 text-sm sm:pl-0">
                                                                    <div class="flex items-center">
                                                                        <div class="size-11 shrink-0">
                                                                            <img class="size-11 rounded-full" src="/storage/avatar_service.jpg" alt="" />
                                                                        </div>
                                                                        <div class="ml-4">
                                                                            <div class="font-medium text-gray-900">{{ person.service_provider.name }}</div>
                                                                            <div class="mt-1 text-gray-500">{{ applyCpfCnpjMask(person.service_provider.cpf_cnpj) }}</div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                                                                    <div class="text-gray-900">{{ applyPhoneMask(person.service_provider.phone) }}</div>
                                                                    <div class="mt-1 text-gray-500">{{ person.service_provider.email }}</div>
                                                                </td>
                                                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                                                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Ativo</span>
                                                                </td>
                                                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                                                                    <div class="text-gray-900">{{ person.service_provider.city }}</div>
                                                                    <div class="mt-1 text-gray-500">{{ person.activity?.name || 'N/A' }}</div>
                                                                </td>
                                                                <!-- ✅ Seção de Autorização Corrigida -->
                                                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                                                                    <a href="#" @click.prevent="authProvider(person.id)" class="text-indigo-600 hover:text-indigo-900">
                                                                        <!-- Permissão de Cadastro -->
                                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                                            <div v-if="isTrue(person.can_create_properties)" class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-emerald-500" />
                                                                            </div>
                                                                            <div v-else class="flex-none rounded-full bg-red-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-red-500" />
                                                                            </div>
                                                                            <p class="text-xs/5 text-gray-500">Cadastro</p>
                                                                        </div>
                                                                    
                                                                        <!-- Permissão de Visualização -->
                                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                                            <div v-if="isTrue(person.can_view_documents)" class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-emerald-500" />
                                                                            </div>
                                                                            <div v-else class="flex-none rounded-full bg-red-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-red-500" />
                                                                            </div>
                                                                            <p class="text-xs/5 text-gray-500">Visualização</p>
                                                                        </div>

                                                                        <!-- Permissão de Avaliação -->
                                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                                            <div v-if="isTrue(person.evaluation_permission)" class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-emerald-500" />
                                                                            </div>
                                                                            <div v-else class="flex-none rounded-full bg-red-500/20 p-1">
                                                                                <div class="size-1.5 rounded-full bg-red-500" />
                                                                            </div>
                                                                            <p class="text-xs/5 text-gray-500">Avaliação</p>
                                                                        </div>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>  
                                </div>  
                                
                                <!-- ✅ Modal de Edição Totalmente Corrigido -->
                                <transition name="showModalAuthorization">
                                    <div v-if="showModalAuthorization" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Alterar a autorização</h3>
                                            
                                            <div class="mb-4">
                                                <div class="space-y-4">   
                                                    <!-- Permissão de Cadastro -->
                                                    <div class="flex items-center justify-between">
                                                        <label for="can_create_properties_modal" class="text-gray-800 cursor-pointer">
                                                            Pode cadastrar propriedades?
                                                        </label>
                                                        <label for="can_create_properties_modal" class="cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                id="can_create_properties_modal"
                                                                v-model="provider.can_create_properties" 
                                                                class="sr-only peer"
                                                            >
                                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        </label>
                                                    </div>
                                                    
                                                    <!-- Permissão de Visualização -->
                                                    <div class="flex items-center justify-between">
                                                        <label for="can_view_documents_modal" class="text-gray-800 cursor-pointer">
                                                            Pode visualizar documentos?
                                                        </label>
                                                        <label for="can_view_documents_modal" class="cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                id="can_view_documents_modal"
                                                                v-model="provider.can_view_documents" 
                                                                class="sr-only peer"
                                                            >
                                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        </label>
                                                    </div>

                                                    <!-- Permissão de Avaliação -->
                                                    <div class="flex items-center justify-between">
                                                        <label for="evaluation_permission_modal" class="text-gray-800 cursor-pointer">
                                                            Pode avaliar propriedades?
                                                        </label>
                                                        <label for="evaluation_permission_modal" class="cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                id="evaluation_permission_modal"
                                                                v-model="provider.evaluation_permission" 
                                                                class="sr-only peer"
                                                            >
                                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        </label>
                                                    </div>

                                                    <!-- Debug (remover em produção) -->
                                                    <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded border">
                                                        <strong>Debug - Provider atual:</strong><br>
                                                        ID: {{ provider.id }}<br>
                                                        can_create_properties: {{ provider.can_create_properties }} ({{ typeof provider.can_create_properties }})<br>
                                                        can_view_documents: {{ provider.can_view_documents }} ({{ typeof provider.can_view_documents }})<br>
                                                        evaluation_permission: {{ provider.evaluation_permission }} ({{ typeof provider.evaluation_permission }})
                                                    </div>
                                                </div>
                                            </div>
                                                                                            
                                            <div class="flex justify-end mt-6">
                                                <button 
                                                    @click="showModalAuthorization = false" 
                                                    class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400"
                                                >
                                                    Cancelar
                                                </button>
                                                                                        
                                                <button 
                                                    @click="updateAuthorization" 
                                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
                                                >
                                                    Salvar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </transition>           
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>