<script setup>
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
</template>
