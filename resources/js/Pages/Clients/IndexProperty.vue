<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, defineProps, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const { props: pageProps } = usePage();
const userId = pageProps.auth.user.id;

const props = defineProps({
    // authorized: Boolean,
    // owner: Array,
    properties: Object, 
    owner: Object,
    canView: Boolean,
    canCreate: Object // Exemplo: { can_create: true, owners: [1, 2, 3] }

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

const owner = ref(null);

const canCreateProperties = ref('');

const checkAuthorization = () => {
    if (props.properties) {

        
    
    }
};
const test = ref([])
const canCreate = computed(() => {
    props.owner
});

watch(() => props.properties, checkAuthorization, { immediate: true });

// const ownerNames = computed(() => {
//     if (!props.owner || props.owner.length === 0) {
//         return 'Sem proprietário'; // Mensagem padrão caso não tenha dono
//     }
//     return props.owner.map(o => o.name).join(' - '); // Junta os nomes separados por " - "
// });

onMounted(() => {
    // fetchOwner();
    // console.log(canCreateProperties.value)
    console.log(props.owner)
    console.log(props.properties)
    // console.log(props.canCreate.can_create)
    // checkAuthorization();
});

const newPropriety = () => {
    router.get(route('property.create'));
}

const goToPropriety = (id) => {
  router.get(route('clients.show', id));
};
</script>
<template>

    <Head title="Lista de Propriedades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex justify-start">
                    <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Propriedades do Cliente  
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>

                    </h1>
                </div>
                <div class="flex justify-center">
                    <span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-lg font-medium text-white ring-1 ring-inset ring-gray-200">
                        {{ props.owner.name }}
                    </span>
                </div>
                <div class="flex justify-end">
                    <button v-if="props.canCreate.can_create === true" @click="newPropriety" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
                                class="px-6 py-6 grid md:grid-cols-4 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
                                <li v-for="file in properties" :key="file.id" class="relative border border-gray-200 rounded-lg p-4">
                                    <div
                                        class="group overflow-hidden rounded-lg bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
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