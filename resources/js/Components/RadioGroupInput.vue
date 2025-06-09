<script setup>
const model = defineModel({
  type: [String, Number],
  required: true,
});

const props = defineProps({
  options: {
    type: Array,
    required: true,
    // cada opção: { value: any, label: string }
  },
  name: {
    type: String,
    default: '',
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
</script>

<template>
  <div class="flex space-x-4">
    <label
      v-for="opt in props.options"
      :key="opt.value"
      class="inline-flex items-center cursor-pointer"
    >
      <input
        type="radio"
        :name="props.name"
        :value="opt.value"
        v-model="model"
        :style="{
          backgroundColor: props.bgColor,
          color: props.textColor
        }"
        class="rounded-md shadow-sm focus:ring-indigo-500
               dark:focus:ring-indigo-600 dark:ring-offset-gray-800"
      />
      <span class="ml-2">{{ opt.label }}</span>
    </label>
  </div>
</template>
