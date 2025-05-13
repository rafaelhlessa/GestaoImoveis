<script setup>
import { reactive, ref } from 'vue';
import FormBuilder from '@/Components/FormBuilder.vue';
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'; // Importando axios para fazer requisições HTTP

// Metadados do componente
const meta = reactive({
  name: '',
  directory: 'resources/js/Pages/Forms',
  columns: 12,
  rows: 1,
  title: '',
});

// Array de definições de campo
const fields = reactive([]);
const generatedCode = ref('');
const message = ref(''); // Mensagem de feedback para o usuário

// Configurações de cores
const colors = reactive({
  background: '#ffffff',
  text: '#000000',
});

const cancelColor = reactive({
  background: '#D20F0F',
  text: '#ffffff',
});

const submitColor = reactive({
  background: '#3A5C92',
  text: '#ffffff',
});

// Adiciona um campo vazio
function addField() {
  fields.push({ label: '', type: 'text', size: 12, sizeCol: 4 });
}

// Remove um campo
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

// Gera o SFC completo
function generate() {
  const cols = meta.columns;
  const title = meta.title;
  const description = meta.description;
  let result = "";

  // Parte 1: Template
  result += '<template>\n';
  result += '  <PageLayout>\n';
  result += '    <template #header>\n';
  result += '      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">\n';
  
  if (title) {
    result += '        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">\n';
    result += `          ${title}\n`;
    result += '        </h1>\n';
  }
  
  result += '        <div class="flex justify-end">\n';
  result += '          <button class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">\n';
  result += '            Salvar\n';
  result += '          </button>\n';
  result += '        </div>\n';
  result += '      </div>\n';
  result += '    </template>\n\n';
  
  // Corpo principal - Adicionando cores selecionadas
  result += '    <div class="py-12">\n';
  result += '      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">\n';
  result += '        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">\n';
  result += `          <div class="p-6" style="background-color: ${colors.background}; color: ${colors.text};">\n`;
  result += `              <div class="space-y-12 p-6">\n`;
  result += '                 <div class="border-b border-gray-900/10">\n';
  result += '                   <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">\n';
  result += '                     <div class="gap-x-4 grid grid-rows-2">\n';
  result += '                       <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">\n';
  result += `                         <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">${ title }</h2>\n`;
  result += '                       </div>\n';
  result += '                       <div>\n';
  result += `                         <p class="row-start-2 mt-1 text-sm/6 text-gray-600">${ description }</p>\n`;
  result += '                       </div>\n';
  result += '                     </div>\n';
  result += '                   </div>\n';
  result += '                   <div class="mt-8 flow-root">\n';
  result += '                     <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">\n';
  result += '                         <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">\n';  
  
  // Formulário com inputs dinâmicos
  result += '                         <form @submit.prevent="submit">\n';
  result += `                           <div class="grid grid-cols-${cols} gap-4">\n`;
  
  // Processa cada campo do formulário
  if (fields.length > 0) {
    fields.forEach(f => {
      const fieldName = f.label.toLowerCase().replace(/\s+/g, '_');
      result += `                    <div class="col-span-${f.size} m-2">\n`;
      result += `                      <label for="${fieldName}" class="block mb-1 font-medium">${f.label}</label>\n`;
      
      // Renderiza o input de acordo com o tipo
      switch (f.type) {
        case 'text':
          result += `                      <input type="text" v-model="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2" />\n`;
          break;
        case 'number':
          result += `                      <input type="number" v-model.number="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2" />\n`;
          break;
        case 'select':
          result += `                      <select v-model="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2">\n`;
          result += '                        <option value="">Selecione...</option>\n';
          result += '                        <option value="opcao1">Opção 1</option>\n';
          result += '                        <option value="opcao2">Opção 2</option>\n';
          result += '                      </select>\n';
          break;
        case 'checkbox':
          result += `                      <div class="flex items-center">\n`;
          result += `                        <input type="checkbox" v-model="form.${fieldName}" id="${fieldName}" class="rounded border" />\n`;
          result += '                      </div>\n';
          break;
        case 'date':
          result += `                      <input type="date" v-model="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2" />\n`;
          break;
        case 'textarea':
          result += `                      <textarea v-model="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2" rows="4"></textarea>\n`;
          break;
        case 'radio':
          result += `                      <div class="flex items-center">\n`;
          result += `                        <input type="radio" v-model="form.${fieldName}" id="${fieldName}" class="rounded border" />\n`;
          result += '                      </div>\n';
          break;  
        default:
          result += `                      <input type="text" v-model="form.${fieldName}" id="${fieldName}" class="w-full rounded-md border px-3 py-2" />\n`;
      }
      
      result += '                    </div>\n';
    });
  } else {
    // Mensagem quando não há campos
    result += '                    <div class="col-span-full p-4 text-center">\n';
    result += '                      <p>Nenhum campo configurado</p>\n';
    result += '                    </div>\n';
  }
  
  // Botões do formulário
  result += '                    <div class="col-span-full flex justify-end space-x-2 p-4">\n';
  result += `                      <button type="button" :style='{ backgroundColor: "${ cancelColor.background }", color: "${ cancelColor.text }"}' class="px-4 py-2 rounded">Cancelar</button>\n`;	
  result += `                      <button type="submit" :style='{ backgroundColor: "${ submitColor.background }", color: "${ submitColor.text }"}' class="px-4 py-2 rounded">Salvar</button>\n`;	
  result += '                    </div>\n';
  
  result += '                  </div>\n';
  result += '                </form>\n';
  result += '              </div>\n';
  result += '            </div>\n';
  result += '          </div>\n';
  
  // Fecha blocos do template
  result += '              </div>\n';
  result += '            </div>\n';
  result += '          </div>\n';
  result += '          </div>\n';
  result += '      </div>\n';
  result += '    </div>\n';
  result += '  </PageLayout>\n';
  result += '</template>\n\n';
  
  // SOLUÇÃO PARA O PROBLEMA DE SCRIPT:
  // 1. Quebramos a tag script em partes para evitar que o parser JavaScript
  // interprete isso como o final do script atual no arquivo .vue
  const openScript = '<' + 'script setup lang="ts"' + '>';
  const closeScript = '</' + 'script' + '>';
  
  result += openScript + '\n';
  result += "import { useForm } from '@inertiajs/vue3';\n";
  result += "import PageLayout from '@/Layouts/AuthenticatedLayout.vue';\n\n";
  
  // Cria uma representação de texto segura do objeto - forma alternativa
  let modelObj = {};
  for (const key in builder.initialModel) {
    modelObj[key] = builder.initialModel[key];
  }
  
  // Convertemos para string de uma maneira mais segura
  const modelString = JSON.stringify(modelObj, null, 2).replace(/\n/g, '\n  ');
  result += `const form = useForm(${modelString});\n\n`;
  
  result += `function submit() {\n  form.post(route('${meta.name.toLowerCase()}.store'));\n}\n`;
  result += closeScript + '\n\n';
  
  // Parte 3: Style - usando a mesma abordagem de divisão
  const openStyle = '<' + 'style scoped' + '>';
  const closeStyle = '</' + 'style' + '>';
  
  result += openStyle + '\n';
  result += '/* estilos básicos ou utilitários Tailwind podem ser usados */\n';
  result += closeStyle;
  
  // Atualiza o código gerado
  generatedCode.value = result;
}

