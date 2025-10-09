<template>
  <transition name="fade">
    <div v-if="visible" :class="wrapperClass" class="fixed right-4 top-4 z-50 rounded shadow-lg px-4 py-3 text-white">
      <div class="flex items-center gap-2">
        <span>{{ message }}</span>
        <button class="ml-2 text-white/80 hover:text-white" @click="visible=false">✕</button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const visible = ref(false)
const message = ref('')
const type = ref('success')

const wrapperClass = computed(() => {
  return type.value === 'error' ? 'bg-red-600' : (type.value === 'warning' ? 'bg-yellow-600' : 'bg-green-600')
})

function showToast({ text, kind='success', timeout=3000 }) {
  message.value = text
  type.value = kind
  visible.value = true
  setTimeout(() => visible.value = false, timeout)
}

onMounted(() => {
  window.__toast = showToast
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
