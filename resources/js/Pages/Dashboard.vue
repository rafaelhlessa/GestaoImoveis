<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  ArcElement,
  BarElement
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
  Filler,
  ArcElement,
  BarElement
);

const { props } = usePage();

// Props vindas do controller
const valuationData = ref(props.valuationData || { urban: [], commercial: [], rural: [] });
const stats = ref(props.stats || {});
const properties = ref(props.properties || []);
const recentEvaluations = ref(props.latestEvaluations || []);
const evaluation = ref(props.evaluation || {});

const isLoading = ref(false);

// Verificar se o usuário deve ser redirecionado
onMounted(() => {
    
    console.log('Stats:', stats.value);
    
    // Access properties from the first evaluation if the array is not empty
    if (recentEvaluations.value && recentEvaluations.value.length > 0) {
        const firstEvaluation = recentEvaluations.value[0];
        console.log('First Evaluation:', firstEvaluation);
        if (firstEvaluation.properties && firstEvaluation.properties.length > 0) {
            console.log('First Property:', firstEvaluation.properties[0]);
        }
    }

if (props.auth.user.profile_id > 1) {
    router.get(route('dashboard'));
}
});

// Dados para o gráfico de valorização principal
const mainChartData = computed(() => {
    const allMonths = new Set();
    
    [...valuationData.value.urban, ...valuationData.value.commercial, ...valuationData.value.rural]
        .forEach(item => allMonths.add(item.month));
    
    const sortedMonths = Array.from(allMonths).sort();
    
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
                data: sortedMonths.map(month => getValueForMonth(valuationData.value.urban, month)),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            },
            {
                label: 'Propriedades Comerciais',
                data: sortedMonths.map(month => getValueForMonth(valuationData.value.commercial, month)),
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            },
            {
                label: 'Propriedades Rurais',
                data: sortedMonths.map(month => getValueForMonth(valuationData.value.rural, month)),
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4,
                spanGaps: true
            }
        ]
    };
});

// Dados para o gráfico de distribuição por tipo
const distributionChartData = computed(() => {
    const types = stats.value.propertiesByType || {};
    return {
        labels: ['Urbanas', 'Comerciais', 'Rurais'],
        datasets: [{
            data: [
                types.Urbanas || 0,
                types.Comerciais || 0,
                types.Rurais || 0
            ],
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
});

// Dados para gráfico de avaliações mensais
const evaluationsChartData = computed(() => {
    const monthlyEvals = stats.value.monthlyEvaluations || [];
    return {
        labels: monthlyEvals.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('pt-BR', { month: 'short' });
        }),
        datasets: [{
            label: 'Avaliações realizadas',
            data: monthlyEvals.map(item => item.count),
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderColor: '#6366F1',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    };
});

// Opções dos gráficos
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
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
            grid: { display: false }
        },
        y: {
            display: true,
            ticks: {
                callback: function(value) {
                    return 'R$ ' + new Intl.NumberFormat('pt-BR', { 
                        notation: 'compact', 
                        compactDisplay: 'short' 
                    }).format(value);
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

const distributionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((context.parsed * 100) / total).toFixed(1);
                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                }
            }
        }
    }
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        x: { grid: { display: false } },
        y: { 
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    }
};

// Cards de estatísticas principais
const mainStats = computed(() => [
    {
        id: 1,
        name: 'Total de Propriedades',
        value: stats.value.totalProperties || 0,
        change: `+${stats.value.lastMonthGrowth || 0}%`,
        changeType: (stats.value.lastMonthGrowth || 0) > 0 ? 'positive' : 'negative',
        icon: '🏘️',
        description: 'Propriedades cadastradas'
    },
    {
        id: 2,
        name: 'Valor Total do Portfólio',
        value: 'R$ ' + new Intl.NumberFormat('pt-BR', { 
            notation: 'compact', 
            compactDisplay: 'short' 
        }).format(stats.value.totalValue || 0),
        change: `+${stats.value.valueGrowth || 0}%`,
        changeType: (stats.value.valueGrowth || 0) > 0 ? 'positive' : 'negative',
        icon: '💰',
        description: 'Valor patrimonial total'
    },
    {
        id: 3,
        name: 'Avaliações Realizadas',
        value: stats.value.totalEvaluations || 0,
        change: `+${stats.value.evaluationsGrowth || 0}%`,
        changeType: (stats.value.evaluationsGrowth || 0) > 0 ? 'positive' : 'negative',
        icon: '📊',
        description: 'Avaliações até hoje'
    },
    {
        id: 4,
        name: 'Valorização Média',
        value: `${stats.value.averageAppreciation || 0}%`,
        change: 'Últimos 12 meses',
        changeType: 'neutral',
        icon: '📈',
        description: 'Taxa de valorização'
    }
]);

// Status types
const statuses = {
    positive: 'text-green-700 bg-green-50 ring-green-600/20',
    negative: 'text-red-700 bg-red-50 ring-red-600/10',
    neutral: 'text-blue-700 bg-blue-50 ring-blue-600/20',
};

// Verificar se há dados
const hasData = computed(() => {
    return valuationData.value.urban.length > 0 || 
           valuationData.value.commercial.length > 0 || 
           valuationData.value.rural.length > 0;
});

// Navegar para propriedade específica
const viewProperty = (propertyId) => {
    console.log('Navegando para propriedade:', propertyId);
    router.get(route('property.show', propertyId));
};

const viewProperties = () => {
    console.log('Navegando para todas as propriedades');
    router.get(route('property.index'));
};

const lastEvaluation = (recentEvaluations) => {
    console.log('Última avaliação:', recentEvaluations);

}

