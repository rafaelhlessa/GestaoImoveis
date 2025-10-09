<template>
  <div class="rural-form space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Rurais</h3>
      
      <!-- Área Total -->
      <div class="mb-4">
        <label for="rural_total_area" class="block text-sm font-medium text-gray-700 mb-2">
          Área Total (hectares) *
        </label>
        <input
          id="rural_total_area"
          type="number"
          step="0.01"
          v-model.number="localForm.rural_total_area"
          min="0"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.rural_total_area }"
          placeholder="Ex: 50.75"
        >
        <p v-if="errors.rural_total_area" class="text-red-500 text-xs mt-1">{{ errors.rural_total_area }}</p>
      </div>

      <!-- Existe Construção -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Possui Construção? *
        </label>
        <div class="flex space-x-4">
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="true" 
              v-model="localForm.has_construction" 
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            >
            <span class="ml-2 text-sm text-gray-700">Sim</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="false" 
              v-model="localForm.has_construction" 
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            >
            <span class="ml-2 text-sm text-gray-700">Não</span>
          </label>
        </div>
        <p v-if="errors.has_construction" class="text-red-500 text-xs mt-1">{{ errors.has_construction }}</p>
      </div>

      <!-- Tipos de Construção -->
      <div v-if="localForm.has_construction" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Tipos de Construções (marque todas que se aplicam)
        </label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <label v-for="type in constructionTypes" :key="type.value" class="flex items-center">
            <input 
              type="checkbox" 
              :value="type.value" 
              v-model="localForm.construction_types"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <span class="ml-2 text-sm text-gray-700">{{ type.label }}</span>
          </label>
        </div>
        <p v-if="errors.construction_types" class="text-red-500 text-xs mt-1">{{ errors.construction_types }}</p>
      </div>

      <!-- Possui Lavoura -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Possui Lavoura? *
        </label>
        <div class="flex space-x-4">
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="true" 
              v-model="localForm.has_farming" 
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            >
            <span class="ml-2 text-sm text-gray-700">Sim</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="false" 
              v-model="localForm.has_farming" 
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            >
            <span class="ml-2 text-sm text-gray-700">Não</span>
          </label>
        </div>
        <p v-if="errors.has_farming" class="text-red-500 text-xs mt-1">{{ errors.has_farming }}</p>
      </div>

      <!-- Tipos de Lavoura -->
      <div v-if="localForm.has_farming" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Tipos de Lavoura (marque todas que se aplicam)
        </label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <label v-for="type in farmingTypes" :key="type.value" class="flex items-center">
            <input 
              type="checkbox" 
              :value="type.value" 
              v-model="localForm.farming_types"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <span class="ml-2 text-sm text-gray-700">{{ type.label }}</span>
          </label>
        </div>
        <p v-if="errors.farming_types" class="text-red-500 text-xs mt-1">{{ errors.farming_types }}</p>
      </div>

      <!-- Fonte de Água -->
      <div class="mb-4">
        <label for="water_source" class="block text-sm font-medium text-gray-700 mb-2">
          Fonte de Água *
        </label>
        <select
          id="water_source"
          v-model="localForm.water_source"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.water_source }"
        >
          <option value="">Selecione...</option>
          <option value="poco_artesiano">Poço Artesiano</option>
          <option value="rio">Rio</option>
          <option value="nascente">Nascente</option>
          <option value="rede_publica">Rede Pública</option>
          <option value="cisterna">Cisterna</option>
          <option value="outros">Outros</option>
        </select>
        <p v-if="errors.water_source" class="text-red-500 text-xs mt-1">{{ errors.water_source }}</p>
      </div>

      <!-- Detalhes da Fonte de Água -->
      <div v-if="localForm.water_source" class="mb-4">
        <label for="water_source_details" class="block text-sm font-medium text-gray-700 mb-2">
          Detalhes da Fonte de Água
        </label>
        <textarea
          id="water_source_details"
          v-model="localForm.water_source_details"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.water_source_details }"
          placeholder="Descreva mais detalhes sobre a fonte de água..."
        ></textarea>
        <p v-if="errors.water_source_details" class="text-red-500 text-xs mt-1">{{ errors.water_source_details }}</p>
      </div>

      <!-- Rebanhos -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Possui Rebanho?
        </label>
        <div class="flex space-x-4 mb-3">
          <label class="flex items-center">
            <input type="radio" :value="true" v-model="localForm.has_herd" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
            <span class="ml-2 text-sm text-gray-700">Sim</span>
          </label>
          <label class="flex items-center">
            <input type="radio" :value="false" v-model="localForm.has_herd" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
            <span class="ml-2 text-sm text-gray-700">Não</span>
          </label>
        </div>

        <div v-if="localForm.has_herd" class="space-y-4">
          <div class="flex justify-between items-center">
            <h4 class="text-sm font-medium text-gray-900">Grupos de Rebanho</h4>
            <button type="button" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700" @click="addHerdGroup">Adicionar Grupo</button>
          </div>

          <div v-if="!Array.isArray(localForm.herds) || localForm.herds.length === 0" class="text-xs text-gray-500">Nenhum grupo adicionado.</div>

          <div v-for="(herd, hIndex) in localForm.herds" :key="hIndex" class="border rounded p-3 space-y-3">
            <div class="flex justify-between items-center">
              <div class="text-sm font-medium text-gray-700">Grupo {{ hIndex + 1 }}</div>
              <button type="button" class="text-red-600 text-xs hover:underline" @click="removeHerdGroup(hIndex)">Remover</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600 mb-1">Espécie</label>
                <select v-model="herd.especie" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                  <option value="">Selecione...</option>
                  <option value="bovino">Bovino</option>
                  <option value="suino">Suíno</option>
                  <option value="ovino">Ovino</option>
                  <option value="caprino">Caprino</option>
                  <option value="equino">Equino</option>
                  <option value="aves">Aves</option>
                  <option value="outros">Outros</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">Raça</label>
                <input v-model="herd.raca" type="text" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Ex: Nelore, Holandês" />
              </div>
            </div>

            <div class="flex justify-between items-center mt-2">
              <div class="text-sm font-medium text-gray-700">Faixas Etárias</div>
              <button type="button" class="text-xs px-2 py-1 bg-gray-800 text-white rounded hover:bg-black" @click="addHerdAge(hIndex)">Adicionar Faixa</button>
            </div>
            <div v-if="!Array.isArray(herd.faixas) || herd.faixas.length === 0" class="text-xs text-gray-500">Nenhuma faixa adicionada.</div>
            <div v-for="(faixa, fIndex) in (herd.faixas || [])" :key="fIndex" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
              <div>
                <label class="block text-xs text-gray-600 mb-1">Faixa Etária</label>
                <select v-model="faixa.faixa_etaria" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                  <option value="">Selecione...</option>
                  <option value="bezerro">0-12 meses (Bezerro/Cria)</option>
                  <option value="juvenil">12-24 meses (Juvenil)</option>
                  <option value="adulto">24-60 meses (Adulto)</option>
                  <option value="matriz_reprodutor">Matriz/Reprodutor</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">Qtd. Machos</label>
                <input v-model.number="faixa.quantidade_machos" min="0" type="number" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">Qtd. Fêmeas</label>
                <input v-model.number="faixa.quantidade_femeas" min="0" type="number" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
              </div>
              <div class="flex justify-end">
                <button type="button" class="text-red-600 text-xs hover:underline" @click="removeHerdAge(hIndex, fIndex)">Remover</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RuralFormModal',
  
  props: {
    modelValue: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },

  emits: ['update:modelValue'],

  data() {
    return {
      constructionTypes: [
        { value: 'casa_sede', label: 'Casa Sede' },
        { value: 'galpon', label: 'Galpão' },
        { value: 'estabulo', label: 'Estábulo' },
        { value: 'celeiro', label: 'Celeiro' },
        { value: 'pocilga', label: 'Pocilga' },
        { value: 'galinheiro', label: 'Galinheiro' },
        { value: 'outros', label: 'Outros' }
      ],
      farmingTypes: [
        { value: 'soja', label: 'Soja' },
        { value: 'milho', label: 'Milho' },
        { value: 'feijao', label: 'Feijão' },
        { value: 'arroz', label: 'Arroz' },
        { value: 'trigo', label: 'Trigo' },
        { value: 'cana_acucar', label: 'Cana de Açúcar' },
        { value: 'cafe', label: 'Café' },
        { value: 'pastagem', label: 'Pastagem' },
        { value: 'fruticultura', label: 'Fruticultura' },
        { value: 'outros', label: 'Outros' }
      ]
    }
  },

  computed: {
    localForm: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  },

  watch: {
    'localForm.has_construction'(newValue) {
      if (!newValue) {
        this.localForm.construction_types = []
      }
    },

    'localForm.has_farming'(newValue) {
      if (!newValue) {
        this.localForm.farming_types = []
      }
    },

    'localForm.has_herd'(newValue) {
      if (!newValue) {
        this.localForm.herds = []
      } else if (!Array.isArray(this.localForm.herds)) {
        this.localForm.herds = []
      }
    }
  },

  methods: {
    addHerdGroup() {
      if (!Array.isArray(this.localForm.herds)) {
        this.localForm.herds = []
      }
      this.localForm.herds.push({ especie: '', raca: '', faixas: [] })
    },
    removeHerdGroup(index) {
      if (Array.isArray(this.localForm.herds)) {
        this.localForm.herds.splice(index, 1)
      }
    },
    addHerdAge(hIndex) {
      if (!Array.isArray(this.localForm.herds)) return
      const herd = this.localForm.herds[hIndex]
      if (!herd) return
      if (!Array.isArray(herd.faixas)) herd.faixas = []
      herd.faixas.push({ faixa_etaria: '', quantidade_machos: null, quantidade_femeas: null })
    },
    removeHerdAge(hIndex, fIndex) {
      if (!Array.isArray(this.localForm.herds)) return
      const herd = this.localForm.herds[hIndex]
      if (!herd || !Array.isArray(herd.faixas)) return
      herd.faixas.splice(fIndex, 1)
    }
  }
}
</script>