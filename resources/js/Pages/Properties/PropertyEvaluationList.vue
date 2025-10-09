<template>
  <PageLayout>
    <template #header>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Avaliações
        </h1>
        <div class="flex justify-end">
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
          <div class="p-6" style="background-color: #ffffff; color: #000000;">
              <div class="space-y-12 p-6">
                 <div class="border-b border-gray-900/10">
                   <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                     <div class="gap-x-4 grid grid-rows-2">
                       <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                         <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">Avaliações</h2>
                       </div>
                       <div>
                         <p class="row-start-2 mt-1 text-sm/6 text-gray-600">Lista de avaliações da propriedade.</p>
                       </div>
                     </div>
                  </div>
                  <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                      <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                          <DataTable :data="evaluations" :columns="columns">
                            <template #cell-user="{ row }">
                              {{ row.user.name }}
                            </template>    
                            <template #cell-valuation="{ value }">
                              {{ formatCurrency(value) }}
                            </template>
                            <template #cell-property_condition="{ row }">
                              {{ getConditionLabel(row.property_condition) }}
                            </template>
                            <template #cell-owner_acknowledged="{ value, row }">
                              <span v-if="value" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700" title="Confirmada">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                                Confirmada
                              </span>
                              <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700" title="Pendente">
                                Pendente
                              </span>
                            </template>
                            <!-- slot de actions, se precisar -->
                            <template #actions="{ row }">
                              <div class="flex justify-end flex-wrap gap-2 items-center">
                                <!-- Ver Detalhes (todos) -->
                                <button @click="view(row)" title="Ver detalhes" class="p-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 3C7.5 3 3.7 6.1 2 10.5c1.7 4.4 5.5 7.5 10 7.5s8.3-3.1 10-7.5C20.3 6.1 16.5 3 12 3Zm0 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Zm0-7.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg>
                                </button>

                                <!-- Proprietário: Check (substitui gerar PDF) -->
                                <button v-if="isOwner && !row.owner_acknowledged" @click="acknowledge(row)" title="Confirmar recebimento" class="p-1.5 rounded bg-green-100 hover:bg-green-200 text-green-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                                </button>

                                <!-- Proprietário: Baixar PDF (após check) -->
                                <button v-if="isOwner && row.owner_acknowledged && row.pdf_url" @click="downloadPdf(row)" title="Baixar PDF" class="p-1.5 rounded bg-blue-100 hover:bg-blue-200 text-blue-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                </button>

                                <!-- Avaliador: Imagens -->
                                <button v-if="isEvaluator(row)" @click="triggerUpload(row)" title="Enviar imagens" class="p-1.5 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M4 5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4Zm3 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm12 9H5l4-5 3 4 2-3 5 4Z"/></svg>
                                </button>
                                <input :id="`file-${row.id}`" ref="fileInputs" type="file" class="hidden" accept="image/*" multiple @change="onImagesSelected($event, row)" />

                                <!-- Avaliador: Gerar PDF -->
                                <button v-if="isEvaluator(row)" @click="generatePdf(row)" title="Gerar PDF" class="p-1.5 rounded bg-purple-100 hover:bg-purple-200 text-purple-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6"/><path d="M8 13h2.5a1.5 1.5 0 0 0 0-3H8v3Zm0 0v3M13 16h3"/></svg>
                                </button>

                                <!-- Avaliador: Excluir -->
                                <button v-if="canDelete(row)" @click="destroy(row)" title="Excluir avaliação" class="p-1.5 rounded bg-red-100 hover:bg-red-200 text-red-700">
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h12M9.75 7.5 10.5 6h3l.75 1.5M18 7.5v10.125A2.625 2.625 0 0 1 15.375 20.25H8.625A2.625 2.625 0 0 1 6 17.625V7.5m3 3.75v6m6-6v6" /></svg>
                                </button>
                              </div>
                            </template>    
                          </DataTable> 
                      </div>
                    </div>
                  </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, computed, PropType } from 'vue';
import DataTable from '@/Components/DataTable.vue';

declare global {
  interface Window { __toast?: (args: { text: string; kind?: 'success'|'error'|'warning'; timeout?: number }) => void }
}

interface Evaluation {
  id?: number;
  user: string;
  valuation: number;
  comments?: string;
  [key: string]: any; // For any additional properties
}

const props = defineProps({
    evaluations: Array as PropType<Evaluation[]>, // Recebe os dados da avaliação
    properties: Object, // Recebe os dados da propriedade para edição
});

function formatCurrency(value: number | string | null | undefined): string {
  if (value == null || value === '') {
    return 'R$ 0,00';
  }
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(Number(value));
}

