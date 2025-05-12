<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

// Crie uma instância específica do Axios para a API do IBGE
// sem a configuração withCredentials que causa o erro CORS
const ibgeAxios = axios.create({
    baseURL: 'https://servicodados.ibge.gov.br/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    },
    // IMPORTANTE: NÃO usar withCredentials para API externa
    withCredentials: false
});

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ id: null, nome: '', uf: '' }),
    },
    cityId: { // Adicionando prop para o city_id
        type: [Number, String],
        default: null
    },
    placeholder: {
        type: String,
        default: 'Digite o nome da cidade'
    },
    required: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        default: 'Município/Estado'
    },
    error: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'city-selected', 'update:cityId']);

// Estados reativos
const allCities = ref([]);
const filteredCities = ref([]);
const isLoadingCities = ref(false);
const showSuggestions = ref(false);
const searchTerm = ref(props.modelValue?.nome || '');
const inputId = `city-selector-${Math.random().toString(36).substring(2, 9)}`;

// Configuração inicial
onMounted(async () => {
    await fetchCities();
    
    // Se temos um cityId, vamos localizar a cidade correspondente após carregar todas as cidades
    if (props.cityId) {
        setTimeout(async () => {
            findAndSelectCityById(props.cityId);
        }, 500); // Pequeno delay para garantir que as cidades foram carregadas
    }
});

// Buscar cidades do IBGE
const fetchCities = async () => {
    try {
        isLoadingCities.value = true;
        
        // Método 1: Buscar estados e criar um mapa de IDs para siglas
        const estadosMap = {};
        try {
            // MODIFICADO: Usar ibgeAxios em vez de axios global
            const estadosResponse = await ibgeAxios.get('/localidades/estados');
            estadosResponse.data.forEach(estado => {
                estadosMap[estado.id] = estado.sigla;
            });
        } catch (err) {
            console.warn('Erro ao buscar estados:', err);
        }
        
        // Método 2: Buscar municípios
        // MODIFICADO: Usar ibgeAxios em vez de axios global
        const response = await ibgeAxios.get('/localidades/municipios');
        
        // Processar municípios de forma segura
        allCities.value = response.data.map(city => {
            let estadoId = null;
            let ufSigla = '';
            
            // Tentar diferentes caminhos na estrutura do objeto
            if (city.microrregiao?.mesorregiao?.UF) {
                estadoId = city.microrregiao.mesorregiao.UF.id;
                ufSigla = city.microrregiao.mesorregiao.UF.sigla;
            } else if (city.municipio?.microrregiao?.mesorregiao?.UF) {
                estadoId = city.municipio.microrregiao.mesorregiao.UF.id;
                ufSigla = city.municipio.microrregiao.mesorregiao.UF.sigla;
            }
            
            // Backup: usar o mapa de estados
            if (!ufSigla && estadoId && estadosMap[estadoId]) {
                ufSigla = estadosMap[estadoId];
            }
            
            return {
                id: city.id,
                nome: city.nome,
                uf: ufSigla,
                displayName: ufSigla ? `${city.nome} / ${ufSigla}` : city.nome
            };
        });
    } catch (error) {
        console.error('Erro ao buscar cidades do IBGE:', error);
        
        // Implementar fallback para facilitar testes
        try {
            // Tentar buscar apenas algumas cidades principais
            allCities.value = [
                { id: 3550308, nome: "São Paulo", uf: "SP", displayName: "São Paulo / SP" },
                { id: 3304557, nome: "Rio de Janeiro", uf: "RJ", displayName: "Rio de Janeiro / RJ" },
                { id: 5300108, nome: "Brasília", uf: "DF", displayName: "Brasília / DF" },
                { id: 2927408, nome: "Salvador", uf: "BA", displayName: "Salvador / BA" },
                { id: 2304400, nome: "Fortaleza", uf: "CE", displayName: "Fortaleza / CE" },
                { id: 3106200, nome: "Belo Horizonte", uf: "MG", displayName: "Belo Horizonte / MG" },
                { id: 4106902, nome: "Curitiba", uf: "PR", displayName: "Curitiba / PR" },
                { id: 1501402, nome: "Belém", uf: "PA", displayName: "Belém / PA" },
                { id: 2611606, nome: "Recife", uf: "PE", displayName: "Recife / PE" },
                { id: 4314902, nome: "Porto Alegre", uf: "RS", displayName: "Porto Alegre / RS" },
                { id: 4205407, nome: "Florianópolis", uf: "SC", displayName: "Florianópolis / SC" }
            ];
        } catch (fallbackError) {
            console.error('Erro no fallback:', fallbackError);
            allCities.value = [];
        }
    } finally {
        isLoadingCities.value = false;
    }
};

