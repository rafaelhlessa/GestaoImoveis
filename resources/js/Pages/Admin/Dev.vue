<script setup>
import { reactive, ref } from 'vue';
import FormBuilder from '@/Components/FormBuilder.vue';
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'

// Metadados do componente
const meta = reactive({
  name: 'FormMyComponent',
  directory: 'resources/js/Pages/Forms',
  columns: 6,
  rows: 3,
});
// Array de definições de campo
const fields = reactive([]);
const generatedCode = ref('');

// Adiciona um campo vazio
function addField() {
  fields.push({ label: '', type: 'text', size: 12 });
}
// Remove
function removeField(i) {
  fields.splice(i, 1);
}

// Monta a configuração para o FormBuilder
const builder = {
  get fields() {
    return fields.map(f => ({
      name: f.label.toLowerCase().replace(/\s+/g, '_'),
      label: f.label,
      type: f.type,
      props: {},
      options: f.type === 'select' ? [] : undefined,
      validationRule: null,
      errorMessage: null,
      grid: f.size,
    }));
  },
  get initialModel() {
    const m = {};
    fields.forEach(f => { m[f.label.toLowerCase().replace(/\s+/g, '_')] = f.type === 'checkbox' ? false : '' });
    return m;
  }
};

// Gera o código do componente SFC
function generate() {
  const name = meta.name;
  const cols = meta.columns;

  // monta o template
  let template = `<template>
  <div class="grid grid-cols-${cols} gap-4">
`;
  builder.fields.forEach(f => {
    template += `    <div class="col-span-${f.size}">
      <label for="${f.name}">${f.label}</label>
      <${f.type}-input v-model="form.${f.name}" :id="'${f.name}'" />
    </div>
`;
  });
  template += `  </div>
</template>
`;

  // monta o script
  let script = `<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm(${JSON.stringify(builder.initialModel, null, 2)});

function submit() {
  form.post(route('${name.toLowerCase()}.store'));
}

`;

  // combina tudo
  generatedCode.value = template + script;
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
                            <div class="p-6 bg-gray-50 dark:bg-gray-100 space-y-6 rounded-lg">
                                <!-- Configurações do componente -->
                                <section class="space-y-4">
                                <h2 class="text-lg font-semibold text-gray-800">Configurações do Formulário</h2>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                    <label class="block text-gray-600 text-sm font-medium">Nome do Componente</label>
                                    <input v-model="meta.name" type="text" class="mt-1 block text-gray-600 w-full rounded" placeholder="FormMyComponent" />
                                    </div>
                                    <div>
                                    <label class="block text-gray-600 text-sm font-medium">Diretório do Componente</label>
                                    <input v-model="meta.directory" type="text" class="mt-1 block text-gray-600 w-full rounded" placeholder="resources/js/Pages/Forms" />
                                    </div>
                                    <div>
                                    <label class="block text-gray-600 text-sm font-medium">Colunas (1 a 12)</label>
                                    <input v-model.number="meta.columns" type="number" min="1" max="12" class="mt-1 block text-gray-600 w-full rounded" />
                                    </div>
                                    <div>
                                    <label class="block text-gray-600 text-sm font-medium">Linhas (estimativa)</label>
                                    <input v-model.number="meta.rows" type="number" min="1" class="mt-1 block text-gray-600 w-full rounded" />
                                    </div>
                                </div>
                                </section>

                                <!-- Adicionar campos -->
                                <section class="space-y-4">
                                <h2 class="text-lg font-semibold">Campos do Formulário</h2>
                                <div v-for="(f, i) in fields" :key="i" class="grid grid-cols-6 gap-4 items-end">
                                    <div class="col-span-2">
                                    <label class="block text-gray-700 text-sm font-medium">Label</label>
                                    <input v-model="f.label" type="text" class="mt-1 block text-gray-700 w-full rounded" />
                                    </div>
                                    <div class="col-span-2">
                                    <label class="block text-gray-700 text-sm font-medium">Tipo de Input</label>
                                    <select v-model="f.type" class="mt-1 block text-gray-700 w-full rounded">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="select">Select</option>
                                        <option value="checkbox">Checkbox</option>
                                        <option value="date">Date</option>
                                        <option value="file">File</option>
                                    </select>
                                    </div>
                                    <div class="col-span-1">
                                    <label class="block text-gray-700 text-sm font-medium">Tamanho (1-12)</label>
                                    <input v-model.number="f.size" type="number" min="1" max="12" class="mt-1 block text-gray-700 w-full rounded" />
                                    </div>
                                    <div class="col-span-1 flex space-x-2">
                                    <button @click="removeField(i)" class="px-2 py-1 bg-red-500 text-white rounded">Excluir</button>
                                    </div>
                                </div>
                                <button @click="addField" class="px-4 py-2 bg-green-600 text-white rounded">Adicionar Campo</button>
                                </section>

                                <!-- Botão Gerar e Visualizações -->
                                <section class="space-y-4">
                                <button @click="generate" class="px-6 py-2 bg-blue-600 text-white rounded">Gerar</button>

                                <div v-if="generatedCode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Preview do formulário -->
                                    <div>
                                    <h3 class="font-semibold mb-2">Preview</h3>
                                    <FormBuilder
                                        :fields="builder.fields"
                                        :initialModel="builder.initialModel"
                                        :submitLabel="'Enviar'"
                                        @submit="() => {}"
                                        @cancel="() => {}"
                                    />
                                    </div>

                                    <!-- Código gerado -->
                                    <div>
                                    <h3 class="font-semibold mb-2">Código Gerado</h3>
                                    <pre class="bg-gray-900 text-green-200 p-4 rounded overflow-auto text-xs">
                            {{ generatedCode }}
                                    </pre>
                                    </div>
                                </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </PageLayout>
</template>

<style scoped>
/* estilos básicos ou utilitários Tailwind podem ser usados */
</style>
