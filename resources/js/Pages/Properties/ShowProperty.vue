<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel, RadioGroup, RadioGroupOption, Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/vue'
import { MinusIcon, PlusIcon } from '@heroicons/vue/24/outline'
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import KmlMap from '@/Components/KmlMap.vue';
import PropertyEvaluationModal from '@/Components/PropertyEvaluationModal.vue';
import EvaluationsListModal from '@/Components/EvaluationsListModal.vue';

const { auth } = usePage().props;

// Define props for the component
const props = defineProps({
    property: {
        type: Object,
        required: true
    },
    documents: {
        type: Array,
        default: () => []
    },
    owners: {
        type: Array,
        default: () => []
    },
    evaluations: {
        type: Array,
        default: () => []
    },
    success: String,
    authorization: Object,
    canEdit: Boolean,
    canEvaluate: Boolean, // ✅ Permissão calculada no backend
    canView: Boolean,
    canCreate: Boolean,
    userActivity: Object,
    canMakeEvaluations: Boolean, // Mantido para compatibilidade
    isServiceProvider: Boolean,
    typeOwnership: Array
});

// Estados para modais
const showKmlModal = ref(false);
const selectedKmlUrl = ref(null);
const showModalDocumentShow = ref(false);
const showEvaluationModal = ref(false);
const showEvaluationsListModal = ref(false);

// ✅ CORRIGIDO: Computed para verificar se é proprietário DIRETO da propriedade
const isDirectOwner = computed(() => {
    // ✅ Verificação defensiva
    if (!props.owners || !Array.isArray(props.owners)) {
        console.warn('props.owners não está definido ou não é um array:', props.owners);
        return false;
    }

    // Verificar se o usuário atual é proprietário direto da propriedade
    const directOwner = props.owners.find(owner => 
        owner.user_id === auth.user.id || owner.id === auth.user.id
    );
    
    return !!directOwner;
});

// ✅ CORRIGIDO: Computed para verificar se tem autorização (prestador autorizado)
const hasAuthorization = computed(() => {
    // Verificar se o usuário tem autorização para ver esta propriedade
    if (props.authorization && props.authorization.authorized_user_id === auth.user.id) {
        // Verificar se a autorização se aplica a um dos proprietários da propriedade
        return props.owners.some(owner => owner.id === props.authorization.owner_id);
    }
    
    return false;
});

// ✅ CORRIGIDO: Computed genérico para "tem acesso" (proprietário ou autorizado)
const isOwner = computed(() => {
    return isDirectOwner.value || hasAuthorization.value;
});

// ✅ CORRIGIDO: Computed para verificar quem pode ver TODOS os documentos
const canViewAllDocuments = computed(() => {
    // Perfil 1 (Proprietário): pode ver todos os documentos das suas propriedades
    if (auth.user.profile_id === 1 && isDirectOwner.value) {
        return true;
    }
    
    // Perfil 3 (Híbrido): pode ver todos os documentos quando é:
    // 1. Proprietário direto da propriedade OU
    // 2. Prestador autorizado pelo proprietário
    if (auth.user.profile_id === 3 && (isDirectOwner.value || hasAuthorization.value)) {
        return true;
    }
    
    return false;
});

// ✅ NOVO: Computed para verificar se pode EDITAR a visibilidade dos documentos
const canEditDocumentVisibility = computed(() => {
    // Só proprietários diretos podem editar a visibilidade
    // Perfil 1: se for proprietário direto
    if (auth.user.profile_id === 1 && isDirectOwner.value) {
        return true;
    }
    
    // Perfil 3: só se for proprietário direto (não se for apenas autorizado)
    if (auth.user.profile_id === 3 && isDirectOwner.value) {
        return true;
    }
    
    return false;
});

