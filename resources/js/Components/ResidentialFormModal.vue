<template>
  <div class="residential-form space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Residenciais</h3>

      <!-- Localização -->
      <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">Localização</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Proximidade de serviços</label>
            <input v-model="localForm.proximidade_servicos" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Próximo a escolas, mercados" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Transporte</label>
            <input v-model="localForm.transporte" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Linhas de ônibus, metrô" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Segurança</label>
            <input v-model="localForm.seguranca" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Monitoramento, bairro tranquilo" />
          </div>
        </div>
      </div>
      
      <!-- Linha 1: Cômodos e Dormitórios -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="rooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Cômodos *
          </label>
          <input
            id="rooms"
            type="number"
            v-model.number="localForm.rooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.rooms }"
            placeholder="Ex: 8"
          >
          <p v-if="errors.rooms" class="text-red-500 text-xs mt-1">{{ errors.rooms }}</p>
        </div>

        <div>
          <label for="bedrooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Dormitórios *
          </label>
          <input
            id="bedrooms"
            type="number"
            v-model.number="localForm.bedrooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.bedrooms }"
            placeholder="Ex: 3"
          >
          <p v-if="errors.bedrooms" class="text-red-500 text-xs mt-1">{{ errors.bedrooms }}</p>
        </div>
      </div>

      <!-- Linha 2: Banheiros e Garagem -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="bathrooms" class="block text-sm font-medium text-gray-700 mb-2">
            Número de Banheiros *
          </label>
          <input
            id="bathrooms"
            type="number"
            v-model.number="localForm.bathrooms"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.bathrooms }"
            placeholder="Ex: 2"
          >
          <p v-if="errors.bathrooms" class="text-red-500 text-xs mt-1">{{ errors.bathrooms }}</p>
        </div>

        <div>
          <label for="garage_spaces" class="block text-sm font-medium text-gray-700 mb-2">
            Vagas de Garagem
          </label>
          <input
            id="garage_spaces"
            type="number"
            v-model.number="localForm.garage_spaces"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.garage_spaces }"
            placeholder="Ex: 2"
          >
          <p v-if="errors.garage_spaces" class="text-red-500 text-xs mt-1">{{ errors.garage_spaces }}</p>
        </div>
      </div>

      <!-- Terreno e Áreas -->
      <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">Terreno</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Topografia</label>
            <select v-model="localForm.terreno_topografia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Selecione...</option>
              <option value="plano">Plano</option>
              <option value="declive">Declive</option>
              <option value="aclive">Aclive</option>
              <option value="ondulado">Ondulado</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Posição</label>
            <select v-model="localForm.terreno_posicao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Selecione...</option>
              <option value="esquina">Esquina</option>
              <option value="meio_quadra">Meio de quadra</option>
              <option value="cul_de_sac">Cul-de-sac</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Linha 3: Áreas -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="built_area" class="block text-sm font-medium text-gray-700 mb-2">
            Área Construída (m²) *
          </label>
          <input
            id="built_area"
            type="number"
            step="0.01"
            v-model.number="localForm.built_area"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.built_area }"
            placeholder="Ex: 120.50"
          >
          <p v-if="errors.built_area" class="text-red-500 text-xs mt-1">{{ errors.built_area }}</p>
        </div>

        <div>
          <label for="total_area" class="block text-sm font-medium text-gray-700 mb-2">
            Área Total (m²) *
          </label>
          <input
            id="total_area"
            type="number"
            step="0.01"
            v-model.number="localForm.total_area"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.total_area }"
            placeholder="Ex: 250.00"
          >
          <p v-if="errors.total_area" class="text-red-500 text-xs mt-1">{{ errors.total_area }}</p>
        </div>
      </div>

      <!-- Construção / Condições e Mobília -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-700 mb-1">Padrão construtivo</label>
          <select v-model="localForm.construcao_padrao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Selecione...</option>
            <option value="alto">Alto padrão</option>
            <option value="medio">Médio padrão</option>
            <option value="basico">Básico</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Idade do imóvel (anos)</label>
          <input v-model.number="localForm.construcao_idade" min="0" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Reformas</label>
          <input v-model="localForm.estado_reformas" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Cozinha reformada em 2023" />
        </div>
      </div>

      <!-- Distribuição interna adicional -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-700 mb-1">Suítes</label>
          <input v-model.number="localForm.suites" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Salas</label>
          <input v-model.number="localForm.salas" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Cozinha</label>
          <select v-model="localForm.cozinha" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Selecione...</option>
            <option value="padrao">Padrão</option>
            <option value="planejada">Planejada</option>
            <option value="americana">Americana</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Área de serviço</label>
          <select v-model="localForm.area_servico" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Selecione...</option>
            <option value="sim">Sim</option>
            <option value="nao">Não</option>
          </select>
        </div>
      </div>

      <!-- Áreas externas -->
      <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">Áreas externas</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="localForm.external_quintal" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <span class="text-sm">Quintal</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="localForm.external_jardim" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <span class="text-sm">Jardim</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="localForm.external_piscina" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <span class="text-sm">Piscina</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="localForm.external_churrasqueira" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <span class="text-sm">Churrasqueira</span>
          </label>
        </div>
      </div>

      <!-- Instalações -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
        <div>
          <label class="block text-sm text-gray-700 mb-1">Instalações elétricas</label>
          <select v-model="localForm.estado_instalacoes_eletricas" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Selecione...</option>
            <option value="novas">Novas</option>
            <option value="boas">Boas</option>
            <option value="regulares">Regulares</option>
            <option value="precisam_reforma">Precisam de reforma</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Instalações hidráulicas</label>
          <select v-model="localForm.estado_instalacoes_hidraulicas" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Selecione...</option>
            <option value="novas">Novas</option>
            <option value="boas">Boas</option>
            <option value="regulares">Regulares</option>
            <option value="precisam_reforma">Precisam de reforma</option>
          </select>
        </div>
      </div>

      <!-- Linha 4: Condições e Mobília -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="property_condition" class="block text-sm font-medium text-gray-700 mb-2">
            Condições do Imóvel *
          </label>
          <select
            id="property_condition"
            v-model="localForm.property_condition"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.property_condition }"
          >
            <option value="">Selecione...</option>
            <option value="excelente">Excelente</option>
            <option value="bom">Bom</option>
            <option value="regular">Regular</option>
            <option value="ruim">Ruim</option>
            <option value="pessimo">Péssimo</option>
          </select>
          <p v-if="errors.property_condition" class="text-red-500 text-xs mt-1">{{ errors.property_condition }}</p>
        </div>

        <div>
          <label for="furniture_status" class="block text-sm font-medium text-gray-700 mb-2">
            Status da Mobília
          </label>
          <select
            id="furniture_status"
            v-model="localForm.furniture_status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.furniture_status }"
          >
            <option value="">Selecione...</option>
            <option value="mobiliado">Mobiliado</option>
            <option value="semi_mobiliado">Semi-mobiliado</option>
            <option value="nao_mobiliado">Não mobiliado</option>
          </select>
          <p v-if="errors.furniture_status" class="text-red-500 text-xs mt-1">{{ errors.furniture_status }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResidentialFormModal',
  
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

  computed: {
    localForm: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  }
}
</script>