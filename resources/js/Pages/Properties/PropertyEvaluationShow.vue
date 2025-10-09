<template>
  <PageLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Avaliação #{{ evaluation.id }}</h1>
        <div class="flex gap-2">
          <button v-if="isOwner && !evaluation.owner_acknowledged" @click="acknowledge()" title="Confirmar recebimento" class="p-2 rounded bg-green-100 hover:bg-green-200 text-green-700">
            <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor' class='w-5 h-5'><path d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z'/></svg>
          </button>
          <button v-if="isOwner && evaluation.owner_acknowledged && evaluation.pdf_url" @click="downloadPdf()" title="Baixar PDF" class="p-2 rounded bg-blue-100 hover:bg-blue-200 text-blue-700">
            <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' class='w-5 h-5'><path stroke-linecap='round' stroke-linejoin='round' d='M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3' /></svg>
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><strong>Imóvel:</strong> {{ property.nickname || property.id }}</div>
            <div><strong>Avaliador:</strong> {{ evaluation.appraiser }}</div>
            <div><strong>Valor:</strong> {{ formatCurrency(evaluation.valuation) }}</div>
            <div><strong>Data:</strong> {{ formatDate(evaluation.created_at) }}</div>
            <div class="md:col-span-2"><strong>Observações:</strong> {{ evaluation.comments }}</div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({ property: Object, evaluation: Object })
const page = usePage()
const isOwner = page.props.isOwner

function formatCurrency(v){ return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(v||0)) }
function formatDate(v){ return v ? new Date(v).toLocaleDateString('pt-BR') : '' }

function acknowledge(){
  router.put(route('properties.evaluations.acknowledge', { property: props.property.id, evaluation: props.evaluation.id }), {}, {
    onSuccess: () => window.__toast?.({ text: 'Recebimento confirmado!', kind: 'success' }),
    onError: () => window.__toast?.({ text: 'Falha ao confirmar.', kind: 'error' })
  })
}
function downloadPdf(){ if (props.evaluation.pdf_url) window.open(props.evaluation.pdf_url, '_blank') }
</script>
