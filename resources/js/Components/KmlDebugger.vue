<template>
  <div class="bg-white border border-gray-300 rounded-lg p-4 mb-4">
    <h3 class="text-lg font-semibold mb-4">🔧 KML Debugger</h3>
    
    <!-- Status do Mapa -->
    <div class="mb-4">
      <h4 class="font-medium mb-2">Status do Mapa:</h4>
      <div class="flex items-center space-x-4">
        <span :class="mapStatus.color" class="px-2 py-1 rounded text-sm">
          {{ mapStatus.text }}
        </span>
        <span v-if="mapReady" class="text-green-600 text-sm">
          ✅ Zoom: {{ currentZoom }} | Centro: {{ mapCenter }}
        </span>
      </div>
    </div>

    <!-- Status do KML -->
    <div class="mb-4">
      <h4 class="font-medium mb-2">Status do KML:</h4>
      <div class="space-y-2">
        <div class="flex items-center space-x-2">
          <span :class="kmlStatus.color" class="px-2 py-1 rounded text-sm">
            {{ kmlStatus.text }}
          </span>
          <span v-if="featureCount >= 0" class="text-blue-600 text-sm">
            Features: {{ featureCount }}
          </span>
        </div>
        
        <div v-if="kmlUrl" class="text-sm text-gray-600">
          <strong>URL:</strong> 
          <a :href="kmlUrl" target="_blank" class="text-blue-600 hover:underline break-all">
            {{ kmlUrl }}
          </a>
        </div>
        
        <div v-if="kmlBounds" class="text-sm text-gray-600">
          <strong>Bounds:</strong> {{ kmlBounds }}
        </div>
      </div>
    </div>

    <!-- Logs -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-medium">Logs de Debug:</h4>
        <button 
          @click="clearLogs"
          class="text-sm text-red-600 hover:text-red-800"
        >
          Limpar
        </button>
      </div>
      <div class="bg-gray-100 rounded p-3 max-h-32 overflow-y-auto">
        <div v-if="logs.length === 0" class="text-gray-500 text-sm">
          Nenhum log ainda...
        </div>
        <div 
          v-for="(log, index) in logs" 
          :key="index"
          :class="getLogClass(log.type)"
          class="text-sm font-mono mb-1"
        >
          <span class="text-gray-500">{{ log.time }}</span> - {{ log.message }}
        </div>
      </div>
    </div>

    <!-- Ações de Debug -->
    <div class="mb-4">
      <h4 class="font-medium mb-2">Ações de Debug:</h4>
      <div class="flex flex-wrap gap-2">
        <button 
          @click="reloadKml"
          :disabled="!kmlUrl"
          class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50"
        >
          🔄 Recarregar KML
        </button>
        
        <button 
          @click="diagnoseKml"
          :disabled="!kmlUrl"
          class="px-3 py-1 bg-orange-600 text-white rounded text-sm hover:bg-orange-700 disabled:opacity-50"
        >
          🔍 Diagnosticar KML
        </button>
        
        <button 
          @click="addTestMarker"
          :disabled="!mapReady"
          class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50"
        >
          📍 Adicionar Marker Teste
        </button>
        
        <button 
          @click="centerBrazil"
          :disabled="!mapReady"
          class="px-3 py-1 bg-purple-600 text-white rounded text-sm hover:bg-purple-700 disabled:opacity-50"
        >
          🇧🇷 Centralizar no Brasil
        </button>
      </div>
    </div>

    <!-- URL de Teste -->
    <div>
      <h4 class="font-medium mb-2">Testar Nova URL:</h4>
      <div class="flex gap-2">
        <input 
          v-model="testUrl"
          placeholder="Cole uma URL de KML para testar..."
          class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm"
        />
        <button 
          @click="loadTestUrl"
          :disabled="!testUrl.trim()"
          class="px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 disabled:opacity-50"
        >
          Testar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  kmlMapRef: Object,
  kmlUrl: String
})

const emit = defineEmits(['update:kmlUrl'])

// Estado
const logs = ref([])
const mapReady = ref(false)
const kmlLoaded = ref(false)
const kmlError = ref(null)
const featureCount = ref(-1)
const kmlBounds = ref(null)
const currentZoom = ref(0)
const mapCenter = ref('')
const testUrl = ref('')

// Computed
const mapStatus = computed(() => {
  if (mapReady.value) {
    return { text: 'Carregado', color: 'bg-green-100 text-green-800' }
  }
  return { text: 'Carregando...', color: 'bg-yellow-100 text-yellow-800' }
})

