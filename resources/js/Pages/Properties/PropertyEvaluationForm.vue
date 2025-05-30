<template>
  <PageLayout>
    <template #header>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Fazer Avaliação
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
                                <div class="flex">
                                  <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-e-0 border-gray-300 rounded-s-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600">
                                    R$
                                  </span>
                                  <input 
                                    type="text" 
                                    v-model="displayValue"
                                    @input="handleInput"
                                    @blur="handleBlur"
                                    @focus="handleFocus"
                                    id="valor" 
                                    class="rounded-none rounded-e-lg bg-gray-50 border border-gray-300 text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm" 
                                    placeholder="R$ 0,00"
                                  />
                                </div>
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
import { ref, watch } from 'vue';

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

// Variável para armazenar o valor formatado para exibição
const displayValue = ref('');

// Função para formatar o valor como moeda brasileira
function formatCurrency(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return 'R$ 0,00';
  }
  
  // Converte para número
  const numValue = typeof value === 'string' ? Number(value.replace(/\D/g, '')) / 100 : Number(value);
  
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(numValue);
}

// Função para extrair apenas os números de uma string
function extractNumbers(value: string): string {
  return value.replace(/\D/g, '');
}

// Manipula o evento de input
function handleInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const value = extractNumbers(input.value);
  
  // Atualiza o valor no formulário (sem formatação)
  if (value.length > 10) { // Limita a 999,999.99 (8 dígitos no total)
    input.value = value.substring(0, 10);
    form.valor = input.value;
  } else {
    // Atualiza o valor no formulário (sem formatação)
    form.valor = value;
  }
  
  // Atualiza o valor exibido
  displayValue.value = value ? formatCurrency(value) : '';
}

// Manipula o evento de blur (quando o campo perde o foco)
function handleBlur() {
  displayValue.value = formatCurrency(form.valor);
}

// Manipula o evento de focus (quando o campo ganha o foco)
function handleFocus(e: Event) {
  const input = e.target as HTMLInputElement;
  input.value = form.valor;
  displayValue.value = form.valor;
}

// Observa mudanças no form.valor para manter displayValue sincronizado
watch(() => form.valor, (newValue) => {
  if (!document.activeElement || document.activeElement.id !== 'valor') {
    displayValue.value = formatCurrency(newValue);
  }
});

// Inicializa o displayValue
displayValue.value = formatCurrency(form.valor);

function submit() {
  console.log(form);

  if (form.valor) {
    // Convertendo de centavos para valor decimal (por exemplo: 56000000 -> 560000.00)
    const valorNumerico = Number(form.valor) / 100;
    
    // Garantir que o valor esteja dentro do limite da coluna no banco de dados
    if (valorNumerico > 99999999.99) {
      alert('O valor excede o limite permitido.');
      return;
    }
    
    // Atualiza o form com o valor numérico correto
    form.valor = valorNumerico.toString();
  }

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