function formatDate(value: string | null | undefined): string {
  if (!value) return '';
  // cria um objeto Date a partir da string ISO e formata para pt-BR
  return new Date(value).toLocaleDateString('pt-BR');
}

function getUserName(user: any): string {
  if (!user) return '';
  // If user is already a string, return it
  if (typeof user === 'string') return user;
  // If user is an object with a name property, return that
  return user.name || '';
}


const columns = [
  { label: 'Usuário', field: 'user', format: (value: any) => getUserName(value) },
  { label: 'Valor', field: 'valuation', format: (value: number) => formatCurrency(value) },
  { label: 'Data', field: 'created_at', format: (value: string) => formatDate(value)},
  { label: 'Qualidade Construções', field: 'property_condition' },
  { label: 'Status', field: 'owner_acknowledged' },
  { label: 'Observações', field: 'comments'},
  { label: 'Ações', field: 'actions', align: 'right' },
]

const pageProps = usePage().props as any
const isOwner = pageProps.isOwner

function getConditionLabel(value: string | null | undefined): string {
  if (!value) return ''
  const map: Record<string, string> = {
    excelente: 'Excelente',
    bom: 'Bom',
    regular: 'Regular',
    ruim: 'Ruim',
    pessimo: 'Péssimo',
  }
  return map[value] ?? value
}

function canDelete(row: any) {
  const auth = pageProps.auth
  if (!auth || !auth.user) return false
  const uid = auth.user.id
  const rowUserId = row.user_id ?? (row.user && row.user.id)
  return rowUserId === uid && !row.owner_acknowledged
}

function isEvaluator(row: any) {
  const auth = pageProps.auth
  return auth && auth.user && (row.user_id ? row.user_id === auth.user.id : (row.user && row.user.id === auth.user.id))
}

function view(row: any) {
  router.get(route('properties.evaluations.show', { property: row.property_id || props.properties?.id, evaluation: row.id }))
}

function acknowledge(row: any) {
  const propertyId = row.property_id || (props.properties && props.properties.id)
  if (!propertyId) return
  router.put(route('properties.evaluations.acknowledge', { property: propertyId, evaluation: row.id }), {}, {
    onSuccess: () => {
      window.__toast?.({ text: 'Recebimento confirmado!', kind: 'success' })
      row.owner_acknowledged = true
    },
    onError: () => window.__toast?.({ text: 'Falha ao confirmar.', kind: 'error' })
  })
}

function generatePdf(row: any) {
  const propertyId = row.property_id || (props.properties && props.properties.id)
  if (!propertyId) return
  router.post(route('properties.evaluations.pdf', { property: propertyId, evaluation: row.id }), {
    preserveScroll: true,
  }, {
    onSuccess: () => window.__toast?.({ text: 'PDF gerado com sucesso!', kind: 'success' }),
    onError: () => window.__toast?.({ text: 'Falha ao gerar PDF.', kind: 'error' })
  })
}

function downloadPdf(row: any) {
  if (row.pdf_url) {
    window.open(row.pdf_url, '_blank')
  }
}

const fileInputs = ref<HTMLInputElement | null>(null)

function triggerUpload(row: any) {
  const input = document.getElementById(`file-${row.id}`) as HTMLInputElement
  if (input) input.click()
}

function onImagesSelected(e: Event, row: any) {
  const input = e.target as HTMLInputElement
  if (!input.files || input.files.length === 0) return
  const propertyId = row.property_id || (props.properties && props.properties.id)
  if (!propertyId) return
  const data: any = { images: Array.from(input.files) }
  router.post(route('properties.evaluations.media', { property: propertyId, evaluation: row.id }), data, {
    forceFormData: true,
    onFinish: () => {
      input.value = ''
    },
    onSuccess: () => window.__toast?.({ text: 'Imagens enviadas!', kind: 'success' }),
    onError: () => window.__toast?.({ text: 'Falha no envio de imagens.', kind: 'error' })
  })
}

function destroy(row: any) {
  if (confirm('Tem certeza que deseja excluir esta avaliação?')) {
    const propertyId = row.property_id || (props.properties && props.properties.id)
    if (!propertyId) return
    router.delete(route('properties.evaluations.destroy', { property: propertyId, evaluation: row.id }), {
      onSuccess: () => window.__toast?.({ text: 'Avaliação excluída.', kind: 'success' }),
      onError: () => window.__toast?.({ text: 'Não foi possível excluir.', kind: 'error' })
    })
  }
}

</script>

<style scoped>
/* estilos básicos ou utilitários Tailwind podem ser usados */
</style>