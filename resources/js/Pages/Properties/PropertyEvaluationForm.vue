<template>
  <PageLayout>
    <template #header>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Fazer Avaliação
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
                         <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">Fazer Avaliação</h2>
                       </div>
                       <div>
                         <p class="row-start-2 mt-1 text-sm/6 text-gray-600">Formulário de cadastro de avaliação.</p>
                       </div>
                     </div>
                   </div>
                   <div class="mt-8 flow-root">
                     <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                          <form @submit.prevent="submit">
                            <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-6 m-2">
                              <label for="avaliador" class="block mb-1 font-medium">Avaliador</label>
                              <input type="text" v-model="form.avaliador" id="avaliador" class="w-full rounded-md border px-3 py-2" />
                            </div>
                            <div class="col-span-6 m-2">
                              <label for="valor" class="block mb-1 font-medium">Valor</label>
                              <input type="text" v-model="form.valor" id="valor" class="w-full rounded-md border px-3 py-2" />
                            </div>
                            <div class="col-span-12 m-2">
                              <label for="observações" class="block mb-1 font-medium">Observações</label>
                              <textarea v-model="form.observações" id="observações" class="w-full rounded-md border px-3 py-2" rows="4"></textarea>
                            </div>
                            <div class="col-span-full flex justify-end space-x-2 p-4">
                              <button type="button" :style='{ backgroundColor: "#D20F0F", color: "#ffffff"}' class="px-4 py-2 rounded">Cancelar</button>
                              <button type="submit" :style='{ backgroundColor: "#3A5C92", color: "#ffffff"}' class="px-4 py-2 rounded">Salvar</button>
                            </div>
                            </div>
                          </form>
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

// Recebe a propriedade do componente
const props = defineProps({
  property: {
    type: Object,
    required: true
  }
});

const form = useForm({
    "avaliador": "",
    "valor": "",
    "observações": "",
    "property_id": props.property.id  // Adicionado o property_id ao formulário
  });

function submit() {
  console.log(form);
  // Corrigido para incluir o ID da propriedade na rota
  form.post(route('properties.evaluations.store', { property: props.property.id }), {
    onSuccess: () => {
      // Redirecionar ou mostrar mensagem de sucesso
      console.log('Avaliação salva com sucesso');
      // Você pode redirecionar para a lista de avaliações com:
      window.location.href = route('properties.evaluations.index', { property: props.property.id });
    },
    onError: (errors) => {
      // Tratamento de erros específicos
      console.error('Erro ao salvar a avaliação:', errors);
    },
  });
}
</script>

<style scoped>
/* estilos básicos ou utilitários Tailwind podem ser usados */
</style>