<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  property: { type: Object, required: true },
});

const emit = defineEmits(['close', 'new']);

const loading = ref(false);
const error = ref(null);
const batches = ref([]);

async function load() {
  error.value = null;
  loading.value = true;
  try {
    const url = route('properties.veterinary.index', { property: props.property.id });
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error('Falha ao carregar declarações');
  batches.value = await res.json();
  } catch (e) {
    error.value = e.message || 'Erro ao carregar declarações';
  } finally {
    loading.value = false;
  }
}

watch(() => props.show, (val) => { if (val) load(); });

function formatDate(d) {
  try { 
    return new Date(d).toLocaleDateString('pt-BR', { 
      day: '2-digit', 
      month: 'short', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch { 
    return d ?? '-'; 
  }
}

async function generatePdf() {
  try {
    const url = route('properties.veterinary.pdf', { property: props.property.id });
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Falha ao gerar PDF');
    const data = await res.json();
    if (data?.url) window.open(data.url, '_blank');
  } catch (e) {
    error.value = e.message || 'Erro ao gerar PDF';
  }
}

async function generateSingle(entry) {
  try {
    const url = route('properties.veterinary.single.pdf', { property: props.property.id, declaration: entry.id });
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Falha ao gerar PDF individual');
    const data = await res.json();
    if (data?.url) window.open(data.url, '_blank');
  } catch (e) {
    error.value = e.message || 'Erro ao gerar PDF individual';
  }
}

async function generateBatchPdf(batch) {
  try {
    const url = route('properties.veterinary.batch.pdf', { property: props.property.id, batch: batch.id });
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Falha ao gerar PDF da declaração');
    const data = await res.json();
    if (data?.url) window.open(data.url, '_blank');
  } catch (e) {
    error.value = e.message || 'Erro ao gerar PDF da declaração';
  }
}

// Estatísticas computadas
const stats = computed(() => {
  const allEntries = batches.value.flatMap(b => b.entries || []);
  const totalAnimals = allEntries.reduce((sum, e) => sum + (Number(e.qty_males || 0) + Number(e.qty_females || 0)), 0);
  const totalMales = allEntries.reduce((sum, e) => sum + Number(e.qty_males || 0), 0);
  const totalFemales = allEntries.reduce((sum, e) => sum + Number(e.qty_females || 0), 0);
  const species = [...new Set(allEntries.map(e => e.species))];
  
  return { totalAnimals, totalMales, totalFemales, species: species.length };
});
</script>

<template>
  <transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="relative bg-gradient-to-r from-blue-600 to-indigo-600 p-6">
          <button 
            @click="$emit('close')" 
            class="absolute top-4 right-4 text-white/80 hover:text-white hover:bg-white/10 rounded-lg p-2 transition-all"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
          
          <div class="flex items-center gap-4">
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-2xl font-bold text-white">Declarações Veterinárias</h3>
              <p class="text-blue-100 text-sm mt-1">
                Propriedade: <strong>{{ property.nickname || ('#' + property.id) }}</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
          
          <!-- Error Alert -->
          <div v-if="error" class="mb-6 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <p class="font-medium text-red-900">Erro</p>
              <p class="text-sm text-red-700 mt-1">{{ error }}</p>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex flex-col items-center justify-center py-16">
            <svg class="w-12 h-12 text-blue-600 animate-spin mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600 font-medium">Carregando declarações...</p>
          </div>

          <!-- Stats Cards -->
          <div v-if="!loading && batches.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 mb-1">Total Declarações</p>
                  <p class="text-2xl font-bold text-gray-900">{{ batches.length }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-emerald-100 mb-1">Total de Animais</p>
                  <p class="text-2xl font-bold text-white">{{ stats.totalAnimals }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 mb-1">Machos / Fêmeas</p>
                  <p class="text-xl font-bold text-blue-600">{{ stats.totalMales }} / {{ stats.totalFemales }}</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                  <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 mb-1">Espécies</p>
                  <p class="text-2xl font-bold text-gray-900">{{ stats.species }}</p>
                </div>
                <div class="bg-amber-100 p-3 rounded-lg">
                  <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!loading && batches.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
              <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma declaração registrada</h3>
            <p class="text-gray-600 mb-6">Comece criando sua primeira declaração veterinária</p>
            <button 
              @click="$emit('new')" 
              class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-emerald-500/30"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Nova Declaração
            </button>
          </div>

          <!-- Entries List -->
          <div v-if="!loading && batches.length > 0" class="space-y-4">
            <div 
              v-for="(batch, idx) in batches" 
              :key="batch.id"
              class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md hover:border-blue-200 transition-all group"
            >
              <div class="flex flex-col gap-3">
                <div class="flex items-start gap-3 justify-between">
                    <div class="bg-emerald-100 p-2 rounded-lg mt-1">
                      <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h4 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                        Declaração #{{ batch.id }}
                      </h4>
                      <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-600">
                        <span class="flex items-center gap-1.5">
                          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Criada em: <strong class="text-gray-900">{{ formatDate(batch.created_at) }}</strong>
                        </span>
                      </div>
                    </div>
                    <div>
                      <button @click="generateBatchPdf(batch)" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        PDF desta declaração
                      </button>
                    </div>
                  </div>
                  
                <!-- Batch entries table -->
                <div class="overflow-hidden border border-gray-200 rounded-lg">
                  <table class="w-full border-collapse">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="border p-2 text-left">Espécie</th>
                        <th class="border p-2 text-left">Faixa Etária</th>
                        <th class="border p-2 text-center">Machos</th>
                        <th class="border p-2 text-center">Fêmeas</th>
                        <th class="border p-2 text-center">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(e, i) in batch.entries" :key="e.id">
                        <td class="border p-2">{{ e.species }}</td>
                        <td class="border p-2">{{ e.age_band }}</td>
                        <td class="border p-2 text-center">{{ Number(e.qty_males || 0) }}</td>
                        <td class="border p-2 text-center">{{ Number(e.qty_females || 0) }}</td>
                        <td class="border p-2 text-center font-semibold">{{ Number(e.qty_males || 0) + Number(e.qty_females || 0) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-white border-t border-gray-200 p-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div v-if="batches.length > 0" class="text-sm text-gray-600">
              <span class="font-medium text-gray-900">{{ batches.length }}</span> 
              {{ batches.length === 1 ? 'declaração registrada' : 'declarações registradas' }}
            </div>
            <div v-else class="flex-1"></div>
            
            <div class="flex gap-3">
              <button 
                @click="$emit('close')" 
                class="px-6 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition-colors"
              >
                Fechar
              </button>
              <!-- <button 
                v-if="entries.length > 0"
                @click="generatePdf" 
                class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium transition-all flex items-center gap-2 shadow-lg shadow-blue-500/30"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Gerar PDF
              </button>
              <button 
                @click="$emit('new')" 
                class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/30"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Nova Declaração
              </button> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { 
  transition: opacity .3s ease;
}
.modal-enter-from, .modal-leave-to { 
  opacity: 0;
}
.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform .3s ease;
}
.modal-enter-from .bg-white {
  transform: scale(0.95) translateY(20px);
}
.modal-leave-to .bg-white {
  transform: scale(0.95) translateY(20px);
}
</style>