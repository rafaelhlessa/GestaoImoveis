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
                <form @submit.prevent="onSubmit" class="space-y-6">

                  <!-- Grid dinâmico baseado em colunas -->
                  <div :class="['grid gap-4', gridColsClass]">
                    <div
                      v-for="(field, idx) in fieldsState"
                      :key="field.name"
                      :class="`col-span-${field.grid || 1}`"
                    >
                      <!-- Label -->
                      <label
                        :for="field.name"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                      >
                        {{ field.label }}
                      </label>

                      <!-- Input dinâmico -->
                      <component
                        :is="componentMap[field.type] || TextInput"
                        v-model="model[field.name]"
                        :id="field.name"
                        v-bind="{
                          ...field.props,
                          class: [baseInputClasses, field.props.class]
                        }"
                      >
                        <!-- Slot para <select> -->
                        <template v-if="field.type === 'select'" #default>
                          <option disabled value="">{{ field.props.placeholder || 'Selecione' }}</option>
                          <option
                            v-for="opt in field.options"
                            :key="opt.value"
                            :value="opt.value"
                          >
                            {{ opt.label }}
                          </option>
                        </template>
                      </component>

                      <!-- Erro de validação -->
                      <p v-if="errors[field.name]" class="mt-1 text-sm text-red-600">
                        {{ errors[field.name] }}
                      </p>

                      <!-- Botão de cores -->
                      <button
                        type="button"
                        @click="openColorModal(idx)"
                        class="mt-2 text-sm px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded"
                      >
                        🎨 Cores
                      </button>
                    </div>
                  </div>

                  <!-- Ações -->
                  <div class="flex justify-end space-x-2">
                    <button
                      type="button"
                      @click="cancel"
                      class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded"
                    >
                      Cancelar
                    </button>
                    <button
                      type="submit"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded"
                    >
                      {{ submitLabel }}
                    </button>
                  </div>
                </form>

                <!-- Modal de seleção de cores -->
                <div
                  v-if="showColorModal"
                  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                >
                  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-80">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                      Selecionar Cores do Campo
                    </h2>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
                          Cor de fundo
                        </label>
                        <input
                          type="color"
                          v-model="modalBgColor"
                          class="w-full h-10 p-0 border-0"
                        />
                      </div>
                      <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
                          Cor do texto
                        </label>
                        <input
                          type="color"
                          v-model="modalTextColor"
                          class="w-full h-10 p-0 border-0"
                        />
                      </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-2">
                      <button @click="applyColors" type="button" class="px-3 py-1 bg-green-600 text-white rounded">
                        Aplicar
                      </button>
                      <button @click="closeColorModal" type="button" class="px-3 py-1 bg-red-600 text-white rounded">
                        Cancelar
                      </button>
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
  import { reactive, computed, ref } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
  import TextInput from '@/Components/TextInput.vue'
  import NumberInput from '@/Components/NumberInput.vue'
  import SelectInput from '@/Components/SelectInput.vue'
  import CheckboxInput from '@/Components/Checkbox.vue'
  import RadioGroup from '@/Components/RadioGroup.vue'
  import DateInput from '@/Components/DateInput.vue'
  import FileInput from '@/Components/FileInput.vue'

  // Definição de campo
  type FieldDefinition = {
    name: string;
    label: string;
    type: 'text' | 'number' | 'select' | 'checkbox' | 'radio' | 'date' | 'file';
    grid?: number;
    props?: Record<string, any>;
    options?: Array<{ label: string; value: any }>;
    validationRule?: (value: any) => boolean;
    errorMessage?: string;
  };

  // Props e destruturação
  const props = defineProps<{
    fields: FieldDefinition[];
    initialModel: Record<string, any>;
    submitLabel?: string;
    columns?: number | string;
  }>();
  const emit = defineEmits<{
    (e: 'submit', model: Record<string, any>): void;
    (e: 'cancel'): void;
  }>();
  const { fields, initialModel, submitLabel = 'Salvar', columns = 2 } = props;

  // Estado reativo
  const model = reactive({ ...initialModel });
  const errors = reactive<Record<string, string>>({});
  const fieldsState = ref(fields.map(field => ({ ...field, props: { ...(field.props || {}) } })));

  // Classes base e grid
  const baseInputClasses = 'rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-gray-400 sm:text-sm w-full';
  const gridColsClass = computed(() => `grid-cols-1 md:grid-cols-2 lg:grid-cols-${columns}`);

  // Mapa de componentes
  const componentMap = { text: TextInput, number: NumberInput, select: SelectInput, checkbox: CheckboxInput, radio: RadioGroup, date: DateInput, file: FileInput };

  // Modal de cores
  const showColorModal = ref(false);
  const currentFieldIndex = ref<number | null>(null);
  const modalBgColor = ref('#ffffff');
  const modalTextColor = ref('#374151');

  function openColorModal(index: number) {
    currentFieldIndex.value = index;
    const field = fieldsState.value[index];
    modalBgColor.value = field.props.bgColor || '#ffffff';
    modalTextColor.value = field.props.textColor || '#374151';
    showColorModal.value = true;
  }

  function applyColors() {
    if (currentFieldIndex.value !== null) {
      const field = fieldsState.value[currentFieldIndex.value];
      field.props.bgColor = modalBgColor.value;
      field.props.textColor = modalTextColor.value;
    }
    closeColorModal();
  }

  function closeColorModal() {
    showColorModal.value = false;
    currentFieldIndex.value = null;
  }

  // Validação simples
  function validate(): boolean {
    Object.keys(errors).forEach(key => delete errors[key]);
    let valid = true;
    fieldsState.value.forEach(field => {
      const val = (model as any)[field.name];
      if (field.validationRule && !field.validationRule(val)) {
        errors[field.name] = field.errorMessage || 'Inválido';
        valid = false;
      }
    });
    return valid;
  }

  // Handlers
  function onSubmit() {
    if (validate()) emit('submit', { ...model });
  }

  function cancel() {
    emit('cancel');
  }
  </script>

  <style scoped>
  /* Estilos adicionais, se necessário */
  </style>
