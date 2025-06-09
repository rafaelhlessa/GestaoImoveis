<template>
  <AppLayout title="Editar Avaliação">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Editar Avaliação de Propriedade
        </h2>
        <div class="flex space-x-4">
          <Link 
            :href="route('property-evaluations.show', evaluation.id)" 
            class="text-sm text-gray-600 hover:text-gray-900"
          >
            ← Voltar para detalhes
          </Link>
          <Link 
            :href="route('property-evaluations.index')" 
            class="text-sm text-gray-600 hover:text-gray-900"
          >
            Ver todas
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <PropertyEvaluationForm 
          :evaluation="evaluation"
          :is-editing="true"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import PropertyEvaluationForm from '@/Components/PropertyEvaluationForm.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  evaluation: {
    type: Object,
    required: true
  }
})
</script>