// ✅ CORREÇÃO: Usar a permissão calculada no backend
const canEvaluateProperty = computed(() => {
    console.log('=== VERIFICAÇÃO PERMISSÃO AVALIAÇÃO ===');
    console.log('canEvaluate (backend):', props.canEvaluate);
    console.log('canMakeEvaluations (legacy):', props.canMakeEvaluations);
    console.log('userActivity:', props.userActivity);
    console.log('user profile:', auth.user.profile_id);
    
    console.log(props)
    // Priorizar a permissão calculada no backend
    if (props.canEvaluate !== undefined) {
        return props.canEvaluate;
    }
    
    // Fallback para compatibilidade
    if (props.canMakeEvaluations !== undefined) {
        return props.canMakeEvaluations;
    }
    
    // Último fallback - nunca deveria chegar aqui
    console.warn('Nenhuma permissão de avaliação encontrada, usando fallback');
    return false;
});

// Computed para verificar se pode ver avaliações (proprietários)
const canViewEvaluations = computed(() => {
    // Proprietários sempre podem ver suas avaliações
    if (isOwner.value) return true;
    
    // Prestadores também podem ver se têm permissão
    return props.canView || false;
});

// Computed para estatísticas das avaliações
const evaluationStats = computed(() => {
    // ✅ CORRIGIDO: Verificações mais robustas
    if (!props.evaluations || !Array.isArray(props.evaluations) || props.evaluations.length === 0) {
        return null;
    }
    
    try {
        const valuations = props.evaluations
            .map(e => parseFloat(e.valuation))
            .filter(val => !isNaN(val) && val > 0); // Filtrar valores inválidos
        
        if (valuations.length === 0) {
            return null;
        }
        
        const average = valuations.reduce((sum, val) => sum + val, 0) / valuations.length;
        const highest = Math.max(...valuations);
        const lowest = Math.min(...valuations);
        
        return {
            count: valuations.length,
            average,
            highest,
            lowest
        };
    } catch (error) {
        console.error('Erro ao calcular estatísticas de avaliação:', error);
        return null;
    }
});

// ✅ MODIFICAÇÃO PRINCIPAL: Computed para documentos visíveis baseado no tipo de usuário
const visibleDocuments = computed(() => {
    if (!props.documents) return [];
    
    // Se pode ver todos os documentos (proprietário ou autorizado), mostra todos
    if (canViewAllDocuments.value) {
        return props.documents;
    }
    
    // Caso contrário (prestadores não autorizados), só mostra os marcados como visíveis
    return props.documents.filter(doc => doc.show === 1);
});

// ✅ NOVA FUNÇÃO: Para verificar se um documento está visível para prestadores
const isDocumentVisibleToServiceProviders = (document) => {
    return document.show === 1;
};

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

const openDocument = (document) => {
  if (!document || !document.file_name) {
    console.error('Documento inválido:', document);
    return;
  }

  const fileExtension = document.file_name.split('.').pop().toLowerCase();
  const fileUrl = route('property.getDocument', document.id);

  if (fileExtension === 'kml' || fileExtension === 'kmz') {
    selectedKmlUrl.value = fileUrl;
    showKmlModal.value = true;
  } else if (fileExtension === 'pdf') {
    window.open(fileUrl, '_blank');
  } else if (fileExtension === 'doc' || fileExtension === 'docx') {
    window.open(fileUrl, '_blank');
  } else {
    alert('Formato de arquivo não suportado para visualização.');
  }
};

