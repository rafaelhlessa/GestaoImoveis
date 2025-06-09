<template>
  <PageLayout>
    <template #header>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ title }}
        </h1>
        <div class="flex justify-end">
          <button class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              Nome do Botão
          </button>
        </div>
      </div>  
      </template>

      <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div class="p-6 text-gray-900 dark:text-gray-100 bg-white">
                <div class="space-y-12 p-6">
                  <div class="border-b border-gray-900/10">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                      <div class="gap-x-4 grid grid-rows-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                          <h2 class="row-start-1 text-base/7 font-semibold text-gray-900 relative truncate">{{ title }}</h2>
                        </div>
                        <div>
                          <p class="row-start-2 mt-1 text-sm/6 text-gray-600">{{ description }}</p>
                        </div>
                      </div>
                    </div>
                    
                    <div class="mt-8 flow-root">
                      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                          <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <form @submit.prevent="onSubmit" class="space-y-6">
                              <!-- Campos dinâmicos -->
                              <div :class="['grid gap-4', gridColsClass]">
                                <div
                                  v-for="(field, idx) in fieldsState"
                                  :key="field.name"
                                  :class="`col-span-${field.grid || 1} p-4`"
                                >
                                  <label
                                    :for="field.name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-700 mb-1"
                                  >
                                    {{ field.label }}
                                  </label>
                                  <component
                                    :is="componentMap[field.type] || TextInput"
                                    v-model="model[field.name]"
                                    :id="field.name"
                                    v-bind="{
                                      ...field.props,
                                      class: [baseInputClasses, field.props.class],
                                      style: { backgroundColor: field.props.bgColor, color: field.props.textColor }
                                    }"
                                  >
                                    <template v-if="field.type === 'Select'" #default>
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
                                  <p v-if="errors[field.name]" class="mt-1 text-sm text-red-600">
                                    {{ errors[field.name] }}
                                  </p>
                                  <button
                                    type="button"
                                    @click="openFieldColorModal(idx)"
                                    class="mt-2 text-sm px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded"
                                  >
                                    🎨 Cores
                                  </button>
                                </div>
                              </div>
                              <!-- Ações -->
                              <div class="flex justify-end space-x-2 p-4">
                                <button
                                  type="button"
                                  @click="openButtonColorModal('cancel')"
                                  :style="{ backgroundColor: cancelColor, color: cancelTextColor }"
                                  class="px-4 py-2 rounded"
                                >
                                  {{ cancelButton }}
                                </button>
                                <button
                                  type="button"
                                  @click="openButtonColorModal('submit')"
                                  :style="{ backgroundColor: submitColor, color: submitTextColor }"
                                  class="px-4 py-2 rounded"
                                >
                                  {{ submitLabel }}
                                </button>
                              </div>
                            </form>
                          </div>
                      </div>
                    </div>
                  </div>  
                </div> 
                

                <!-- Modal de seleção de cores -->
                <div
                  v-if="showColorModal"
                  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                >
                  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-80">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                      {{ modalTitle }}
                    </h2>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Cor de fundo</label>
                        <input type="color" v-model="modalBgColor" class="w-full h-10 p-0 border-0" />
                      </div>
                      <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Cor do texto</label>
                        <input type="color" v-model="modalTextColor" class="w-full h-10 p-0 border-0" />
                      </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-2">
                      <button @click="applyColors" type="button" class="px-3 py-1 bg-green-600 text-white rounded">Aplicar</button>
                      <button @click="closeColorModal" type="button" class="px-3 py-1 bg-red-600 text-white rounded">Cancelar</button>
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
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import NumberInput from '@/Components/NumberInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import CheckboxInput from '@/Components/CheckboxInput.vue'
import RadioGroup from '@/Components/RadioGroupInput.vue'
import DateInput from '@/Components/DateInput.vue'
import TextareaInput from '@/Components/TextareaInput.vue'

export type FieldDefinition = {
  name: string;
  label: string;
  type: 'Text' | 'Number' | 'Select' | 'Checkbox' | 'Radio' | 'Date' | 'Textarea';
  grid?: number;
  props?: Record<string, any>;
  options?: Array<{ label: string; value: any }>;
  validationRule?: (value: any) => boolean;
  errorMessage?: string;
};

