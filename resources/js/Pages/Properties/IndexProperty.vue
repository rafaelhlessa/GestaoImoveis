<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, defineProps, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const { props: pageProps } = usePage();
const userId = pageProps.auth.user.id;

const props = defineProps({
    properties: Object,
    can: Object
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
    if (!base64Data) {
        // Retorna uma imagem placeholder quando não há foto
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+CiAgPHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzZiNzI4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbSBuw6NvIGRpc3BvbsOtdmVsPC90ZXh0Pgo8L3N2Zz4=';
    }
    return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/jpeg;base64,${base64Data}`;
};

onMounted(() => {
    console.log('Propriedades do usuário:', props.properties);
    console.log('Permissões:', props.can);
});

const newProperty = () => {
    router.get(route('property.create'));
};

const goToProperty = (id) => {
    router.get(route('property.show', id));
};

// Computed para verificar se pode criar propriedades
const canCreate = computed(() => {
    const user = pageProps.auth.user;
    // Perfis 1 e 3 podem criar propriedades
    return user.profiles && user.profiles.includes('proprietario');
});

// Computed para filtrar propriedades válidas
const validProperties = computed(() => {
    if (!props.properties || !props.properties.data) return [];
    return props.properties.data.filter(property => property && property.id);
});
</script>

<template>
    <Head title="Minhas Propriedades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Minhas Propriedades
                </h1>
                <button
                    v-if="canCreate"
                    @click="newProperty"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Adicionar Propriedade
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div v-if="!validProperties || validProperties.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-6m-2 0H5m0 0H3m2 0v-3a1 1 0 011-1h1a1 1 0 011 1v3M9 7h1a1 1 0 011 1v1a1 1 0 01-1 1H9a1 1 0 01-1-1V8a1 1 0 011-1z"></path>
                            </svg>
                            <p class="text-lg font-medium">Nenhuma propriedade encontrada</p>
                            <p class="text-sm">Você ainda não possui propriedades cadastradas.</p>
                            <button
                                v-if="canCreate"
                                @click="newProperty"
                                class="mt-4 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500"
                            >
                                Adicionar Primeira Propriedade
                            </button>
                        </div>

                        <div v-else class="bg-white rounded-lg shadow">
                            <ul role="list" class="px-6 py-6 grid md:grid-cols-4 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
                                <li v-for="property in validProperties" :key="property.id" class="relative border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                    <div class="group overflow-hidden rounded-lg bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 relative">
                                        <img
                                            :src="getImageSrc(property.file_photo)"
                                            :alt="property.nickname || 'Propriedade'"
                                            class="pointer-events-none aspect-[10/7] h-auto w-full object-cover group-hover:opacity-75"
                                            @error="$event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+CiAgPHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzZiNzI4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbSBuw6NvIGRpc3BvbsOtdmVsPC90ZXh0Pgo8L3N2Zz4='"
                                        />
                                        <!-- Ícone de propriedade quando não há imagem -->
                                        <div v-if="!property.file_photo" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-6m-2 0H5m0 0H3m2 0v-3a1 1 0 011-1h1a1 1 0 011 1v3M9 7h1a1 1 0 011 1v1a1 1 0 01-1 1H9a1 1 0 01-1-1V8a1 1 0 011-1z"></path>
                                            </svg>
                                        </div>
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
                                        
                                        <p v-if="(property.property_category || (property.type_property === 1 ? 'urban' : property.type_property === 2 ? 'rural' : null)) === 'urban'" class="mt-1 text-sm font-medium text-gray-900">Distrito: <b>{{ property.district }}</b></p>
                                        <p v-else class="mt-1 text-sm font-medium text-gray-900">Subdistrito: {{ property.district }}</p>

                                        <p v-if="(property.property_category || (property.type_property === 1 ? 'urban' : property.type_property === 2 ? 'rural' : null)) === 'urban'" class="mt-1 text-sm font-medium text-gray-900">Bairro: <b>{{ property.locality }}</b></p>
                                        <p v-else class="mt-1 text-sm font-medium text-gray-900">Localidade: {{ property.locality }}</p>
                                        
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ property.area }} - {{ property.unit }}</p>
                                        <p v-if="property.title_deed != '3'" class="mt-1 text-sm font-medium text-gray-900">
                                            {{ getTitleDeedText(property.title_deed) }} - Nº {{ property.title_deed_number }}
                                        </p>
                                        <p v-else class="mt-1 text-sm font-medium text-gray-900">
                                            {{ getTitleDeedText(property.title_deed) }} - {{ property.title_deed_number }}
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
