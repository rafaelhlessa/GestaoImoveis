<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref, computed } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel, TabGroup, TabPanel, TabPanels } from '@headlessui/vue'
import { MinusIcon, PlusIcon } from '@heroicons/vue/24/outline'
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import KmlMap from '@/Components/KmlMap.vue';

const form = useForm({
    name: '',
    cpf_cnpj: '',
    title: '',
    state: '',
    city: '',
});

// Criando uma directive personalizada para tooltips
const vTooltip = {
  mounted(el, binding) {
    el._tippyInstance = tippy(el, {
      content: binding.value,
      placement: "top",
      arrow: true,
      animation: "scale",
      theme: "light-border"
    });
  },
  updated(el, binding) {
    if (el._tippyInstance) {
      el._tippyInstance.setContent(binding.value);
    }
  },
  beforeUnmount(el) {
    if (el._tippyInstance) {
      el._tippyInstance.destroy();
    }
  }
};

// Controle do modal e URL do KML
const showKmlModal = ref(false);
const selectedKmlUrl = ref(null);

const openDocument = (document) => {
  if (!document || !document.file_name) {
    console.error('Documento inválido:', document);
    return;
  }

  // Extrai a extensão do arquivo
  const fileExtension = document.file_name.split('.').pop().toLowerCase();
  const fileUrl = route('property.getDocument', document.id);
  console.log("📌 URL do documento:", fileUrl);

  if (fileExtension === 'kml' || fileExtension === 'kmz') {
    // 🗺️ Abrir no modal do Leaflet
    selectedKmlUrl.value = fileUrl;
    showKmlModal.value = true;
  } else if (fileExtension === 'pdf') {
    // 📄 Abrir PDF em nova aba
    window.open(fileUrl, '_blank');
} else if (fileExtension === 'doc' || fileExtension === 'docx') {
    // 📥 Baixar arquivo .doc ou .docx
    window.open(fileUrl, '_blank');
  } else {
    // ⚠️ Tipo de arquivo desconhecido
    alert('Formato de arquivo não suportado para visualização.');
  }
};

const props = defineProps({
    property: Object,
    properties: Object, // Para compatibilidade com versões antigas
    documents: Array,
    owners: Array,
    evaluations: Array,
    success: String,
    authorization: Object,
    typeOwnership: Array,
    canView: Boolean,
    canCreate: Boolean,
    canEdit: Boolean,
    canEvaluate: Boolean,
    isServiceProvider: Boolean,
    userActivity: Object,
});

// Computed para garantir que sempre temos uma propriedade válida
const currentProperty = computed(() => {
    // Prioriza 'property', mas fallback para 'properties' se necessário
    return props.property || props.properties || null;
});

// Computed para verificar se a propriedade está carregada
const isPropertyLoaded = computed(() => {
    return currentProperty.value && currentProperty.value.nickname;
});

// Computed para documentos filtrados
const visibleDocuments = computed(() => {
    if (!props.documents) return [];
    return props.documents.filter(doc => doc.show === 1);
});

