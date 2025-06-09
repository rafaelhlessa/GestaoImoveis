<template>
    <nav v-if="links && links.length" class="mt-4">
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
  import { defineProps } from 'vue'
  import { Link } from '@inertiajs/vue3'
  
  defineProps({
    links: {
      type: Array,
      required: true,
    },
  })
  </script>
  