// Função para criar o arquivo físico
async function createComponentFile() {
  try {
    // Primeiro gera o código caso ainda não tenha sido gerado
    if (!generatedCode.value) {
      generate();
    }
    
    // Verifica se existem campos definidos
    if (fields.length === 0) {
      message.value = "Por favor, adicione pelo menos um campo antes de criar o componente.";
      return;
    }
    
    // Verifica se o nome do componente está definido
    if (!meta.name || meta.name.trim() === '') {
      message.value = "Por favor, defina um nome para o componente.";
      return;
    }
    
    // Verifica se o diretório está definido
    if (!meta.directory || meta.directory.trim() === '') {
      message.value = "Por favor, defina um diretório para o componente.";
      return;
    }

    // Verifica se o nome do componente está definido
    if (!meta.title || meta.title.trim() === '') {
      message.value = "Por favor, defina um título para o componente.";
      return;
    }

    // Verifica se o nome do componente está definido
    if (!meta.description || meta.description.trim() === '') {
      message.value = "Por favor, defina uma descrição para o componente.";
      return;
    }
    
    // Cria o código final sem o seletor de cores
    const fileContent = generatedCode.value;
    
    // Exibe mensagem de carregamento
    message.value = `Criando componente ${meta.name}.vue...`;
    
    // Prepara os dados para a requisição
    const requestData = {
      path: `${meta.directory}/${meta.name}.vue`,
      content: fileContent
    };
    
    // Faz a requisição para o endpoint de criação de arquivo
    const response = await axios.post('/api/create-file', requestData);
    
    if (response.data.success) {
      message.value = `Arquivo ${meta.name}.vue criado com sucesso em ${meta.directory}!`;
    } else {
      message.value = `Erro ao criar arquivo: ${response.data.error}`;
    }
  } catch (error) {
    console.error('Erro ao criar arquivo:', error);
    message.value = `Erro ao criar arquivo: ${error.message}`;
  }
}
</script>

