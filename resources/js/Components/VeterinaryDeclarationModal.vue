<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: { type: Boolean, default: false },
  property: { type: Object, required: true },
});

const emit = defineEmits(['close', 'success']);

const entries = ref([
  { species: '', age_band: '', qty_males: 0, qty_females: 0 },
]);

const loading = ref(false);
const error = ref(null);

const total = computed(() => entries.value.reduce((sum, e) => sum + (Number(e.qty_males || 0) + Number(e.qty_females || 0)), 0));

function addRow() {
  entries.value.push({ species: '', age_band: '', qty_males: 0, qty_females: 0 });
}

function removeRow(index) {
  if (entries.value.length > 1) entries.value.splice(index, 1);
}

async function submit() {
  error.value = null;
  const valid = entries.value.every(e => e.species && e.age_band && (e.qty_males >= 0) && (e.qty_females >= 0));
  if (!valid) {
    error.value = 'Preencha espécie, faixa etária e quantidades não negativas.';
    return;
  }

  loading.value = true;
  try {
    // POST entries
    const url = route('properties.veterinary.store', { property: props.property.id });
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ entries: entries.value }),
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('Falha ao salvar as entradas');
    const saveData = await res.json();
    // Generate PDF for the created declaration (batch)
    if (!saveData?.batch?.id) throw new Error('Não foi possível identificar a declaração criada');
    const pdfUrl = route('properties.veterinary.batch.pdf', { property: props.property.id, batch: saveData.batch.id });
    const pdfRes = await fetch(pdfUrl, { credentials: 'same-origin' });
    if (!pdfRes.ok) throw new Error('Falha ao gerar PDF da declaração');
    const data = await pdfRes.json();
    if (data?.url) window.open(data.url, '_blank');
    emit('success');
    emit('close');
  } catch (e) {
    error.value = e.message || 'Erro desconhecido';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="relative bg-gradient-to-r from-emerald-600 to-teal-600 p-6">
          <button 
            @click="$emit('close')" 
            class="absolute top-4 right-4 text-white/80 hover:text-white hover:bg-white/10 rounded-lg p-2 transition-all"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
          
          <div class="flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Declaração da Inspetoria Veterinária</h3>
              <p class="text-emerald-100 text-sm mt-1">Cadastro de animais e geração de documento</p>
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
              <p class="font-medium text-red-900">Erro de validação</p>
              <p class="text-sm text-red-700 mt-1">{{ error }}</p>
            </div>
          </div>

          <!-- Stats Summary -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600">Entradas</p>
                  <p class="text-2xl font-bold text-gray-900 mt-1">{{ entries.length }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600">Total de Animais</p>
                  <p class="text-2xl font-bold text-emerald-600 mt-1">{{ total }}</p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-lg">
                  <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl p-4 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-purple-100">Machos / Fêmeas</p>
                  <p class="text-2xl font-bold text-white mt-1">
                    {{ entries.reduce((s, e) => s + (Number(e.qty_males) || 0), 0) }} / 
                    {{ entries.reduce((s, e) => s + (Number(e.qty_females) || 0), 0) }}
                  </p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Entries Cards -->
          <div class="space-y-4">
            <div 
              v-for="(entry, idx) in entries" 
              :key="idx"
              class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-all"
            >
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                  <div class="bg-emerald-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </div>
                  <span class="font-semibold text-gray-900">Entrada #{{ idx + 1 }}</span>
                </div>
                <button 
                  @click="removeRow(idx)" 
                  :disabled="entries.length === 1"
                  class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Remover
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                      </svg>
                      Espécie
                    </span>
                  </label>
                  <input 
                    v-model.trim="entry.species" 
                    type="text" 
                    placeholder="Ex.: Bovino"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      Faixa Etária
                    </span>
                  </label>
                  <input 
                    v-model.trim="entry.age_band" 
                    type="text" 
                    placeholder="Ex.: 0-12 meses"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Qtd. Machos
                    </span>
                  </label>
                  <input 
                    v-model.number="entry.qty_males" 
                    min="0" 
                    type="number"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-center font-semibold"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Qtd. Fêmeas
                    </span>
                  </label>
                  <input 
                    v-model.number="entry.qty_females" 
                    min="0" 
                    type="number"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-center font-semibold"
                  />
                </div>
              </div>

              <!-- Entry Total -->
              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total desta entrada:</span>
                  <span class="font-bold text-gray-900 text-lg">
                    {{ (Number(entry.qty_males) || 0) + (Number(entry.qty_females) || 0) }} animais
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Add Row Button -->
          <button 
            @click="addRow" 
            class="w-full mt-4 py-3 bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-dashed border-emerald-300 rounded-xl hover:from-emerald-100 hover:to-teal-100 hover:border-emerald-400 transition-all flex items-center justify-center gap-2 text-emerald-700 font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Adicionar Nova Entrada
          </button>
        </div>

        <!-- Footer -->
        <div class="bg-white border-t border-gray-200 p-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-600">
              <span class="font-medium text-gray-900">Total geral:</span> 
              <span class="text-2xl font-bold text-emerald-600 ml-2"> {{ total }} </span> 
              <span class="text-gray-500"> animais cadastrados </span>
            </div>
            <div class="flex gap-3">
              <button 
                @click="$emit('close')" 
                class="px-6 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition-colors"
              >
                Cancelar
              </button>
              <button 
                @click="submit" 
                :disabled="loading"
                class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-lg shadow-emerald-500/30"
              >
                <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ loading ? 'Gerando PDF...' : 'Salvar e Gerar PDF' }}
              </button>
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