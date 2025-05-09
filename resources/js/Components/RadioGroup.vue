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
        class="text-indigo-600 shadow-sm focus:ring-indigo-500
               dark:bg-gray-700 dark:focus:ring-indigo-600 dark:ring-offset-gray-800"
      />
      <span class="ml-2">{{ opt.label }}</span>
    </label>
  </div>
</template>
