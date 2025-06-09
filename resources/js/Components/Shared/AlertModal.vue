<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="$emit('close')"
      >
        <div 
          :class="`rounded-md px-14 py-8 ${alertClass}`"
          @click.stop
        >
          <div class="flex">
            <div class="shrink-0">
              <ExclamationTriangleIcon 
                v-if="type === 'warning'" 
                class="size-5 text-yellow-400" 
                aria-hidden="true" 
              />
              <XCircleIcon 
                v-if="type === 'error'" 
                class="size-5 text-red-400" 
                aria-hidden="true" 
              />
              <CheckCircleIcon 
                v-if="type === 'success'" 
                class="size-5 text-green-400" 
                aria-hidden="true" 
              />
              <InformationCircleIcon 
                v-if="type === 'info'" 
                class="size-5 text-blue-400" 
                aria-hidden="true" 
              />
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium">{{ message }}</p>
            </div>
            <div class="ml-auto pl-3">
              <div class="-mx-1.5 -my-1.5">
                <button 
                  type="button" 
                  @click="$emit('close')"
                  class="inline-flex rounded-md bg-gray-50 p-1.5 ml-8 border text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 focus:ring-offset-gray-50"
                >
                  <XMarkIcon class="size-4" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { 
  CheckCircleIcon, 
  XMarkIcon, 
  ExclamationTriangleIcon, 
  XCircleIcon,
  InformationCircleIcon 
} from '@heroicons/vue/20/solid'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  color: {
    type: String,
    default: 'gray'
  }
})

defineEmits(['close'])

const colors = {
  red: "bg-red-200 text-red-800",
  green: "bg-green-200 text-green-800",
  blue: "bg-blue-200 text-blue-800",
  yellow: "bg-yellow-100 text-yellow-800",
  gray: "bg-gray-200 text-gray-800",
}

const alertClass = computed(() => colors[props.color] || "bg-gray-200 text-gray-800")
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>