<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-600 dark:text-gray-400">
          <tr>
            <th
              v-for="column in columns"
              :key="column.field"
              class="px-6 py-3"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="row in items"
            :key="row.id"
            class="bg-white border-b dark:bg-gray-700 dark:border-gray-500"
          >
            <td
              v-for="column in columns"
              :key="column.field"
              class="px-6 py-4 text-gray-900 dark:text-white"
            >
              <!-- Se houver slot para esta coluna -->
              <template v-if="$slots[column.field]">
                <slot :name="column.field" :row="row" />
              </template>
              <!-- Caso contrário, usa o valor simples ou formatado -->
              <template v-else>
                {{ column.format ? column.format(row[column.field], row) : row[column.field] }}
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <nav v-if="links.length" class="mt-4">
      <ul class="inline-flex -space-x-px">
        <li v-for="link in links" :key="link.label">
          <Link
            :href="link.url"
            v-html="link.label"
            :class="[
              'px-3 py-1 border border-gray-300 hover:bg-gray-100',
              link.active ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'
            ]"
          />
        </li>
      </ul>
    </nav>
  </template>

  <script setup>
  import { computed } from 'vue'
  import { Link } from '@inertiajs/vue3'

  const props = defineProps({
    data: {
      type: [Array, Object],
      required: true,
    },
    columns: {
      type: Array,
      required: true,
    },
  })

  const items = computed(() =>
    Array.isArray(props.data)
      ? props.data
      : (props.data.data ?? [])
  )

  const links = computed(() =>
    Array.isArray(props.data)
      ? []
      : (props.data.links ?? [])
  )
  </script>

  <style scoped>
  /* scoped styles caso queira ajustes adicionais */
  </style>
