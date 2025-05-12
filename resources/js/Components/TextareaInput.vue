<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
  type: String,
  required: true,
});

const props = defineProps({
  bgColor: {
    type: String,
    default: '#fff'       // ex: '#f00', 'rgb(10,20,30)' ou classes Tailwind como 'bg-red-500'
  },
  textColor: {
    type: String,
    default: 'text-gray-700'       // ex: '#fff', 'white' ou classes Tailwind como 'text-white'
  },
  rows: {
    type: Number,
    default: 4            // Define o número de linhas padrão do textarea
  },
  resize: {
    type: String,
    default: 'vertical'   // Opções: 'none', 'both', 'horizontal', 'vertical'
  }
});

const textarea = ref(null);

onMounted(() => {
  if (textarea.value.hasAttribute('autofocus')) {
    textarea.value.focus();
  }
});

defineExpose({ focus: () => textarea.value.focus() });
</script>

<template>
  <textarea
    ref="textarea"
    v-model="model"
    :rows="rows"
    :style="{
      backgroundColor: props.bgColor,
      color: props.textColor,
      resize: props.resize
    }"
    class="w-full rounded-md border-gray-300 shadow-sm
           focus:border-indigo-500 focus:ring-indigo-500/50
           dark:border-gray-700 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
  ></textarea>
</template>