<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/20/solid'
import { computed, defineProps, reactive, ref, watch } from 'vue';

const props = defineProps({
  authorizations: Array,
});

const search = ref(""); // Reactive search query

const newAuthorization = () => {
    router.get(route('authorizations.create'));
}

const form = useForm({
    owner_id: '',
    service_provider_id: '',
    service_input: '',
    can_view_documents: false,
    can_create_properties: false,
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


// Função para aplicar a máscara de Telefone **************************************************************************
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

// Função para aplicar a máscara de CPF ou CNPJ ***********************************************************************
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

const showModalAuthorization = ref(false);
const test = ref(false);
const provider = reactive(
    {
        id:'',
        owner_id: '',
        service_provider_id: '',
        can_view_documents: false,
        can_create_properties: false,
    }
);

const authProvider = (id) => {
    showModalAuthorization.value = true;
    
    const authorization = props.authorizations.find((authorization) => authorization.id === id);
    
    provider.id = authorization.id;
    provider.owner_id = authorization.owner_id;
    provider.service_provider_id = authorization.service_provider_id;
    provider.can_view_documents = authorization.can_view_documents;
    provider.can_create_properties = authorization.can_create_properties;

    // Converter para booleano
    provider.can_view_documents = authorization.can_view_documents === 1;
    provider.can_create_properties = authorization.can_create_properties === 1;

};

const updateAuthorization = () => {
    form.can_view_documents = provider.can_view_documents ? 1 : 0;
    form.can_create_properties = provider.can_create_properties ? 1 : 0;

    router.patch(route('authorizations.updateAuthChange', provider.id), {
        can_view_documents: form.can_view_documents,
        can_create_properties: form.can_create_properties,
    });

    showModalAuthorization.value = false;
};

// watch(
//     () => provider.can_view_documents,
//     (newValue) => {
//         provider.can_view_documents = newValue ? 1 : 0;
//     }
// );

// watch(
//     () => provider.can_create_properties,
//     (newValue) => {
//         provider.can_create_properties = newValue ? 1 : 0;
//     }
// );
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
                                            <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">Prestadores de Serviço Autorizados</h2>
                                            <div class="flex justify-end">
                                              <input type="text" v-model="search" placeholder="Pesquisar" @change="searchServiceProvider" class="min-w-64 block w-1/4 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                          </div>
                                          <div>
                                            <p class="row-start-2 mt-1 text-sm/6 text-gray-600">Esta lista mostra os prestadores de serviço autorizados pelo proprietário e a respectiva autorização.</p>
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
                                            <tr v-for="person in props.authorizations" :key="person.id">
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
                                                    <div class="mt-1 text-gray-500">{{ person.activity.name }}</div>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                                                    <a href="#" @click="authProvider(person.id)" class="text-indigo-600 hover:text-indigo-900">
                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                            <div v-if="person.can_create_properties === 1" class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                            <div v-if="person.can_create_properties === 1" class="size-1.5 rounded-full bg-emerald-500" />
                                                            </div>
                                                            <div v-else class="flex-none rounded-full bg-red-500/20 p-1">
                                                            <div  class="size-1.5 rounded-full bg-red-500" />
                                                            </div>
                                                            <p class="text-xs/5 text-gray-500">Cadastro</p>
                                                        </div>
                                                    
                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                            <div v-if="person.can_view_documents === 1" class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                            <div v-if="person.can_view_documents === 1" class="size-1.5 rounded-full bg-emerald-500" />
                                                            </div>
                                                            <div v-else class="flex-none rounded-full bg-red-500/20 p-1">
                                                            <div  class="size-1.5 rounded-full bg-red-500" />
                                                            </div>
                                                            <p class="text-xs/5 text-gray-500">Visualização</p>
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
                                <transition name="showModalAuthorization">
                                    <div v-if="showModalAuthorization" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Alterar a autorização</h3>
                                            <div>
                                                <div class="mb-4">
                                                    <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">   
                                                        <div class="col-span-12"> 
                                                            <div class="col-span-3">
                                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                                    <p class="text-gray-800">Pode cadastrar propriedades?</p>
                                                                    <input type="checkbox" v-model="provider.can_create_properties" class="sr-only peer">
                                                                    <div class="relative w-11 h-6 ml-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                                    <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-span-3">
                                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                                    <p class="text-gray-800">Pode visualizar documentos?</p>
                                                                    <input type="checkbox" v-model="provider.can_view_documents" class="sr-only peer">
                                                                    <div class="relative w-11 h-6 ml-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                                    <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                                </label>
                                                            </div>
                                                        </div>    
                                                </div>

                                                </div>
                                                                                                
                                                <div class="flex justify-end">
                                                    <button @click="showModalAuthorization = false" class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400">
                                                        Não
                                                    </button>
                                                                                                    
                                                    <button @click="updateAuthorization" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                                                        Sim
                                                    </button>
                                                </div>
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
