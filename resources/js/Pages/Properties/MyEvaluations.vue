<template>
  <PageLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Minhas Avaliações</h2>
          <p class="text-gray-600 mt-1">Gerencie todas as suas avaliações imobiliárias</p>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total de Avaliações</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
              </div>
              <div class="bg-blue-100 p-3 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Confirmadas</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.confirmed }}</p>
              </div>
              <div class="bg-green-100 p-3 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pendentes</p>
                <p class="text-2xl font-bold text-amber-600">{{ stats.pending }}</p>
              </div>
              <div class="bg-amber-100 p-3 rounded-lg">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-blue-100 mb-1">Valor Total</p>
                <p class="text-2xl font-bold text-white">{{ formatCurrency(stats.totalValue) }}</p>
              </div>
              <div class="bg-white/20 p-3 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
          <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="searchTerm"
                type="text"
                placeholder="Buscar por imóvel ou avaliador..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div class="flex gap-2">
              <button
                @click="filterStatus = 'all'"
                :class="[
                  'px-4 py-2.5 rounded-lg font-medium transition-colors',
                  filterStatus === 'all'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Todas
              </button>
              <button
                @click="filterStatus = 'confirmed'"
                :class="[
                  'px-4 py-2.5 rounded-lg font-medium transition-colors',
                  filterStatus === 'confirmed'
                    ? 'bg-green-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Confirmadas
              </button>
              <button
                @click="filterStatus = 'pending'"
                :class="[
                  'px-4 py-2.5 rounded-lg font-medium transition-colors',
                  filterStatus === 'pending'
                    ? 'bg-amber-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Pendentes
              </button>
            </div>
          </div>
        </div>

        <!-- Evaluations List -->
        <div class="space-y-4">
          <div
            v-if="filteredEvaluations.length === 0"
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center"
          >
            <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma avaliação encontrada</h3>
            <p class="text-gray-600">Tente ajustar os filtros de busca</p>
          </div>

          <div
            v-for="evaluation in filteredEvaluations"
            :key="evaluation.id"
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all hover:border-blue-200 group"
          >
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">
                      {{ getPropertyName(evaluation.property) }}
                    </h3>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span>{{ evaluation.appraiser }}</span>
                    </div>
                  </div>
                  <span
                    :class="[
                      'px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1',
                      evaluation.owner_acknowledged
                        ? 'bg-green-100 text-green-700'
                        : 'bg-amber-100 text-amber-700'
                    ]"
                  >
                    <svg v-if="evaluation.owner_acknowledged" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ evaluation.owner_acknowledged ? 'Confirmada' : 'Pendente' }}
                  </span>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <div>
                      <p class="text-xs text-gray-500">Valor Avaliado</p>
                      <p class="text-lg font-bold text-gray-900">
                        {{ formatCurrency(evaluation.valuation) }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                      <p class="text-xs text-gray-500">Data da Avaliação</p>
                      <p class="text-sm font-medium text-gray-700">
                        {{ formatDate(evaluation.created_at) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="canDelete(evaluation)" class="flex items-center">
                <button
                  @click="destroy(evaluation)"
                  class="flex items-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all font-medium border border-red-200 hover:border-red-600"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <span class="hidden sm:inline">Excluir</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  evaluations: Array,
  stats: Object
})

const page = usePage()

const searchTerm = ref('')
const filterStatus = ref('all')

// Estatísticas computadas (usa servidor se disponível)
const stats = computed(() => {
  if (props.stats) return props.stats
  const total = props.evaluations?.length || 0
  const confirmed = props.evaluations?.filter(e => e.owner_acknowledged).length || 0
  const pending = props.evaluations?.filter(e => !e.owner_acknowledged).length || 0
  const totalValue = props.evaluations?.reduce((sum, e) => sum + (Number(e.valuation) || 0), 0) || 0
  return { total, confirmed, pending, totalValue }
})

// Avaliações filtradas
const filteredEvaluations = computed(() => {
  if (!props.evaluations) return []
  
  return props.evaluations.filter(evaluation => {
    const q = (searchTerm.value || '').toString().toLowerCase()
    const propNickname = (evaluation.property?.nickname || '').toString().toLowerCase()
    const propId = (evaluation.property?.id ?? '').toString().toLowerCase()
    const appraiser = (evaluation.appraiser || '').toString().toLowerCase()
    const matchesSearch = propNickname.includes(q) || propId.includes(q) || appraiser.includes(q)
    
    const matchesStatus = 
      filterStatus.value === 'all' ||
      (filterStatus.value === 'confirmed' && evaluation.owner_acknowledged) ||
      (filterStatus.value === 'pending' && !evaluation.owner_acknowledged)
    
    return matchesSearch && matchesStatus
  })
})

function getPropertyName(property) {
  if (property?.nickname) {
    return property.nickname
  }
  const cat = property?.property_category || (property?.type_property === 1 ? 'urban' : property?.type_property === 2 ? 'rural' : null)
  if (cat === 'urban') {
    return 'Propriedade Urbana'
  }
  return 'Propriedade Rural'
}

function formatCurrency(value) {
  if (value == null || value === '') return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value))
}

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function canDelete(row) {
  return page.props.auth?.user?.id === row.user_id && !row.owner_acknowledged
}

function destroy(row) {
  if (!confirm('Excluir esta avaliação? Esta ação não pode ser desfeita.')) return
  router.delete(route('properties.evaluations.destroy', { property: row.property_id, evaluation: row.id }))
}
</script>