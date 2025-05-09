<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import DataTable from '@/Components/DataTable.vue'

const { props } = usePage()
const criteria = ref(
  props.criteria.map(item => ({
    ...item,
    weight: Number(item.weight),
  }))
)
const columns = [
  { label: 'Nome', field: 'name' },
  { label: 'Peso', field: 'weight', format: val => val.toFixed(2) },
  { label: 'Ações', field: 'actions' },
]

function destroy(id) {
  if (confirm('Deseja realmente excluir?')) {
    fetch(route('criteria.destroy', id), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => window.location.reload())
  }
}
</script>

<template>
    <PageLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Critérios de Avaliação
                </h1>
                <div class="flex justify-end">
                    <Link :href="route('criteria.create')" class="btn-primary">Novo Critério</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow">
                            <DataTable :data="criteria" :columns="columns">
                                <template #actions="{ row }">
                                <Link :href="route('criteria.edit', row.id)" class="btn-sm">Editar</Link>
                                <button @click="destroy(row.id)" size="sm" variant="danger">Excluir</button>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<style scoped>
/* estilizações específicas */
</style>

