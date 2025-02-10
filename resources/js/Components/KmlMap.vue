<template>
    TEste
    <div id="map" style="height: 800px;"></div>
  </template>
  
  <script setup>
  import { onMounted, watch } from 'vue';
  import L from 'leaflet';
  import 'leaflet-kml';
  
  // Recebe a propriedade `kmlData` passada pelo componente pai
  defineProps({
    kmlData: {
      type: String,
      required: true,
    },
  });
  
  // Função para carregar o KML a partir de Base64
  const loadKmlFromBase64 = (base64) => {
    const rawKml = atob(base64.split(',')[1]); // Decodifica o conteúdo Base64
    const kmlLayer = new L.KML(rawKml);
    map.addLayer(kmlLayer);
    map.fitBounds(kmlLayer.getBounds());
  };
  
  let map;
  
  onMounted(() => {
    // Inicializa o mapa
    map = L.map('map').setView([0, 0], 2);
  
    // Adiciona o layer de tiles do OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap',
    }).addTo(map);
  
    // Carrega o KML inicial
    loadKmlFromBase64(kmlData);
  });
  
  // Observa alterações na propriedade `kmlData` para recarregar o KML
  watch(
    () => kmlData,
    (newData) => {
      if (map && newData) {
        loadKmlFromBase64(newData);
      }
    }
  );
  </script>
  