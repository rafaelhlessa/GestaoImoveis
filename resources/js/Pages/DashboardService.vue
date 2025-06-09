<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { defineProps, ref, computed, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { EllipsisHorizontalIcon, UserIcon, HomeIcon, BriefcaseIcon } from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';

// Registrar componentes do Chart.js
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
    serviceProviders: Array,
    valuationData: {
        type: Object,
        default: () => ({ urban: [], commercial: [], rural: [] })
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    showGraphs: {
        type: Boolean,
        default: false
    },
    showClients: {
        type: Boolean,
        default: false
    },
    user: {
        type: Object,
        default: () => ({})
    }
});

const isLoading = ref(false);

// Estado para controlar a visualização atual (apenas para perfil 3)
const currentView = ref('owner'); // 'owner' ou 'provider'

const authUser = props.user;

onMounted(() => {
    console.log('Perfil do usuário:', props.user);
    
    // Para perfil 3, iniciar com visualização de proprietário
    if (props.user.profile_id === 3) {
        currentView.value = 'owner';
    }
});

// Função para alternar visualização
const switchView = (view) => {
    currentView.value = view;
};

// Verificar se deve mostrar o seletor de visualização
const shouldShowViewSelector = computed(() => {
    return props.user.profile_id === 3;
});

// Determinar se deve mostrar gráficos baseado na visualização atual
const shouldShowGraphs = computed(() => {
    if (props.user.profile_id === 1) return true; // Perfil 1 sempre mostra
    if (props.user.profile_id === 2) return false; // Perfil 2 nunca mostra
    if (props.user.profile_id === 3) {
        return currentView.value === 'owner'; // Perfil 3 mostra apenas na visualização de proprietário
    }
    return false;
});

// Determinar se deve mostrar lista de clientes baseado na visualização atual
const shouldShowClients = computed(() => {
    if (props.user.profile_id === 1) return false; // Perfil 1 nunca mostra
    if (props.user.profile_id === 2) return true; // Perfil 2 sempre mostra
    if (props.user.profile_id === 3) {
        return currentView.value === 'provider'; // Perfil 3 mostra apenas na visualização de prestador
    }
    return false;
});

// Filtrar clientes para remover o próprio usuário da lista
const filteredServiceProviders = computed(() => {
    if (!props.serviceProviders) return [];
    
    return props.serviceProviders.filter(client => {
        // Remove o próprio usuário da lista de clientes
        return client.id !== props.user.id;
    });
});

// Verificar se há dados no gráfico
const hasChartData = computed(() => {
    if (!props.valuationData || !shouldShowGraphs.value) return false;
    return props.valuationData.urban.length > 0 || 
           props.valuationData.commercial.length > 0 || 
           props.valuationData.rural.length > 0;
});

// Configuração dos dados do gráfico
const chartData = computed(() => {
    if (!props.valuationData || !shouldShowGraphs.value) {
        return {
            labels: [],
            datasets: []
        };
    }
    
    const allMonths = new Set();
    
    // Coletar todos os meses únicos
    [...props.valuationData.urban, ...props.valuationData.commercial, ...props.valuationData.rural]
        .forEach(item => allMonths.add(item.month));
    
    const sortedMonths = Array.from(allMonths).sort();
    
    // Função para buscar valor por mês
    const getValueForMonth = (data, month) => {
        const item = data.find(d => d.month === month);
        return item ? item.value : null;
    };
    
    return {
        labels: sortedMonths.map(month => {
            const [year, monthNum] = month.split('-');
            const date = new Date(year, monthNum - 1);
            return date.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
        }),
        datasets: [
            {
                label: 'Propriedades Urbanas',
                data: sortedMonths.map(month => getValueForMonth(props.valuationData.urban, month)),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            },
            {
                label: 'Propriedades Comerciais',
                data: sortedMonths.map(month => getValueForMonth(props.valuationData.commercial, month)),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            },
            {
                label: 'Propriedades Rurais',
                data: sortedMonths.map(month => getValueForMonth(props.valuationData.rural, month)),
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            }
        ]
    };
});

// Opções do gráfico
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
        title: {
            display: true,
            text: 'Valorização das Minhas Propriedades'
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function(context) {
                    if (context.parsed.y === null) return null;
                    return context.dataset.label + ': R$ ' + 
                           new Intl.NumberFormat('pt-BR').format(context.parsed.y);
                }
            }
        }
    },
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Período'
            }
        },
        y: {
            display: true,
            title: {
                display: true,
                text: 'Valor Médio (R$)'
            },
            ticks: {
                callback: function(value) {
                    return 'R$ ' + new Intl.NumberFormat('pt-BR').format(value);
                }
            }
        }
    },
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
    }
};

