<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, defineProps, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const { props: pageProps } = usePage();
const userId = pageProps.auth.user.id;

const props = defineProps({
    properties: Object, 
    owner: Object,
    canView: Boolean,
    canCreate: Object // { can_create: true/false, owners: [1, 2, 3] }
});

const getTitleDeedText = (titleDeed) => {
    const mapping = {
        1: 'Matrícula',
        2: 'Transcrição',
        3: 'Posse'
    };
    return mapping[titleDeed] || 'Desconhecido';
};

const getImageSrc = (base64Data) => {
    if (!base64Data) return '';
    return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/jpeg;base64,${base64Data}`;
};

onMounted(() => {
    console.log('Propriedades do cliente:', props.properties);
    console.log('Owner:', props.owner);
    console.log('Permissões:', { canView: props.canView, canCreate: props.canCreate });
});

const newProperty = () => {
    router.get(route('property.create'));
};

const goToProperty = (id) => {
    router.get(route('property.show', id));
};

const backToDashboard = () => {
    router.get(route('dashboard'));
};

// Computed para verificar se pode criar propriedades para este cliente
const canCreateForClient = computed(() => {
    return props.canCreate && props.canCreate.can_create === true;
});
</script>

<template>
    <Head title="Propriedades do Cliente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex justify-start items-center">
                    <button 
                        @click="backToDashboard"
                        class="mr-4 p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100"
                        title="Voltar ao Dashboard"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Propriedades do Cliente
                    </h1>
                </div>
                <div class="flex justify-center">
                    <span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-lg font-medium text-white bg-indigo-600 ring-1 ring-inset ring-indigo-200">
                        {{ props.owner.name }}
                    </span>
                </div>
                <div class="flex justify-end">
                    <button 
                        v-if="canCreateForClient" 
                        @click="newProperty" 
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Adicionar Propriedade
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Informações do Cliente -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ props.owner.name }}</h3>
                                    <p class="text-sm text-gray-600" v-if="props.owner.cpf_cnpj">{{ props.owner.cpf_cnpj }}</p>
                                </div>
                                <div class="ml-auto">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ props.canView ? 'Acesso Autorizado' : 'Acesso Restrito' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Propriedades -->
                        <div v-if="!props.canView" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <p class="text-lg font-medium">Acesso não autorizado</p>
                            <p class="text-sm">Você não tem permissão para visualizar as propriedades deste cliente.</p>
                        </div>

                        <div v-else-if="!properties || properties.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-6m-2 0H5m0 0H3m2 0v-3a1 1 0 011-1h1a1 1 0 011 1v3M9 7h1a1 1 0 011 1v1a1 1 0 01-1 1H9a1 1 0 01-1-1V8a1 1 0 011-1z"></path>
                            </svg>
                            <p class="text-lg font-medium">Nenhuma propriedade encontrada</p>
                            <p class="text-sm">Este cliente ainda não possui propriedades cadastradas.</p>
                            <button 
                                v-if="canCreateForClient"
                                @click="newProperty" 
                                class="mt-4 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500"
                            >
                                Adicionar Primeira Propriedade
                            </button>
                        </div>
                        
                        <div v-else class="bg-white rounded-lg shadow">
                            <ul role="list" class="px-6 py-6 grid md:grid-cols-4 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
                                <li v-for="property in properties" :key="property.id" class="relative border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                    <div class="group overflow-hidden rounded-lg bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                                        <img 
                                            :src="getImageSrc(property.file_photo)" 
                                            :alt="property.nickname"
                                            class="pointer-events-none aspect-[10/7] h-auto w-full object-cover group-hover:opacity-75" 
                                        />
                                        <button 
                                            type="button" 
                                            @click="goToProperty(property.id)" 
                                            class="absolute inset-0 focus:outline-none"
                                        >
                                            <span class="sr-only">Ver detalhes de {{ property.nickname }}</span>
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <h2 class="mt-4 text-sm font-medium text-gray-900">
                                            <b>{{ property.nickname }}</b>
                                        </h2>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ property.district }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ property.locality }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ property.area }} - {{ property.unit }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ getTitleDeedText(property.title_deed) }} - Nº {{ property.title_deed_number }}
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>