const getImageSrc = (base64Data) => {
    if (!base64Data) return ''; // Se não houver imagem, retorna vazio para evitar erros

    // Verifica se a string já tem o prefixo correto (data:image/)
    return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/jpeg;base64,${base64Data}`; // Ajuste conforme o tipo de imagem (jpeg/png)
};

const getDocSrc = (base64Data) => {
    if (!base64Data) return ''; // Se não houver imagem, retorna vazio para evitar erros

    // Verifica se a string já tem o prefixo correto (data:application/)
    return base64Data.startsWith('data:application')
        ? base64Data
        : `data:application/pdf;base64,${base64Data}`; // Ajuste conforme o tipo de documento (pdf/docx)
};

const goToEdit = () => {
    if (currentProperty.value && currentProperty.value.id) {
        router.get(route('property.edit', currentProperty.value.id));
    }
};

const showModalDocumentShow = ref(false);

const showDocumentShowModal = () => {
    showModalDocumentShow.value = true;
};

onMounted(() => {
    console.log('Props recebidas:', props);
    console.log('Propriedade atual:', currentProperty.value);
    console.log('Propriedade carregada:', isPropertyLoaded.value);
    console.log('Documentos:', props.documents);
    console.log('Proprietários:', props.owners);
    console.log('Permissões:', {
        canEdit: props.canEdit,
        canEvaluate: props.canEvaluate,
        canView: props.canView,
        canCreate: props.canCreate
    });
});

const getOwnershipTypeName = (typeOwnershipId) => {
    if (!props.typeOwnership) return 'Tipo desconhecido';
    const type = props.typeOwnership.find(type => type.id === typeOwnershipId);
    return type ? type.name : 'Tipo desconhecido';
};
</script>

<template>
    <Head title="Propriedades" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Propriedades
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <!-- Loading State -->
                        <div v-if="!isPropertyLoaded" class="flex justify-center items-center py-12">
                            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-gray-900"></div>
                        </div>

                        <!-- Property Content -->
                        <div v-else class="bg-white">
                            <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
                                
                                <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                                    
                                    <!-- Image gallery -->
                                    <TabGroup as="div" class="flex flex-col-reverse">
                                        <TabPanels>
                                            <TabPanel>
                                                <img 
                                                    :src="getImageSrc(currentProperty.file_photo)" 
                                                    :alt="`Foto da propriedade ${currentProperty.nickname}`"  
                                                    class="aspect-square w-full object-cover sm:rounded-lg" 
                                                />
                                            </TabPanel>
                                        </TabPanels>
                                    </TabGroup>

                                    <!-- Property info -->
                                    <div class="mt-10 px-4 sm:mt-16 sm:px-0 lg:mt-0">
                                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                                            {{ currentProperty.nickname }}
                                        </h1>

                                        <div class="mt-3">
                                            <h2 class="sr-only">Property information</h2>
                                            <p class="text-2xl tracking-tight text-gray-900">{{ currentProperty.city }}</p>
                                        </div>

                                        <div class="mt-3">
                                            <p class="text-1xl tracking-tight text-gray-900">{{ currentProperty.district }}</p>
                                            <p class="text-1xl tracking-tight text-gray-900">{{ currentProperty.locality }}</p>
                                        </div>
                                        
                                        <div class="relative mt-4">
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                <div class="w-full border-t border-gray-300" />
                                            </div>
                                            <div class="relative flex justify-center">
                                                <span class="bg-white px-3 text-base font-semibold text-gray-900">Informações</span>
                                            </div>
                                        </div>

                                        <div class="mt-6">
                                            <div class="space-y-6 text-base text-gray-700">
                                                <p v-if="currentProperty.type_property === 2"> 
                                                    Trata-se de propriedade rural no município de {{ currentProperty.city }}, {{ currentProperty.district }} na localidade {{ currentProperty.locality }}, 
                                                    medindo {{ currentProperty.area }} - {{ currentProperty.unit }}. 
                                                </p>
                                                <p v-if="currentProperty.type_property === 1"> 
                                                    Trata-se de propriedade urbana no município de {{ currentProperty.city }}, bairro {{ currentProperty.locality }}, 
                                                    medindo {{ currentProperty.area }} {{ currentProperty.unit }}. 
                                                </p>
                                            </div>
                                            <div v-if="currentProperty.about">
                                                <p>{{ currentProperty.about }}</p>
                                            </div>
                                        </div>

                                        <div class="relative mt-4">
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                <div class="w-full border-t border-gray-300" />
                                            </div>
                                            <div class="relative flex justify-center">
                                                <span class="bg-white px-3 text-base font-semibold text-gray-900">Proprietário</span>
                                            </div>
                                        </div>

                                        <div class="mt-6">
                                            <div v-if="owners && owners.length > 0" class="space-y-6 text-base text-gray-700">
                                                <div v-for="owner in owners" :key="owner.id" class="space-y-6 text-base text-gray-700">
                                                    <h3>{{ owner.name }} - {{ getOwnershipTypeName(owner.pivot?.type_ownership_id) }}</h3>
                                                </div>
                                            </div>
                                            <div v-else-if="currentProperty.owners && currentProperty.owners.length > 0" class="space-y-6 text-base text-gray-700">
                                                <div v-for="owner in currentProperty.owners" :key="owner.id" class="space-y-6 text-base text-gray-700">
                                                    <h3>{{ owner.name }} - {{ getOwnershipTypeName(owner.pivot?.type_ownership_id) }}</h3>
                                                </div>
                                            </div>
                                        </div>

                                        <section aria-labelledby="details-heading" class="mt-12">
                                            <h2 id="details-heading" class="sr-only">Additional details</h2>

                                            <div class="divide-y divide-gray-200 border-t">
                                                <Disclosure as="div" v-for="detail in visibleDocuments" :key="detail.id" v-slot="{ open }">
                                                    <h3>
                                                        <DisclosureButton
                                                            class="group relative flex w-full items-center justify-between py-6 text-left">
                                                            <span :class="[open ? 'text-indigo-600' : 'text-gray-900', 'text-sm font-medium']">{{ detail.name }}</span>
                                                            <span class="ml-6 flex items-center">
                                                                <PlusIcon v-if="!open" class="block size-6 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                                                                <MinusIcon v-else class="block size-6 text-indigo-400 group-hover:text-indigo-500" aria-hidden="true" />
                                                            </span>
                                                        </DisclosureButton>
                                                    </h3>
                                                    <DisclosurePanel as="div" class="pb-6">
                                                        <div class="flow-root">
                                                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                                                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                                <div class="overflow-hidden shadow ring-1 ring-black/5 sm:rounded-lg">
                                                                    <table class="min-w-full divide-y divide-gray-300">
                                                                    <tbody class="divide-y divide-gray-200 bg-white">
                                                                        <tr>
                                                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ detail.name }}</td>
                                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center" v-tooltip="'Vencimento do documento'">{{ detail.date === null ? "Sem vencimento" : new Date(detail.date).toLocaleDateString('pt-BR') }}</td>
                                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center" @click="showDocumentShowModal" v-tooltip.top-start="'Clique para mudar a visibilidade do documento'">
                                                                            <button v-if="detail.show === 1" class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-700">
                                                                                <svg class="size-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                    <circle cx="3" cy="3" r="3" />
                                                                                </svg>
                                                                                Visível
                                                                            </button>
                                                                            <button v-else class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">
                                                                                <svg class="size-1.5 fill-red-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                    <circle cx="3" cy="3" r="3" />
                                                                                </svg>
                                                                                Não Visível
                                                                            </button>
                                                                        </td>
                                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                                            <div class="flex items-center space-x-2">
                                                                                <button @click="openDocument(detail)" v-tooltip="'Visualizar documento'">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c0 .621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                                                    </svg>
                                                                                </button>

                                                                                <!-- Modal Visualização Documento-->
                                                                                <transition name="showModalDocumentShow">
                                                                                    <div v-if="showModalDocumentShow" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                                                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                                                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Acesso negado</h3>
                                                                                            <p class="text-gray-500">Você não tem permissão para alterar a visibilidade de documentos.</p>
                                                                                            <div class="flex justify-end mt-4">
                                                                                                <button @click="showModalDocumentShow = false" class="bg-gray-600 text-white px-4 py-2 rounded">
                                                                                                    Fechar
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>    
                                                                                    </div>
                                                                                </transition>

                                                                                <!-- Modal para Visualizar KML -->
                                                                                <transition name="modal">
                                                                                    <div v-if="showKmlModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                                                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl">
                                                                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Visualização de KML</h3>

                                                                                            <!-- Exibe o mapa apenas quando a URL do KML está carregada -->
                                                                                            <KmlMap v-if="selectedKmlUrl" :kmlUrl="selectedKmlUrl" />
                                                                                            <p v-else class="text-gray-500">Nenhum KML disponível para esta propriedade.</p>

                                                                                            <div class="flex justify-end mt-4">
                                                                                                <button @click="showKmlModal = false" class="bg-gray-600 text-white px-4 py-2 rounded">
                                                                                                    Fechar
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </transition>
                                                                            </div>
                                                                        </td>
                                                                        </tr>
                                                                    </tbody>
                                                                    </table>
                                                                </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </DisclosurePanel>
                                                </Disclosure>
                                            </div>
                                        </section>
                                        
                                        <div v-if="canEdit" class="mt-4 flex justify-between items-end">
                                            <button @click="goToEdit" class="ml-auto bg-gray-600 border border-gray-700 rounded py-2 px-4 text-gray-50 hover:text-gray-100 hover:bg-gray-900">
                                                Editar Propriedade
                                            </button>
                                        </div>
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