// Estatísticas cards baseadas na visualização atual
const statCards = computed(() => {
    const baseCards = [];
    
    if (shouldShowGraphs.value) {
        // Visualização de proprietário
        baseCards.push(
            {
                id: 1,
                name: 'Minhas Propriedades',
                value: props.stats.totalProperties || 0,
                change: '',
                changeType: 'neutral',
                icon: 'building'
            },
            {
                id: 2,
                name: 'Minhas Avaliações',
                value: props.stats.totalEvaluations || 0,
                change: `${props.stats.lastMonthGrowth > 0 ? '+' : ''}${props.stats.lastMonthGrowth || 0}%`,
                changeType: (props.stats.lastMonthGrowth || 0) > 0 ? 'positive' : 'negative',
                icon: 'chart'
            }
        );
    }
    
    if (shouldShowClients.value) {
        // Visualização de prestador de serviços
        baseCards.push({
            id: 3,
            name: 'Total de Clientes',
            value: filteredServiceProviders.value.length || 0,
            change: '',
            changeType: 'neutral',
            icon: 'users'
        });
    }
    
    return baseCards;
});

// Estatísticas por tipo de propriedade
const propertyTypeStats = computed(() => {
    if (!props.stats || !props.stats.propertiesByType || !shouldShowGraphs.value) return [];
    
    const types = props.stats.propertiesByType || {};
    return [
        { name: 'Urbanas', count: types.Urbanas || 0, color: 'bg-blue-500' },
        { name: 'Comerciais', count: types.Comerciais || 0, color: 'bg-green-500' },
        { name: 'Rurais', count: types.Rurais || 0, color: 'bg-yellow-500' }
    ];
});

// Função para renderizar ícones
const getIconComponent = (iconType) => {
    const icons = {
        users: '👥',
        building: '🏠', 
        chart: '📊'
    };
    return icons[iconType] || '📄';
};

const statuses = {
  Paid: 'text-green-700 bg-green-50 ring-green-600/20',
  Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
  Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
  positive: 'text-green-700 bg-green-50 ring-green-600/20',
  negative: 'text-red-700 bg-red-50 ring-red-600/10',
  neutral: 'text-gray-600 bg-gray-50 ring-gray-500/10',
}

// Função para visualizar propriedades conforme perfil do usuário
const navigateToProperties = () => {
    // Perfil 1 (proprietário) e perfil 3 (misto) acessam suas próprias propriedades
    if (props.user.profile_id === 1 || props.user.profile_id === 3) {
        router.get(route('property.index'));
    }
};

// Função para visualizar propriedades do cliente (apenas para perfis 2 e 3)
const viewClientProperties = (clientId) => {
    // Perfil 2 (prestador) e perfil 3 (misto) podem ver propriedades dos clientes
    if (props.user.profile_id === 2 || props.user.profile_id === 3) {
        router.get(route('clients.property', { id: clientId }));
    } else {
        console.error('Acesso não permitido para este perfil');
    }
};

