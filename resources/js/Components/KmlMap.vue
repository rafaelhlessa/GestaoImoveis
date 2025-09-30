<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import omnivore from '@mapbox/leaflet-omnivore';

const props = defineProps({
  kmlUrl: String,
  height: {
    type: String,
    default: '600px'
  }
});

const emit = defineEmits(['kml-error']);

const map = ref(null);
const mapElement = ref(null);
const kmlLayer = ref(null);

// Configurar ícone padrão do Leaflet
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
    console.log("📌 Carregando KML inicial:", props.kmlUrl);
    loadKml(props.kmlUrl);
  }
});

onUnmounted(() => {
  if (map.value) {
    map.value.remove();
  }
});

const loadKml = (url) => {
  console.log("📌 Tentando carregar KML:", url);

  if (!url) {
    console.error("⚠️ URL do KML está vazia!");
    emit('kml-error', 'URL do KML não fornecida');
    return;
  }

  if (!map.value) {
    console.error("⚠️ Mapa não está inicializado!");
    return;
  }

  if (kmlLayer.value) {
    map.value.removeLayer(kmlLayer.value);
  }

  try {
    kmlLayer.value = omnivore.kml(url)
      .on('ready', (e) => {
        console.log("✅ KML carregado com sucesso");
        const bounds = e.target.getBounds();
        console.log("📍 Bounds do KML:", bounds);

        let featureCount = 0;
        let firstCoordinates = null;
        let allCoordinates = [];

        e.target.eachLayer((layer) => {
          featureCount++;

          if (layer.getLatLng) {
            const latLng = layer.getLatLng();
            allCoordinates.push(latLng);
            if (!firstCoordinates) {
              firstCoordinates = latLng;
            }
          } else if (layer.getLatLngs) {
            const latLngs = layer.getLatLngs();
            if (Array.isArray(latLngs) && latLngs.length > 0) {
              latLngs.forEach(coord => {
                if (coord.lat && coord.lng) {
                  allCoordinates.push(coord);
                  if (!firstCoordinates) {
                    firstCoordinates = coord;
                  }
                }
              });
            }
          }
        });

        console.log(`📊 Total de features: ${featureCount}`);
        console.log(`📍 Coordenadas encontradas: ${allCoordinates.length}`);

        if (bounds.isValid() && featureCount > 0) {
          map.value.fitBounds(bounds, {
            padding: [20, 20],
            maxZoom: 16
          });
          console.log("🎯 Mapa ajustado usando bounds");
        } else if (allCoordinates.length > 0) {
          console.log("🔧 Criando bounds manualmente");
          const group = new L.featureGroup(e.target.getLayers());
          const groupBounds = group.getBounds();

          if (groupBounds.isValid()) {
            map.value.fitBounds(groupBounds, {
              padding: [20, 20],
              maxZoom: 16
            });
            console.log("🎯 Mapa ajustado usando bounds do grupo");
          } else if (firstCoordinates) {
            map.value.setView(firstCoordinates, 15);
            console.log("🎯 Centralizando na primeira coordenada");
          }
        } else {
          console.warn("⚠️ Nenhuma coordenada válida encontrada");
        }
      })
      .on('error', (e) => {
        console.error("❌ Erro ao carregar KML:", e);
        emit('kml-error', `Erro ao carregar arquivo KML: ${e.error?.message || 'Erro desconhecido'}`);
      })
      .addTo(map.value);

  } catch (error) {
    console.error("❌ Erro ao inicializar KML:", error);
    emit('kml-error', `Erro ao processar KML: ${error.message}`);
  }
};

watch(() => props.kmlUrl, (newUrl) => {
  if (newUrl && map.value) {
    console.log("📌 URL do KML mudou para:", newUrl);
    loadKml(newUrl);
  }
});
</script>

<template>
  <div
    ref="mapElement"
    class="w-full"
    :style="{ height: height }"
  ></div>
</template>

<style scoped>
:deep(.leaflet-container) {
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