const lastEvaluations = Object.values(recentEvaluations.value).map((evaluation) => {
        // Check if the evaluation has properties and verify ID match
        if (evaluation.properties && evaluation.properties.length > 0) {
            const propertyId = evaluation.properties[0].id;
            const evaluationPropertyId = evaluation.property_id;
            
            if (propertyId !== evaluationPropertyId) {
                console.warn(`Property ID mismatch: ${propertyId} vs ${evaluationPropertyId}`);
            }
        }
        return evaluation.valuation;
    });

// Formatação de valores
const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        notation: 'compact',
        compactDisplay: 'short'
    }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
};

const formatDateToBRManual = (dateString) => {

    console.log('Data recebida:', dateString);
  // Verificar se a data existe e não está vazia
  if (!dateString || dateString === null || dateString === undefined) {
    return '-'; // ou return 'Data não informada';
  }
  
  const date = new Date(dateString);
  
  // Verificar se a data é válida
  if (isNaN(date.getTime())) {
    console.error('Data inválida:', dateString);
    return '-'; // ou return 'Data inválida';
  }
  
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};
</script>

<template>
    <Head title="Dashboard - Portfólio de Propriedades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Meu Portfólio de Propriedades
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Acompanhe a valorização e performance das suas propriedades
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Última atualização</div>
                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ formatDate(new Date()) }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Cards de Estatísticas Principais -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <div 
                        v-for="stat in mainStats" 
                        :key="stat.id"
                        class="relative overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-3xl">{{ stat.icon }}</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ stat.name }}
                                </p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ stat.value }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ stat.description }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span 
                                :class="[
                                    statuses[stat.changeType], 
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset'
                                ]"
                            >
                                {{ stat.change }}
                            </span>
                        </div>
                        <!-- Gradiente decorativo -->
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-blue-400/10 to-purple-400/10"></div>
                    </div>
                </div>

                <!-- Gráficos Principais -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    
                    <!-- Gráfico de Valorização Principal -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Evolução da Valorização
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Acompanhe a valorização das suas propriedades por tipo
                                </p>
                            </div>
                            <div class="text-sm text-gray-400">Últimos 12 meses</div>
                        </div>
                        
                        <div v-if="isLoading" class="flex justify-center items-center h-64">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                        
                        <div v-else-if="!hasData" class="flex flex-col items-center justify-center h-64 text-gray-500">
                            <div class="text-6xl mb-4">📊</div>
                            <p class="text-lg font-medium">Nenhuma avaliação encontrada</p>
                            <p class="text-sm text-center">Realize avaliações das suas propriedades para<br>visualizar o gráfico de valorização</p>
                        </div>
                        
                        <div v-else class="h-80">
                            <Line 
                                :data="mainChartData" 
                                :options="chartOptions"
                                style="height: 100%; width: 100%"
                            />
                        </div>
                    </div>

                    <!-- Distribuição por Tipo -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                Distribuição do Portfólio
                            </h3>
                            <p class="text-sm text-gray-500">
                                Composição por tipo de propriedade
                            </p>
                        </div>
                        
                        <div v-if="hasData" class="h-64">
                            <Doughnut 
                                :data="distributionChartData" 
                                :options="distributionOptions"
                            />
                        </div>
                        
                        <div v-else class="flex items-center justify-center h-64 text-gray-400">
                            <div class="text-center">
                                <div class="text-4xl mb-2">🏠</div>
                                <p class="text-sm">Sem dados</p>
                            </div>
                        </div>
                        
                        <!-- Resumo numérico -->
                        <div v-if="hasData" class="mt-6 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                    <span>Urbanas</span>
                                </div>
                                <span class="font-medium">{{ stats.propertiesByType?.Urbanas || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                    <span>Comerciais</span>
                                </div>
                                <span class="font-medium">{{ stats.propertiesByType?.Comerciais || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                                    <span>Rurais</span>
                                </div>
                                <span class="font-medium">{{ stats.propertiesByType?.Rurais || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda linha de gráficos -->
                <!--  -->

                <!-- Lista de Propriedades Recentes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Suas Propriedades
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Visão geral das suas propriedades mais importantes
                                </p>

                            </div>
                            <button @click="viewProperties" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Ver Todas
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div v-if="properties.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div 
                                v-for="property in properties.slice(0, 6)" 
                                :key="property.id"
                                @click="viewProperty(property.id)"
                                class="group relative bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 hover:from-blue-50 hover:to-blue-100 transition-all duration-200 cursor-pointer border hover:border-blue-200"
                            >
                            
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 group-hover:text-blue-900 transition-colors">
                                            {{ property.address || property.description }}
                                        </h4>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ property.city }}, {{ property.state }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ property.type }}
                                    </span>
                                </div>
                                
                                <div v-for="evaluation in property.evaluations.slice(0, 1)" class="flex items-center justify-between">
                                    <div class="text-left">
                                        <p class="text-sm text-gray-500">Último valor</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ formatCurrency(evaluation.valuation || 0) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">Avaliação</p>
                                        <p class="text-xs text-gray-600">
                                            {{ formatDateToBRManual(evaluation.created_at || '') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Indicador de valorização -->
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex items-center text-xs">
                                        <span :class="property.appreciation >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ property.appreciation >= 0 ? '↗' : '↘' }}
                                            {{ Math.abs(property.appreciation || 0) }}%
                                        </span>
                                        <span class="text-gray-500 ml-1">no período</span>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="text-6xl mb-4">🏘️</div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Nenhuma propriedade cadastrada</h4>
                            <p class="text-sm text-gray-500 mb-4">Cadastre suas propriedades para começar a acompanhar a valorização</p>
                            <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Cadastrar Propriedade
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animações personalizadas */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}
</style>