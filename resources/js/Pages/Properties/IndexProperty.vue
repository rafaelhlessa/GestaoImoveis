<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, defineProps, computed } from 'vue';

const props = defineProps({
    // typeOwners: Array,
    // users: Array,
    properties: Object, // Recebe os dados da propriedade para edição

});
const getTitleDeedText = (titleDeed) => {
    const mapping = {
        1: 'Matrícula',
        2: 'Transcrição',
        3: 'Posse'
    };
    return mapping[titleDeed] || 'Desconhecido'; // Caso o valor não esteja no mapeamento
};

const getImageSrc = (base64Data) => {
    if (!base64Data) return ''; // Se não houver imagem, retorna vazio para evitar erros

    // Verifica se a string já tem o prefixo correto (data:image/)
    return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/jpeg;base64,${base64Data}`; // Ajuste conforme o tipo de imagem (jpeg/png)
};

const newPropriety = () => {
    router.get(route('property.create'));
}

const goToPropriety = (id) => {
  router.get(route('property.show', id));
};
</script>
<template>

    <Head title="Lista de Propriedades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Lista de Propriedades
                </h1>
                <div class="flex justify-end">
                    <button @click="newPropriety" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Adicionar Propriedade
                    </button>
                </div>

            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow">
                            <ul role="list"
                                class="px-6 py-6 grid grid-cols-4 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
                                <li v-for="file in properties" :key="file.source" class="relative border border-gray-200 rounded-lg p-4">
                                    <div
                                        class="group overflow-hidden rounded-lg bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                                        <!-- <img :src="file.file_foto" alt="" class="pointer-events-none aspect-[10/7] h-auto w-full object-cover group-hover:opacity-75" /> -->
                                        <img :src="getImageSrc(file.file_photo)" alt="" class="pointer-events-none aspect-[10/7] h-auto w-full object-cover group-hover:opacity-75" />
                                        
                                        <button type="button" @click="goToPropriety(file.id)" class="absolute inset-0 focus:outline-none">
                                            <span class="sr-only">View details for {{ file.nickname }}</span>
                                        </button>
                                    </div>
                                    <div class="mt-2 ">
                                        <h2 class="mt-4 text-sm font-medium text-gray-900"><b>{{ file.nickname }}</b></h2>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ file.district }} </p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ file.locality }} </p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ file.area }} - {{ file.unit }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ getTitleDeedText(file.title_deed) }} - Nº {{ file.title_deed_number }}</p>
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