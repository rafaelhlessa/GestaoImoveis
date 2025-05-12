<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: [String, Number],
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
});

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

defineExpose({ focus: () => select.value.focus() });
</script>

<template>
    <select
        ref="select"
        v-model="model"
        :style="{
            backgroundColor: props.bgColor,
            color: props.textColor
        }"
        class="rounded-md border-gray-300 shadow-sm 
               focus:border-indigo-500 focus:ring-indigo-500
               dark:border-gray-700 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
    >
        <slot />
    </select>
</template>
