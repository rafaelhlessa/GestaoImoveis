<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { 
    UserGroupIcon, 
    HomeIcon, 
    ClipboardDocumentCheckIcon,
    EyeIcon,
    DocumentTextIcon,
    ChartBarIcon,
    BriefcaseIcon,
    CalendarIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    StarIcon
} from '@heroicons/vue/24/outline';
import { 
    UserGroupIcon as UserGroupIconSolid,
    HomeIcon as HomeIconSolid, 
    ClipboardDocumentCheckIcon as ClipboardDocumentCheckIconSolid
} from '@heroicons/vue/24/solid';

const props = defineProps({
    clients: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    user: {
        type: Object,
        default: () => ({})
    }
});

const currentTime = ref(new Date());
const searchQuery = ref('');
const selectedFilter = ref('all');

onMounted(() => {
    // Atualizar hora a cada minuto
    setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
});

// Computed para saudação baseada na hora
const greeting = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 12) return '☀️ Bom dia';
    if (hour < 18) return '🌤️ Boa tarde';
    return '🌙 Boa noite';
});

// Filtrar clientes baseado na busca e filtro
const filteredClients = computed(() => {
    let clients = props.clients || [];
    
    // Filtrar por busca
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        clients = clients.filter(client => 
            client.name?.toLowerCase().includes(query) ||
            client.email?.toLowerCase().includes(query) ||
            client.cpf_cnpj?.includes(query)
        );
    }
    
    // Filtrar por tipo
    if (selectedFilter.value !== 'all') {
        clients = clients.filter(client => {
            if (selectedFilter.value === 'owner') return client.profile_id === 1;
            if (selectedFilter.value === 'mixed') return client.profile_id === 3;
            return true;
        });
    }
    
    return clients;
});

// Cards de estatísticas
const statsCards = computed(() => [
    {
        id: 1,
        title: 'Total de Clientes',
        value: props.clients?.length || 0,
        icon: UserGroupIconSolid,
        color: 'bg-blue-500',
        change: '+2 este mês',
        changeType: 'positive'
    },
    {
        id: 2,
        title: 'Propriedades Acessíveis',
        value: props.stats.totalProperties || 0,
        icon: HomeIconSolid,
        color: 'bg-green-500',
        change: 'Ver todas',
        changeType: 'neutral'
    },
    {
        id: 3,
        title: 'Avaliações Realizadas',
        value: props.stats.totalEvaluations || 0,
        icon: ClipboardDocumentCheckIconSolid,
        color: 'bg-purple-500',
        change: '+5 esta semana',
        changeType: 'positive'
    }
]);

// Aplicar máscara de telefone
const formatPhone = (phone) => {
    if (!phone) return 'Não informado';
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 3)} ${cleaned.slice(3, 7)}-${cleaned.slice(7)}`;
    }
    if (cleaned.length === 10) {
        return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 6)}-${cleaned.slice(6)}`;
    }
    return phone;
};

