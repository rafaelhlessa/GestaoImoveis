<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: {
    type: [File, Array],
    default: null,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
});

const input = ref(null);

const proxyFiles = computed({
  get() {
    return props.modelValue;
  },
  set(val) {
    emit('update:modelValue', val);
  },
});

function onChange(event) {
  const files = event.target.files;
  proxyFiles.value = props.multiple ? Array.from(files) : files[0];
}

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
  <input
    ref="input"
    type="file"
    :multiple="props.multiple"
    @change="onChange"
    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50
           dark:border-gray-600 dark:bg-gray-700 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
  />
</template>