const props = defineProps<{
  fields: FieldDefinition[];
  initialModel: Record<string, any>;
  submitLabel?: string;
  cancelButton?: string;
  columns?: number | string;
  title?: string;
  description?: string;
  cancelLabel?: string;
  cancelColor?: string;
  submitColor?: string;
}>();
const emit = defineEmits<{
  (e: 'submit', model: Record<string, any>): void;
  (e: 'cancel'): void;
}>();
const {
  fields,
  initialModel,
  submitLabel = 'Salvar',
  cancelButton = 'Cancelar',
  columns = 2,
  title = '',
  description = '',
} = props;

const model = reactive({ ...initialModel });
const errors = reactive<Record<string, string>>({});
const fieldsState = ref(fields.map(f => ({ ...f, props: { ...(f.props || {}) } })));

const componentMap = {
  text: TextInput,
  number: NumberInput,
  select: SelectInput,
  checkbox: CheckboxInput,
  radio: RadioGroup,
  date: DateInput,
  textarea: TextareaInput,
};

const baseInputClasses = 'rounded-md border-gray-300 dark:border-gray-600 bg-white px-3 py-2 text-gray-900 sm:text-sm w-full';
const gridColsClass = computed(() => `grid-cols-${columns}`);

// Configuração da DataTable
const tableColumns = [
  { label: 'Campo', field: 'label' },
  { label: 'Tipo', field: 'type' },
  { label: 'Cor Fundo', field: 'props.bgColor' },
  { label: 'Cor Texto', field: 'props.textColor' },
  { label: 'Ações', field: 'actions' }
];

const showColorModal = ref(false);
const currentTarget = ref<'field' | 'cancel' | 'submit'>('field');
const currentFieldIndex = ref<number | null>(null);
const modalBgColor = ref('#ffffff');
const modalTextColor = ref('#374151');

const cancelBgColor = ref('#e5e7eb');
const cancelTextColor = ref('#374151');
const submitBgColor = ref('#2563eb');
const submitTextColor = ref('#ffffff');

const modalTitle = computed(() => {
  if (currentTarget.value === 'field') return 'Selecionar cores do campo';
  if (currentTarget.value === 'cancel') return 'Selecionar cores do botão Cancelar';
  return 'Selecionar cores do botão Enviar';
});

function openFieldColorModal(idx: number) {
  currentTarget.value = 'field';
  currentFieldIndex.value = idx;
  const f = fieldsState.value[idx];
  modalBgColor.value = f.props.bgColor || '#ffffff';
  modalTextColor.value = f.props.textColor || '#374151';
  showColorModal.value = true;
}

function openButtonColorModal(type: 'cancel' | 'submit') {
  currentTarget.value = type;
  modalBgColor.value = type === 'cancel' ? cancelBgColor.value : submitBgColor.value;
  modalTextColor.value = type === 'cancel' ? cancelTextColor.value : submitTextColor.value;
  showColorModal.value = true;
}

function applyColors() {
  if (currentTarget.value === 'field' && currentFieldIndex.value !== null) {
    const f = fieldsState.value[currentFieldIndex.value];
    f.props.bgColor = modalBgColor.value;
    f.props.textColor = modalTextColor.value;
  } else if (currentTarget.value === 'cancel') {
    cancelBgColor.value = modalBgColor.value;
    cancelTextColor.value = modalTextColor.value;
  } else {
    submitBgColor.value = modalBgColor.value;
    submitTextColor.value = modalTextColor.value;
  }
  closeColorModal();
}

function closeColorModal() {
  showColorModal.value = false;
  currentFieldIndex.value = null;
}

function validate(): boolean {
  Object.keys(errors).forEach(k => delete errors[k]);
  let valid = true;
  fieldsState.value.forEach(f => {
    const v = (model as any)[f.name];
    if (f.validationRule && !f.validationRule(v)) {
      errors[f.name] = f.errorMessage || 'Inválido';
      valid = false;
    }
  });
  return valid;
}

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
