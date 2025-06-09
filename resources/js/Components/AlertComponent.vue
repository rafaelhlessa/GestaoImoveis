<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { CheckCircleIcon, XMarkIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/20/solid';

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
  autoClose: {
    type: Boolean,
    default: true
  },
  duration: {
    type: Number,
    default: 3000
  }
});

const emit = defineEmits(['close', 'update:show']);

const isVisible = ref(props.show);
let timer = null;

// Classes dinâmicas para cores de alertas
const colors = {
  success: "bg-green-200 text-green-800",
  error: "bg-red-200 text-red-800",
  warning: "bg-yellow-100 text-yellow-800",
  info: "bg-blue-200 text-blue-800"
};

// Determina o ícone com base no tipo de alerta
const AlertIcon = () => {
  switch (props.type) {
    case 'success':
      return CheckCircleIcon;
    case 'error':
      return XCircleIcon;
    case 'warning':
      return ExclamationTriangleIcon;
    default:
      return CheckCircleIcon;
  }
};

// Iniciar o temporizador de auto-fechamento
const startTimer = () => {
  if (props.autoClose && isVisible.value) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      closeAlert();
    }, props.duration);
  }
};

// Fechamento do alerta
const closeAlert = () => {
  isVisible.value = false;
  emit('close');
  emit('update:show', false);
};

// Observar mudanças na prop show
watch(
  () => props.show,
  (newValue) => {
    isVisible.value = newValue;
    if (newValue && props.autoClose) {
      startTimer();
    }
  }
);

// Configurar temporizador quando montado
onMounted(() => {
  if (props.show && props.autoClose) {
    startTimer();
  }
});

// Limpar temporizador antes de desmontar
onBeforeUnmount(() => {
  clearTimeout(timer);
});
</script>

<template>
  <transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div :class="`rounded-md px-14 py-8 ${colors[type]}`">
        <div class="flex">
          <div class="shrink-0">
            <component :is="AlertIcon()" class="size-5" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium">{{ message }}</p>
          </div>
          <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
              <button
                type="button"
                @click="closeAlert"
                class="inline-flex rounded-md bg-white bg-opacity-25 p-1.5 ml-8 border text-gray-500 hover:bg-white hover:bg-opacity-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
              >
                <XMarkIcon class="size-4" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>