const getImageSrc = (base64Data) => {
    if (!base64Data) return '';
    return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/jpeg;base64,${base64Data}`;
};

const goToPropriety = (id) => {
  router.get(route('property.edit', id));
};

// ✅ CORREÇÃO: Verificar permissão adequadamente
const goToEvaluation = (id) => {
    if (!canEvaluateProperty.value) {
        alert('Você não tem permissão para avaliar propriedades.');
        return;
    }
    showEvaluationModal.value = true;
};

const showEvaluationPropriety = (id) => {
    if (!canViewEvaluations.value) {
        alert('Você não tem permissão para visualizar as avaliações.');
        return;
    }
    showEvaluationsListModal.value = true;
};

const showDocumentShowModal = () => {
    showModalDocumentShow.value = true;
};

const documentShow = (id) => {
    router.patch(route('property.updateDocument', id), {
        show: !props.documents.find(doc => doc.id === id).show
    });
    showModalDocumentShow.value = false;
};

// Handlers para os modais de avaliação
const handleEvaluationSuccess = () => {
    // Recarregar a página para atualizar as avaliações
    router.reload({ only: ['evaluations', 'property'] });
};

const openEvaluationFromList = () => {
    showEvaluationsListModal.value = false;
    showEvaluationModal.value = true;
};

// Função para formatar moeda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

// Função para obter nome do tipo de propriedade
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
                        <div class="bg-white">
                            <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
                                
                                <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                                    
                                    <!-- Image gallery -->
                                    <TabGroup as="div" class="flex flex-col-reverse">
                                        <TabPanels>
                                            <TabPanel>
                                                <img :src="getImageSrc(props.property.file_photo)" alt="Foto da propriedade"  class="aspect-square w-full object-cover sm:rounded-lg" />
                                            </TabPanel>
                                        </TabPanels>
                                    </TabGroup>

                                    <!-- Product info -->
                                    <div class="mt-10 px-4 sm:mt-16 sm:px-0 lg:mt-0">
                                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ props.property.nickname }}</h1>

                                        <div class="mt-3">
                                            <h2 class="sr-only">Property information</h2>
                                            <p class="text-2xl tracking-tight text-gray-900">{{ props.property.city }}</p>
                                        </div>

                                        <div class="mt-3">
                                            <p class="text-1xl tracking-tight text-gray-900">{{ props.property.district }}</p>
                                            <p class="text-1xl tracking-tight text-gray-900">{{ props.property.locality }}</p>
                                        </div>
                                        
                                        <!-- Estatísticas de Avaliações (se pode ver avaliações) -->
                                        <div v-if="auth.user.profile_id !== 2" class="mt-6 p-4 bg-blue-50 rounded-lg">
                                            <h3 class="text-sm font-medium text-blue-900 mb-2">Resumo das Avaliações</h3>
                                            
                                            <!-- ✅ CORRIGIDO: Verificação se existem avaliações -->
                                            <div v-if="evaluationStats && evaluationStats.count > 0">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="text-center">
                                                        <div class="text-lg font-bold text-blue-600">{{ evaluationStats.count }}</div>
                                                        <div class="text-xs text-blue-700">Avaliações</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-lg font-bold text-green-600">
                                                            {{ formatCurrency(evaluationStats.average) }}
                                                        </div>
                                                        <div class="text-xs text-green-700">Valor Médio</div>
                                                    </div>
                                                </div>
                                                <div v-if="evaluationStats.count > 1" class="grid grid-cols-2 gap-4 mt-2">
                                                    <div class="text-center">
                                                        <div class="text-sm font-semibold text-purple-600">
                                                            {{ formatCurrency(evaluationStats.highest) }}
                                                        </div>
                                                        <div class="text-xs text-purple-700">Maior Valor</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-sm font-semibold text-orange-600">
                                                            {{ formatCurrency(evaluationStats.lowest) }}
                                                        </div>
                                                        <div class="text-xs text-orange-700">Menor Valor</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- ✅ NOVO: Mensagem quando não há avaliações -->
                                            <div v-else class="text-center text-gray-500">
                                                <div class="text-sm">Nenhuma avaliação disponível</div>
                                                <div class="text-xs text-gray-400 mt-1">Esta propriedade ainda não foi avaliada</div>
                                            </div>
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
                                            <h3 class="sr-only">Description</h3>
                                            <div class="space-y-6 text-base text-gray-700">
                                                <p v-if="props.property.type_property === 2"> 
                                                    Trata-se de propriedade rural no município de {{ props.property.city }}, {{ props.property.district }} na localidade {{ props.property.locality }}, 
                                                    medindo {{ props.property.area }} - {{ props.property.unit }}. 
                                                </p>
                                                <p v-if="props.property.type_property === 1"> 
                                                    Trata-se de propriedade urbana no município de {{ props.property.city }}, bairro {{ props.property.locality }}, 
                                                    medindo {{ props.property.area }} {{ props.property.unit }}. 
                                                </p>
                                            </div>
                                            <div v-if="props.property.about">
                                                <p class="mt-4 text-base text-gray-700">{{ props.property.about }}</p>
                                            </div>
                                        </div>

                                        <!-- ✅ CORREÇÃO: Seção de Proprietários -->
                                        <div class="relative mt-6">
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                <div class="w-full border-t border-gray-300" />
                                            </div>
                                            <div class="relative flex justify-center">
                                                <span class="bg-white px-3 text-base font-semibold text-gray-900">Proprietários</span>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <div v-if="owners && owners.length > 0" class="space-y-2">
                                                <div v-for="owner in owners" :key="owner.id" class="text-base text-gray-700">
                                                    <h3>{{ owner.name }} - {{ getOwnershipTypeName(owner.pivot?.type_ownership_id) }}</h3>
                                                </div>
                                            </div>
                                            <div v-else class="text-base text-gray-500">
                                                <p>Informações dos proprietários não disponíveis.</p>
                                            </div>
                                        </div>

                                        <section aria-labelledby="details-heading" class="mt-12">
                                            <h2 id="details-heading" class="sr-only">Additional details</h2>

                                            <div class="divide-y divide-gray-200 border-t">
                                                <Disclosure as="div" v-for="detail in visibleDocuments" :key="detail.id" v-slot="{ open }">
                                                    <h3>
                                                        <DisclosureButton
                                                            class="group relative flex w-full items-center justify-between py-6 text-left">
                                                            <!-- ✅ MODIFICAÇÃO: Mostrar indicador de visibilidade para proprietários -->
                                                            <span :class="[open ? 'text-indigo-600' : 'text-gray-900', 'text-sm font-medium flex items-center']">
                                                                {{ detail.name }}
                                                                <!-- Badge de visibilidade para proprietários -->
                                                                <span v-if="canViewAllDocuments && !isDocumentVisibleToServiceProviders(detail)" 
                                                                      class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                                    Oculto para prestadores
                                                                </span>
                                                                <span v-else-if="canViewAllDocuments && isDocumentVisibleToServiceProviders(detail)" 
                                                                      class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                                    Visível para prestadores
                                                                </span>
                                                            </span>
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
                                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center" v-tooltip="'Vencimento do documento'">{{ detail.date === null ? "Sem Data" : new Date(detail.date).toLocaleDateString('pt-BR') }}</td>
                                                                        <!-- ✅ MODIFICAÇÃO: Só mostrar controle de visibilidade para proprietários -->
                                                                        <td v-if="canViewAllDocuments" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center" @click="showDocumentShowModal" v-tooltip.top-start="'Clique para mudar a visibilidade do documento'">
                                                                            <button v-if="detail.show === 1" class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-700">
                                                                                <svg class="size-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                    <circle cx="3" cy="3" r="3" />
                                                                                </svg>
                                                                                Visível para prestadores
                                                                            </button>
                                                                            <button v-else class="inline-flex items-center gap-x-1.5 rounded-md bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700">
                                                                                <svg class="size-1.5 fill-orange-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                    <circle cx="3" cy="3" r="3" />
                                                                                </svg>
                                                                                Oculto para prestadores
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
                                                                                        <div v-if="canViewAllDocuments" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                                                                            
                                                                                            <h3 v-if="detail.show === 0" class="text-lg font-medium text-gray-900 mb-4">Tornar documento visível para prestadores?</h3>
                                                                                            <h3 v-else class="text-lg font-medium text-gray-900 mb-4">Ocultar documento dos prestadores?</h3>
                                                                                            <div>
                                                                                                <div class="mb-4">
                                                                                                    <h3 v-if="detail.show === 0">Tornar o arquivo visível para prestadores de serviço?</h3>
                                                                                                    <h3 v-else>Ocultar o arquivo dos prestadores de serviço?</h3>
                                                                                                    <p class="text-sm text-gray-600 mt-2">
                                                                                                        <strong>Nota:</strong> Como proprietário, você sempre poderá visualizar todos os documentos da sua propriedade, independente desta configuração.
                                                                                                    </p>
                                                                                                </div>
                                                                                                
                                                                                                <div class="flex justify-end">
                                                                                                    <button @click="showModalDocumentShow = false" class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400">
                                                                                                        Cancelar
                                                                                                    </button>
                                                                                                    
                                                                                                    <button @click="documentShow(detail.id)" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                                                                                                        Confirmar
                                                                                                    </button>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div v-else class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
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
                                        
                                        <!-- ✅ CORREÇÃO: Botões de Ação com Permissões Corretas -->
                                        <div class="mt-8 border-t border-gray-200 pt-6">
                                            <div class="flex flex-col sm:flex-row gap-4 justify-end">
                                                
                                                <!-- Botão de Ver Avaliações (Proprietários e Prestadores autorizados) -->
                                                <button 
                                                    v-if="auth.user.profile_id !== 2"
                                                    @click="showEvaluationPropriety(props.property.id)" 
                                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                                                    :title="`Ver ${(props.evaluations && Array.isArray(props.evaluations)) ? props.evaluations.length : 0} avaliações desta propriedade`"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l-1-3m1 3l-1-3m-16.5-3h9v-8.25m0 0h1.5m-1.5 0V9m-1.5-1.5h1.5v1.5" />
                                                    </svg>
                                                    Ver Avaliações ({{ (props.evaluations && Array.isArray(props.evaluations)) ? props.evaluations.length : 0 }})
                                                </button>
                                                
                                                <!-- Botão de Avaliar (Prestadores autorizados) -->
                                                <button 
                                                    v-if="canEvaluateProperty"
                                                    @click="goToEvaluation(props.property.id)" 
                                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                                    title="Criar nova avaliação desta propriedade"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                                                    </svg>
                                                    Avaliar Propriedade
                                                </button>
                                                
                                                <!-- Botão de Editar -->
                                                <button 
                                                    v-if="props.canEdit"
                                                    @click="goToPropriety(props.property.id)" 
                                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                                                    title="Editar informações desta propriedade"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
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
        </div>

        <!-- Modal para Visualizar KML -->
        <Teleport to="body">
            <transition name="modal">
                <div v-if="showKmlModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl h-5/6 mx-4">
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-medium text-gray-900">Visualização de KML</h3>
                            <button @click="showKmlModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="p-4" style="height: calc(100% - 140px);">
                            <KmlMap 
                                v-if="selectedKmlUrl" 
                                ref="kmlMapRef"
                                :kmlUrl="selectedKmlUrl" 
                                height="100%"
                            />
                            <div v-else class="flex items-center justify-center h-full">
                                <p class="text-gray-500">Nenhum KML disponível para esta propriedade.</p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end p-4 border-t bg-gray-50">
                            <button @click="showKmlModal = false" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- Modal de Avaliação da Propriedade -->
        <PropertyEvaluationModal
            :show="showEvaluationModal"
            :property="props.property"
            @close="showEvaluationModal = false"
            @success="handleEvaluationSuccess"
        />

        <!-- Modal de Lista de Avaliações -->
        <EvaluationsListModal
            :show="showEvaluationsListModal"
            :property="props.property"
            :evaluations="props.evaluations || []"
            @close="showEvaluationsListModal = false"
            @open-evaluation-modal="openEvaluationFromList"
        />

    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white {
  transform: scale(0.9);
}

.showModalDocumentShow-enter-active, .showModalDocumentShow-leave-active {
  transition: opacity 0.3s ease;
}

.showModalDocumentShow-enter-from, .showModalDocumentShow-leave-to {
  opacity: 0;
}
</style>