// Filtrar cidades com base na busca
const filterCities = (query) => {
    if (query.length >= 3) {
        // Normaliza os termos de busca (remove acentos, etc.)
        const normalizedQuery = query.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        
        filteredCities.value = allCities.value.filter((city) => {
            const normalizedName = city.nome.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const normalizedUF = city.uf.toLowerCase();
            
            return normalizedName.includes(normalizedQuery) || 
                   normalizedUF.includes(normalizedQuery) ||
                   `${normalizedName} ${normalizedUF}`.includes(normalizedQuery);
        });
        
        // Limita o número de resultados para melhor desempenho na UI
        filteredCities.value = filteredCities.value.slice(0, 100);
        showSuggestions.value = filteredCities.value.length > 0;
    } else {
        filteredCities.value = [];
        showSuggestions.value = false;
    }
};

// Selecionar uma cidade
const selectCity = (city) => {
    searchTerm.value = city.displayName;
    emit('update:modelValue', {
        id: city.id,
        nome: city.nome,
        uf: city.uf
    });
    emit('city-selected', city);
    emit('update:cityId', city.id); // Emitir o cityId atualizado
    showSuggestions.value = false;
};

// Localizar cidade pelo ID
const findAndSelectCityById = (cityId) => {
    if (!cityId || !allCities.value || allCities.value.length === 0) return;
    
    const cityIdInt = parseInt(cityId);
    const foundCity = allCities.value.find(city => parseInt(city.id) === cityIdInt);
    
    if (foundCity) {
        searchTerm.value = foundCity.displayName;
        emit('update:modelValue', {
            id: foundCity.id,
            nome: foundCity.nome,
            uf: foundCity.uf
        });
        emit('city-selected', foundCity);
    }
};

// Observa mudanças no searchTerm
watch(searchTerm, (newValue) => {
    filterCities(newValue);
});

// Observa mudanças na prop cityId
watch(() => props.cityId, (newCityId) => {
    if (newCityId && allCities.value.length > 0) {
        findAndSelectCityById(newCityId);
    }
}, { immediate: true });

// Fechar sugestões com atraso para permitir cliques
const closeSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

// Método para limpar a seleção
const clearSelection = () => {
    searchTerm.value = '';
    emit('update:modelValue', { id: null, nome: '', uf: '' });
    emit('update:cityId', null);
};
</script>

<template>
    <div class="ibge-city-selector">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-900">{{ label }}</label>
        <div class="relative mt-2">
            <input 
                :id="inputId"
                type="text" 
                v-model="searchTerm"
                :placeholder="placeholder"
                :required="required"
                @focus="showSuggestions = true" 
                @blur="closeSuggestions"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm border-gray-300"
                :class="{'border-red-500': error}"
            />
            
            <!-- Indicador de carregamento -->
            <div v-if="isLoadingCities" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <!-- Botão para limpar a seleção -->
            <div v-else-if="searchTerm" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <button type="button" @click="clearSelection" class="text-gray-400 hover:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            
            <!-- Lista de sugestões -->
            <div v-if="showSuggestions" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg max-h-60 overflow-y-auto">
                <ul v-if="filteredCities.length > 0" class="py-1 divide-y divide-gray-200">
                    <li v-for="city in filteredCities" :key="city.id" @mousedown="selectCity(city)"
                        class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex justify-between items-center">
                        <span class="text-sm text-gray-900">{{ city.nome }}</span>
                        <span class="text-xs text-gray-500 font-medium">{{ city.uf }}</span>
                    </li>
                </ul>
                <div v-else-if="searchTerm.length >= 3" class="px-3 py-2 text-sm text-gray-500">
                    Nenhuma cidade encontrada
                </div>
                <div v-else class="px-3 py-2 text-sm text-gray-500">
                    Digite pelo menos 3 caracteres para buscar
                </div>
            </div>
        </div>
        
        <!-- Mensagem de erro -->
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<style scoped>
.ibge-city-selector {
    position: relative;
}
</style>