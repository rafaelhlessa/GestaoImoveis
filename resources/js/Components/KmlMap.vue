<!-- <script setup>
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import omnivore from '@mapbox/leaflet-omnivore';

const props = defineProps({
  kmlUrl: String, // URL do arquivo KML
});

const map = ref(null);
const mapElement = ref(null);
const kmlLayer = ref(null);

const defaultIcon = L.icon({
    iconUrl: '/storage/marker-icon.png',
    shadowUrl: '/storage/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    tooltipAnchor: [16, -28],
    shadowSize: [41, 41]
});
L.Marker.prototype.options.icon = defaultIcon;

onMounted(() => {
  if (!mapElement.value) return;

  map.value = L.map(mapElement.value).setView([-15.7801, -47.9292], 5);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map.value);

  if (props.kmlUrl) {
    console.log("📌 Chamando Leaflet com URL:", props.kmlUrl);
    loadKml(props.kmlUrl);
  }
});

// Função para carregar KML
const loadKml = (url) => {
  console.log("📌 Tentando carregar KML do URL:", url);

  if (!url) {
    console.error("⚠️ URL do KML está vazia!");
    return;
  }

  if (kmlLayer.value) {
    map.value.removeLayer(kmlLayer.value);
  }

  kmlLayer.value = omnivore.kml(url)
    .on('ready', function () {
      console.log("✅ KML carregado com sucesso!");
      map.value.fitBounds(kmlLayer.value.getBounds());
    })
    .on('error', function (e) {
      console.error("❌ Erro ao carregar KML:", e);
    })
    .addTo(map.value);
};

// Atualiza KML quando a URL mudar
watch(() => props.kmlUrl, (newUrl) => {
  if (newUrl && map.value) {
    console.log("📌 URL do KML mudou para:", newUrl);
    loadKml(newUrl);
  }
});
</script>

<template>
  <div ref="mapElement" class="w-full h-[600px]"></div>
</template> -->
<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import omnivore from '@mapbox/leaflet-omnivore';

const props = defineProps({
  kmlUrl: String, // URL do arquivo KML
  height: {
    type: String,
    default: '600px'
  }
});

const emit = defineEmits(['map-ready', 'kml-loaded', 'kml-error']);

const map = ref(null);
const mapElement = ref(null);
const kmlLayer = ref(null);

// Configuração correta dos ícones do Leaflet
const setupLeafletIcons = () => {
  // Fix para ícones do Leaflet
  delete L.Icon.Default.prototype._getIconUrl;
  
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    tooltipAnchor: [16, -28],
    shadowSize: [41, 41]
  });
};

// Configurar CORS proxy se necessário
const createProxyUrl = (url) => {
  // Se a URL já for local ou HTTPS, retorna como está
  if (url.startsWith('/') || url.startsWith(window.location.origin)) {
    return url;
  }
  
  // Para URLs externas, você pode precisar de um proxy CORS
  // Exemplo usando cors-anywhere (substitua pela sua solução de proxy)
  // return `https://cors-anywhere.herokuapp.com/${url}`;
  
  return url;
};

onMounted(() => {
  if (!mapElement.value) {
    console.error("❌ Elemento do mapa não encontrado");
    return;
  }

  console.log("🗺️ Inicializando mapa...");
  
  // Configurar ícones antes de criar o mapa
  setupLeafletIcons();

  // Criar mapa com configurações otimizadas
  map.value = L.map(mapElement.value, {
    center: [-15.7801, -47.9292], // Centro do Brasil
    zoom: 5,
    zoomControl: true,
    attributionControl: true
  });

  // Adicionar camada de tiles
  const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  });
  
  tileLayer.addTo(map.value);

  // Aguardar o mapa estar totalmente carregado
  map.value.whenReady(() => {
    console.log("✅ Mapa carregado e pronto");
    emit('map-ready', map.value);
    
    // Carregar KML se URL fornecida
    if (props.kmlUrl) {
      loadKml(props.kmlUrl);
    }
  });
});