// Aplicar máscara CPF/CNPJ
const formatDocument = (doc) => {
    if (!doc) return 'Não informado';
    const cleaned = doc.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6, 9)}-${cleaned.slice(9)}`;
    }
    if (cleaned.length === 14) {
        return `${cleaned.slice(0, 2)}.${cleaned.slice(2, 5)}.${cleaned.slice(5, 8)}/${cleaned.slice(8, 12)}-${cleaned.slice(12)}`;
    }
    return doc;
};

// Navegar para propriedades do cliente
const viewClientProperties = (clientId) => {
    router.get(route('clients.property', { id: clientId }));
};

// Ações rápidas
const quickActions = [
    {
        id: 1,
        title: 'Ver Todas as Propriedades',
        description: 'Acesse todas as propriedades que você tem autorização',
        icon: HomeIcon,
        color: 'bg-blue-50 text-blue-600',
        action: () => router.get('/properties')
    },
    {
        id: 2,
        title: 'Realizar Avaliação',
        description: 'Criar uma nova avaliação de propriedade',
        icon: ClipboardDocumentCheckIcon,
        color: 'bg-green-50 text-green-600',
        action: () => router.get('/evaluations/create')
    },
    {
        id: 3,
        title: 'Relatórios',
        description: 'Visualizar relatórios e estatísticas',
        icon: ChartBarIcon,
        color: 'bg-purple-50 text-purple-600',
        action: () => router.get('/reports')
    }
];
</script>

<template>
    <Head title="Dashboard - Prestador de Serviços" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ greeting }}, {{ user.name }}!
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Gerencie seus clientes e propriedades autorizadas
                    </p>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <CalendarIcon class="w-4 h-4" />
                    <span>{{ currentTime.toLocaleDateString('pt-BR', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    }) }}</span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        v-for="card in statsCards"
                        :key="card.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all duration-300"
                    >
                        <div class="flex items-center">
                            <div :class="['p-3 rounded-xl', card.color]">
                                <component :is="card.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ card.title }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {{ card.value }}
                                </p>
                                <p :class="[
                                    'text-xs mt-1',
                                    card.changeType === 'positive' ? 'text-green-600' : 'text-gray-500'
                                ]">
                                    {{ card.change }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <!-- <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <BriefcaseIcon class="w-5 h-5 mr-2 text-indigo-600" />
                        Ações Rápidas
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button
                            v-for="action in quickActions"
                            :key="action.id"
                            @click="action.action"
                            class="flex items-start p-4 rounded-xl border border-gray-200 dark:border-gray-600 hover:shadow-sm transition-all duration-200 hover:border-indigo-300 group text-left"
                        >
                            <div :class="['p-2 rounded-lg mr-3 group-hover:scale-110 transition-transform', action.color]">
                                <component :is="action.icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ action.title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ action.description }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div> -->

                <!-- Lista de Clientes -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <UserGroupIcon class="w-5 h-5 mr-2 text-indigo-600" />
                                Meus Clientes ({{ filteredClients.length }})
                            </h3>
                            
                            <!-- Barra de busca e filtros -->
                            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Buscar cliente..."
                                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <select
                                    v-model="selectedFilter"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
                                >
                                    <option value="all">Todos os tipos</option>
                                    <option value="owner">Proprietários</option>
                                    <option value="mixed">Perfil Misto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Conteúdo da lista de clientes -->
                    <div class="p-6">
                        <div v-if="filteredClients.length === 0" class="text-center py-12">
                            <UserGroupIcon class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                {{ searchQuery ? 'Nenhum cliente encontrado' : 'Nenhum cliente autorizado' }}
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ searchQuery ? 'Tente ajustar sua busca' : 'Aguarde os proprietários autorizarem seu acesso às propriedades' }}
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div
                                v-for="client in filteredClients"
                                :key="client.id"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 hover:shadow-md transition-all duration-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="viewClientProperties(client.id)"
                            >
                                <!-- Header do card -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                            <span class="text-white font-bold text-lg">
                                                {{ client.name?.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ client.name }}
                                            </h4>
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                client.profile_id === 1 
                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                            ]">
                                                {{ client.profile_id === 1 ? 'Proprietário' : 'Perfil Misto' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <StarIcon class="w-4 h-4 text-yellow-400 fill-current" />
                                        <span class="text-sm text-gray-600 dark:text-gray-400">4.8</span>
                                    </div>
                                </div>

                                <!-- Informações de contato -->
                                <div class="space-y-3">
                                    <div class="flex items-center text-sm">
                                        <EnvelopeIcon class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                        <span class="text-gray-600 dark:text-gray-300 truncate">
                                            {{ client.email || 'Email não informado' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <PhoneIcon class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                        <span class="text-gray-600 dark:text-gray-300">
                                            {{ formatPhone(client.phone) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <DocumentTextIcon class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                        <span class="text-gray-600 dark:text-gray-300">
                                            {{ formatDocument(client.cpf_cnpj) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <MapPinIcon class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                        <span class="text-gray-600 dark:text-gray-300">
                                            {{ client.city || 'Cidade não informada' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Ações do card -->
                                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex space-x-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <EyeIcon class="w-3 h-3 mr-1" />
                                            Ver Docs
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <HomeIcon class="w-3 h-3 mr-1" />
                                            Propriedades
                                        </span>
                                    </div>
                                    <button 
                                        @click.stop="viewClientProperties(client.id)"
                                        class="text-indigo-600 hover:text-indigo-800 font-medium text-sm transition-colors"
                                    >
                                        Ver Detalhes →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animações personalizadas */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.bg-gradient-to-br {
    animation: fadeIn 0.3s ease-out;
}
</style>