<template>
    <PageLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Critérios de FormComponent
                </h1>
                <div class="flex justify-end">
                    
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
                                <div class="grid grid-cols-12 gap-4">
                                  <!-- First row -->
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Nome do Componente</label>
                                    <input v-model="meta.name" type="text" class="mt-1 block text-gray-600 w-full rounded" placeholder="FormMyComponent" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Diretório do Componente</label>
                                    <input v-model="meta.directory" type="text" class="mt-1 block text-gray-600 w-full rounded" placeholder="resources/js/Pages/Forms" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Título</label>
                                    <input v-model="meta.title" type="text" min="1" class="mt-1 block text-gray-600 w-full rounded" placeholder="Título do Cabeçalho" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Descrição</label>
                                    <input v-model="meta.description" type="text" min="1" class="mt-1 block text-gray-600 w-full rounded" placeholder="Descrição do Formulário" />
                                  </div>
                                  
                                  <!-- Second row -->
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Colunas (1 a 12)</label>
                                    <input v-model.number="meta.columns" type="number" min="1" max="12" class="mt-1 block text-gray-600 w-full rounded" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Linhas (estimativa)</label>
                                    <input v-model.number="meta.rows" type="number" min="1" class="mt-1 block text-gray-600 w-full rounded" />
                                  </div>
                                  
                                  <!-- Seletor de cores -->
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor de Fundo</label>
                                    <input v-model="colors.background" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor do Texto</label>
                                    <input v-model="colors.text" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>

                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor de Botão Cancelar</label>
                                    <input v-model="cancelColor.background" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor do Texto Botão Cancelar</label>
                                    <input v-model="cancelColor.text" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>

                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor de Fundo Botão Enviar</label>
                                    <input v-model="submitColor.background" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>
                                  <div class="col-span-3">
                                    <label class="block text-gray-600 text-sm font-medium">Cor do Texto Botão Enviar</label>
                                    <input v-model="submitColor.text" type="color" class="mt-1 block text-gray-600 w-full rounded h-10" />
                                  </div>
                                </div>
                                </section>

                                <!-- Adicionar campos -->
                                <section class="space-y-4">
                                <h2 class="text-lg font-semibold">Campos do Formulário</h2>
                                <div v-for="(f, i) in fields" :key="i" class="grid grid-cols-12 gap-4 items-end">
                                    <div class="col-span-3">
                                    <label class="block text-gray-700 text-sm font-medium">Label</label>
                                    <input v-model="f.label" type="text" class="mt-1 block text-gray-700 w-full rounded" />
                                    </div>
                                    <div class="col-span-3">
                                    <label class="block text-gray-700 text-sm font-medium">Tipo de Input</label>
                                    <select v-model="f.type" class="mt-1 block text-gray-700 w-full rounded">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="select">Select</option>
                                        <option value="checkbox">Checkbox</option>
                                        <option value="date">Date</option>
                                        <option value="textarea">Textarea</option>
                                    </select>
                                    </div>
                                    <div class="col-span-2">
                                    <label class="block text-gray-700 text-sm font-medium">Tamanho da Coluna (1-12)</label>
                                    <input v-model.number="f.size" type="number" min="1" max="12" class="mt-1 block text-gray-700 w-full rounded" />
                                    </div>
                                    <div class="col-span-2 flex space-x-2">
                                    <button @click="removeField(i)" class="px-2 py-1 bg-red-500 text-white rounded">Excluir</button>
                                    </div>
                                </div>
                                <button @click="addField" class="px-4 py-2 bg-green-600 text-white rounded">Adicionar Campo</button>
                                </section>

                                <!-- Botão Gerar e Criar Arquivo -->
                                <section class="space-y-4">
                                  <div class="flex space-x-4">
                                    <button @click="generate" class="px-6 py-2 bg-blue-600 text-white rounded">Gerar Código</button>
                                    <button @click="createComponentFile" class="px-6 py-2 bg-purple-600 text-white rounded">Criar Componente</button>
                                  </div>
                                  
                                  <!-- Mensagem de feedback -->
                                  <div v-if="message" class="p-4 mt-4 bg-green-100 text-green-800 rounded-md">
                                    {{ message }}
                                  </div>

                                  <div v-if="generatedCode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Preview do formulário -->
                                    <div class="col-span-12">
                                      <h3 class="font-semibold mb-2">Preview</h3>
                                      <div class="p-4 rounded-lg shadow-md" :style="{ backgroundColor: colors.background, color: colors.text }">
                                        <FormBuilder
                                          :title="meta.title"
                                          :description="meta.description"
                                          :columns="meta.columns"
                                          :fields="builder.fields"
                                          :initialModel="builder.initialModel"
                                          :submitLabel="'Enviar'"
                                          :cancelLabel="'Cancelar'"
                                          :cancelBgColor="cancelColor.background"
                                          :submitBgColor="submitColor.background"
                                          @submit="() => {}"
                                          @cancel="() => {}"
                                        />
                                      </div>
                                    </div>

                                    <!-- Código gerado -->
                                    <div class="col-span-12">
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