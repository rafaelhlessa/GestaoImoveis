<!-- <script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
        v-model="model"
        ref="input"
    />
</template> -->
<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
  type: String,
  required: true,
});

const props = defineProps({
  bgColor: {
    type: String,
    default: ''       // ex: '#f00', 'rgb(10,20,30)' ou classes Tailwind como 'bg-red-500'
  },
  textColor: {
    type: String,
    default: ''       // ex: '#fff', 'white' ou classes Tailwind como 'text-white'
  },
});

const input = ref(null);

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
    v-model="model"
    :style="{
      backgroundColor: props.bgColor,
      color: props.textColor
    }"
    class="rounded-md border-gray-300 shadow-sm
           focus:border-indigo-500 focus:ring-indigo-500/50
           dark:border-gray-700 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
  />
</template>
