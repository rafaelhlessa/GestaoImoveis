<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
    bgColor: {
    type: String,
    default: '#fff'       // ex: '#f00', 'rgb(10,20,30)' ou classes Tailwind como 'bg-red-500'
    },
    textColor: {
        type: String,
        default: 'text-gray-700'       // ex: '#fff', 'white' ou classes Tailwind como 'text-white'
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        :style="{
            backgroundColor: props.bgColor,
            color: props.textColor
        }"
        class="rounded border-gray-300 shadow-sm 
               text-indigo-600  focus:ring-indigo-500 
               dark:border-gray-700 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
    />
</template>