// Função para aplicar a máscara de Telefone
const applyPhoneMask = (value) => {
    if (!value) return '';
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

// Computed para o título da página baseado na visualização
const pageTitle = computed(() => {
    if (props.user.profile_id === 1) return 'Dashboard - Proprietário';
    if (props.user.profile_id === 2) return 'Dashboard - Prestador de Serviços';
    if (props.user.profile_id === 3) {
        return currentView.value === 'owner' 
            ? 'Dashboard - Proprietário' 
            : 'Dashboard - Prestador de Serviços';
    }
    return 'Dashboard';
});
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ pageTitle }}
                </h2>
                
                <!-- Seletor de Visualização para Perfil 3 -->
                <div v-if="shouldShowViewSelector" class="flex bg-gray-100 rounded-lg p-1">
                    <button
                        @click="switchView('owner')"
                        :class="[
                            'flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                            currentView === 'owner' 
                                ? 'bg-white text-gray-900 shadow-sm' 
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <HomeIcon class="w-4 h-4 mr-1.5" />
                        Proprietário
                    </button>
                    <button
                        @click="switchView('provider')"
                        :class="[
                            'flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                            currentView === 'provider' 
                                ? 'bg-white text-gray-900 shadow-sm' 
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <BriefcaseIcon class="w-4 h-4 mr-1.5" />
                        Prestador
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div 
                        v-for="card in statCards" 
                        :key="card.id"
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">{{ getIconComponent(card.icon) }}</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ card.name }}
                                </p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    {{ card.value }}
                                </p>
                            </div>
                            <div v-if="card.change" class="flex items-center">
                                <span 
                                    :class="[
                                        statuses[card.changeType], 
                                        'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                                    ]"
                                >
                                    {{ card.change }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Valorização (apenas para visualização de proprietário) -->
                <div v-if="shouldShowGraphs" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Valorização das Minhas Propriedades
                            </h3>
                            <div class="text-sm text-gray-500">
                                Últimos 12 meses
                            </div>
                        </div>
                        
                        <div v-if="isLoading" class="flex justify-center items-center h-64">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                        
                        <div v-else-if="!hasChartData" class="flex flex-col items-center justify-center h-64 text-gray-500">
                            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                            <p class="text-lg font-medium">Nenhuma avaliação encontrada</p>
                            <p class="text-sm">Realize avaliações para visualizar os dados</p>
                        </div>
                        
                        <div v-else class="h-64">
                            <Line 
                                :data="chartData" 
                                :options="chartOptions"
                                style="height: 100%; width: 100%"
                            />
                        </div>

                        <!-- Resumo por tipo de propriedade -->
                        <div v-if="hasChartData" class="mt-6 grid grid-cols-3 gap-4 text-sm">
                            <div 
                                v-for="type in propertyTypeStats" 
                                :key="type.name"
                                class="text-center p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center justify-center mb-1">
                                    <div :class="['w-3 h-3 rounded-full mr-2', type.color]"></div>
                                    <span class="font-medium">{{ type.name }}</span>
                                </div>
                                <p class="text-2xl font-bold text-gray-900">{{ type.count }}</p>
                                <p class="text-gray-600">propriedades</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Clientes (apenas para visualização de prestador) -->
                <div v-if="shouldShowClients" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">
                            Meus Clientes
                        </h3>
                        
                        <div v-if="filteredServiceProviders.length === 0" class="text-center py-8 text-gray-500">
                            <UserIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                            <p class="text-lg font-medium">Nenhum cliente encontrado</p>
                            <p class="text-sm">Você ainda não possui clientes autorizados.</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-2 xl:grid-cols-3">
                            <div 
                                v-for="client in filteredServiceProviders" 
                                :key="client.id"
                                class="overflow-hidden rounded-xl border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
                                @click="viewClientProperties(client.id)"
                            >
                                <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6">
                                    <div class="size-12 flex-none rounded-lg bg-white flex items-center justify-center ring-1 ring-gray-900/10">
                                        <UserIcon class="h-6 w-6 text-gray-600" />
                                    </div>
                                    <div class="text-sm/6 font-medium text-gray-900 flex-1">
                                        {{ client.name }}
                                    </div>
                                    <Menu as="div" class="relative">
                                        <MenuButton
                                            class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500"
                                            @click.stop
                                        >
                                            <span class="sr-only">Abrir opções</span>
                                            <EllipsisHorizontalIcon class="size-5" aria-hidden="true" />
                                        </MenuButton>
                                        <transition 
                                            enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95"
                                        >
                                            <MenuItems
                                                class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
                                            >
                                                <MenuItem v-slot="{ active }">
                                                    <button
                                                        @click.stop="viewClientProperties(client.id)"
                                                        :class="[active ? 'bg-gray-50 outline-none' : '', 'block w-full text-left px-3 py-1 text-sm/6 text-gray-900']"
                                                    >
                                                        Ver Propriedades
                                                    </button>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                                <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm/6">
                                    <div class="flex justify-between gap-x-4 py-3">
                                        <dt class="text-gray-500">CPF/CNPJ</dt>
                                        <dd class="text-gray-700">
                                            {{ client.cpf_cnpj || 'Não informado' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-x-4 py-3">
                                        <dt class="text-gray-500">Tipo</dt>
                                        <dd class="flex items-start gap-x-2">
                                            <div class="font-medium text-gray-900">
                                                {{ client.profile_id === 1 ? 'Proprietário' : 'Misto' }}
                                            </div>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Estilos adicionais se necessário */
</style>