// Função melhorada para carregar KML
const loadKml = (url) => {
  console.log("📌 Iniciando carregamento do KML:", url);

  if (!url || !url.trim()) {
    console.error("⚠️ URL do KML está vazia!");
    emit('kml-error', new Error('URL do KML não fornecida'));
    return;
  }

  if (!map.value) {
    console.error("⚠️ Mapa não inicializado!");
    emit('kml-error', new Error('Mapa não inicializado'));
    return;
  }

  // Remove camada anterior se existir
  if (kmlLayer.value) {
    console.log("🗑️ Removendo camada KML anterior...");
    map.value.removeLayer(kmlLayer.value);
    kmlLayer.value = null;
  }

  // Preparar URL (aplicar proxy se necessário)
  const processedUrl = createProxyUrl(url);
  console.log("🔗 URL processada:", processedUrl);

  // Criar nova camada KML com configurações detalhadas
  kmlLayer.value = omnivore.kml(processedUrl)
    .on('ready', function(e) {
      console.log("✅ KML carregado com sucesso!");
      console.log("📊 Dados da camada:", e.target);
      
      const bounds = e.target.getBounds();
      console.log("📍 Bounds do KML:", bounds);
      
      if (bounds.isValid()) {
        // Ajustar visualização para mostrar todo o conteúdo KML
        map.value.fitBounds(bounds, {
          padding: [20, 20],
          maxZoom: 16
        });
        console.log("🎯 Mapa ajustado para mostrar KML");
      } else {
        console.warn("⚠️ Bounds inválidos, mantendo visualização atual");
      }
      
      // Contar features carregadas
      let featureCount = 0;
      e.target.eachLayer(() => featureCount++);
      console.log(`📊 Total de features carregadas: ${featureCount}`);
      
      emit('kml-loaded', {
        layer: e.target,
        bounds: bounds,
        featureCount: featureCount
      });
    })
    .on('error', function(e) {
      console.error("❌ Erro ao carregar KML:", e);
      console.error("🔍 Detalhes do erro:", {
        url: processedUrl,
        error: e.error,
        target: e.target
      });
      
      emit('kml-error', {
        error: e.error,
        url: processedUrl,
        message: 'Falha ao carregar arquivo KML'
      });
    })
    .addTo(map.value);

  // Timeout para detectar carregamento lento
  setTimeout(() => {
    if (kmlLayer.value && kmlLayer.value.getLayers().length === 0) {
      console.warn("⏰ KML demorou para carregar ou está vazio");
      
      // Tentar diagnóstico
      fetch(processedUrl, { method: 'HEAD' })
        .then(response => {
          console.log("🔍 Status da URL:", response.status, response.statusText);
          console.log("🔍 Headers:", response.headers);
        })
        .catch(error => {
          console.error("🔍 Erro ao verificar URL:", error);
        });
    }
  }, 5000);
};

// Função para adicionar marcadores customizados (fallback)
const addCustomMarker = (lat, lng, title = '', content = '') => {
  if (!map.value) return null;
  
  const marker = L.marker([lat, lng])
    .addTo(map.value);
    
  if (title || content) {
    marker.bindPopup(`
      <div>
        ${title ? `<h3>${title}</h3>` : ''}
        ${content ? `<p>${content}</p>` : ''}
      </div>
    `);
  }
  
  return marker;
};

// Função para diagnosticar problemas
const diagnoseKml = async (url) => {
  console.log("🔧 Iniciando diagnóstico do KML...");
  
  try {
    const response = await fetch(url);
    const text = await response.text();
    
    console.log("📄 Conteúdo do KML (primeiros 500 chars):", text.substring(0, 500));
    console.log("📄 Tamanho do arquivo:", text.length, "bytes");
    
    // Verificar se é um XML válido
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(text, "text/xml");
    const parseError = xmlDoc.getElementsByTagName("parsererror");
    
    if (parseError.length > 0) {
      console.error("❌ XML inválido:", parseError[0].textContent);
    } else {
      console.log("✅ XML válido");
      
      // Verificar elementos KML
      const placemarks = xmlDoc.getElementsByTagName("Placemark");
      const coordinates = xmlDoc.getElementsByTagName("coordinates");
      
      console.log(`📊 Placemarks encontrados: ${placemarks.length}`);
      console.log(`📊 Coordenadas encontradas: ${coordinates.length}`);
      
      if (coordinates.length > 0) {
        console.log("📍 Primeira coordenada:", coordinates[0].textContent.trim().substring(0, 100));
      }
    }
    
  } catch (error) {
    console.error("❌ Erro no diagnóstico:", error);
  }
};

// Observar mudanças na URL do KML
watch(() => props.kmlUrl, (newUrl, oldUrl) => {
  if (newUrl !== oldUrl && newUrl && map.value) {
    console.log("📌 URL do KML mudou:", { old: oldUrl, new: newUrl });
    loadKml(newUrl);
  }
});

// Limpeza ao desmontar componente
onUnmounted(() => {
  if (map.value) {
    console.log("🗑️ Limpando mapa...");
    map.value.remove();
  }
});

// Expor métodos para o componente pai
defineExpose({
  map: () => map.value,
  kmlLayer: () => kmlLayer.value,
  loadKml,
  addCustomMarker,
  diagnoseKml
});
</script>

<template>
  <div 
    ref="mapElement" 
    class="w-full border border-gray-300 rounded-lg"
    :style="{ height: height }"
  >
    <!-- Loading indicator -->
    <div 
      v-if="!map" 
      class="flex items-center justify-center h-full bg-gray-100"
    >
      <div class="text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
        <p class="text-gray-600">Carregando mapa...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Garantir que o Leaflet funcione corretamente */
:deep(.leaflet-container) {
  height: 100%;
  width: 100%;
  font-family: inherit;
}

:deep(.leaflet-popup-content) {
  margin: 8px 12px;
  line-height: 1.4;
}

:deep(.leaflet-popup-content h3) {
  margin: 0 0 8px 0;
  font-weight: bold;
}

:deep(.leaflet-popup-content p) {
  margin: 0;
}
</style>