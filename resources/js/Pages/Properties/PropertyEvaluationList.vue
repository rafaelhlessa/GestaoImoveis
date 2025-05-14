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
                            <!-- slot de actions, se precisar -->
                            <template #actions="{ row }">
                              <button @click="destroy(row.id)" class="px-2 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Excluir
                              </button>
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
import { useForm, router } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, PropType } from 'vue';
import DataTable from '@/Components/DataTable.vue';

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
  { label: 'Observações', field: 'comments'},
  { label: 'Ações', field: 'actions' },
]

function destroy(id: number) {
  if (confirm('Tem certeza que deseja excluir esta avaliação?')) {
    // Aqui você pode chamar uma função para excluir a avaliação
    // Por exemplo, usando o Inertia.js para fazer uma requisição DELETE
    router.delete(route('properties.evaluations.destroy', id));
  }
}

</script>

<style scoped>
/* estilos básicos ou utilitários Tailwind podem ser usados */
</style>