<template>
  <PageLayout>
    <template #header>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Avaliações
        </h1>
        <div class="flex justify-end">
          <button class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Salvar
          </button>
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
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';

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

<style scoped>
/* estilos básicos ou utilitários Tailwind podem ser usados */
</style>