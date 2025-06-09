<template>
  <AppLayout title="Nova Avaliação">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Nova Avaliação de Propriedade
        </h2>
        <Link 
          :href="route('property-evaluations.index')" 
          class="text-sm text-gray-600 hover:text-gray-900"
        >
          ← Voltar para lista
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <PropertyEvaluationForm 
          :property-id="propertyId"
          :is-editing="false"
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
  propertyId: {
    type: Number,
    default: null
  }
})
</script>