const kmlStatus = computed(() => {
  if (kmlError.value) {
    return { text: 'Erro', color: 'bg-red-100 text-red-800' }
  }
  if (kmlLoaded.value) {
    return { text: 'Carregado', color: 'bg-green-100 text-green-800' }
  }
  if (props.kmlUrl) {
    return { text: 'Carregando...', color: 'bg-yellow-100 text-yellow-800' }
  }
  return { text: 'Nenhum KML', color: 'bg-gray-100 text-gray-800' }
})

// Métodos
const addLog = (type, message) => {
  const time = new Date().toLocaleTimeString()
  logs.value.unshift({ type, message, time })
  
  // Limitar a 50 logs
  if (logs.value.length > 50) {
    logs.value = logs.value.slice(0, 50)
  }
}

const getLogClass = (type) => {
  switch (type) {
    case 'error': return 'text-red-600'
    case 'warning': return 'text-orange-600'
    case 'success': return 'text-green-600'
    default: return 'text-gray-600'
  }
}

const clearLogs = () => {
  logs.value = []
}

const reloadKml = () => {
  if (props.kmlMapRef?.loadKml && props.kmlUrl) {
    addLog('info', 'Recarregando KML...')
    kmlLoaded.value = false
    kmlError.value = null
    featureCount.value = -1
    kmlBounds.value = null
    props.kmlMapRef.loadKml(props.kmlUrl)
  }
}

const diagnoseKml = async () => {
  if (props.kmlMapRef?.diagnoseKml && props.kmlUrl) {
    addLog('info', 'Iniciando diagnóstico...')
    try {
      await props.kmlMapRef.diagnoseKml(props.kmlUrl)
      addLog('success', 'Diagnóstico concluído - verifique o console')
    } catch (error) {
      addLog('error', `Erro no diagnóstico: ${error.message}`)
    }
  }
}

const addTestMarker = () => {
  if (props.kmlMapRef?.addCustomMarker) {
    // Coordenadas de Brasília
    const marker = props.kmlMapRef.addCustomMarker(
      -15.7942, 
      -47.8822, 
      'Marker de Teste', 
      'Este é um marker de teste adicionado pelo debugger'
    )
    if (marker) {
      addLog('success', 'Marker de teste adicionado em Brasília')
    } else {
      addLog('error', 'Falha ao adicionar marker de teste')
    }
  }
}

const centerBrazil = () => {
  const map = props.kmlMapRef?.map()
  if (map) {
    map.setView([-15.7801, -47.9292], 5)
    addLog('info', 'Mapa centralizado no Brasil')
  }
}

const loadTestUrl = () => {
  if (testUrl.value.trim()) {
    emit('update:kmlUrl', testUrl.value.trim())
    addLog('info', `Carregando URL de teste: ${testUrl.value.trim()}`)
  }
}

const updateMapInfo = () => {
  const map = props.kmlMapRef?.map()
  if (map) {
    currentZoom.value = map.getZoom()
    const center = map.getCenter()
    mapCenter.value = `${center.lat.toFixed(4)}, ${center.lng.toFixed(4)}`
  }
}

// Watchers
watch(() => props.kmlUrl, (newUrl) => {
  if (newUrl) {
    addLog('info', `Nova URL do KML: ${newUrl}`)
    kmlLoaded.value = false
    kmlError.value = null
    featureCount.value = -1
    kmlBounds.value = null
  }
})

// Eventos do mapa (simulados - você precisará conectar aos eventos reais)
const onMapReady = () => {
  mapReady.value = true
  addLog('success', 'Mapa carregado e pronto')
  updateMapInfo()
  
  // Atualizar info do mapa quando houver mudanças
  const map = props.kmlMapRef?.map()
  if (map) {
    map.on('zoomend moveend', updateMapInfo)
  }
}

const onKmlLoaded = (data) => {
  kmlLoaded.value = true
  kmlError.value = null
  featureCount.value = data.featureCount || 0
  kmlBounds.value = data.bounds ? 
    `${data.bounds.getNorth().toFixed(4)}, ${data.bounds.getEast().toFixed(4)}` : 
    null
  addLog('success', `KML carregado com ${data.featureCount} features`)
}

const onKmlError = (error) => {
  kmlLoaded.value = false
  kmlError.value = error
  addLog('error', `Erro ao carregar KML: ${error.message || error}`)
}

// Expor eventos para o componente pai conectar
defineExpose({
  onMapReady,
  onKmlLoaded,
  onKmlError,
  addLog
})
</script>