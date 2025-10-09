// composables/usePropertyForm.js - VERSÃO 2.0 COMPLETA E CONSISTENTE
import { reactive, ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

export function usePropertyForm(props = {}) {
  console.log('🚀 usePropertyForm v2.0 - Inicializando', props)

  // ====================================
  // DADOS DA PÁGINA E CONFIGURAÇÃO
  // ====================================
  const page = usePage()

  // Extrai dados dos props de forma consistente
  const initialData = props.property || null
  const mode = props.mode || 'create'
  const isEditMode = computed(() => mode === 'edit')

  // Dados do usuário atual sempre da página
  const currentUser = computed(() => page.props.auth?.user || page.props.currentUser || null)


  // Dados de entrada
  const allUsers = ref(props.users || [])
  const allAuthorizations = computed(() => props.authorizations || [])
  const typeOwners = ref(props.typeOwners || [])

  console.log('📊 Dados iniciais:', {
    mode,
    hasInitialData: !!initialData,
    currentUser: currentUser.value,
    usersCount: allUsers.value.length,
    authorizationsCount: allAuthorizations.value.length,
    typeOwnersCount: typeOwners.value.length
  })

  // ====================================
  // FORMULÁRIO PRINCIPAL
  // ====================================
  const form = useForm({
    is_active: initialData?.is_active ?? true,
    type_property: initialData?.type_property || null,
    title_deed: initialData?.title_deed || '',
    title_deed_number: initialData?.title_deed_number || '',
    other: initialData?.other || '',
    area: initialData?.area || '',
    unit: initialData?.unit || '',
    address: initialData?.address || '',
    city: initialData?.city || '',
    city_id: initialData?.city_id || null,
    district: initialData?.district || '',
    locality: initialData?.locality || '',
    nickname: initialData?.nickname || '',
    about: initialData?.about || '',
    file_photo: isEditMode.value ? (initialData?.file_photo || null) : null,
    owners: [],
    documents: []
  })

  // ====================================
  // ESTADO DOS PROPRIETÁRIOS
  // ====================================
  const owners = ref(props.owners || initialData?.owners || [])
  const selectedOwner = reactive({
    id: null,
    name: '',
    cpf_cnpj: '',
    percent: '',
    type_ownership: '',
    observations: ''
  })

  // Estado da busca de proprietários
  const searchTerm = ref('')
  const filteredUsers = ref([])

  // ====================================
  // ESTADO DOS DOCUMENTOS
  // ====================================
  const documents = ref(props.documents || initialData?.documents || [])
  const documentsToDelete = ref([]) // IDs dos documentos a serem deletados
  const newDocument = reactive({
    name: '',
    date: '',
    show: true,
    file: null,
    file_name: ''
  })
  const docDate = ref(false)

  // ====================================
  // ESTADO DOS MODAIS
  // ====================================
  const showModalOwner = ref(false)
  const showModalCoOwner = ref(false)
  const showModalDocument = ref(false)

  // ====================================
  // ESTADO DAS CIDADES
  // ====================================
  const allCities = ref([])
  const filteredCities = ref([])
  const showSuggestions = ref(false)
  const isLoadingCities = ref(false)

  // ====================================
  // SISTEMA DE ALERTAS
  // ====================================
  const alert = reactive({
    show: false,
    message: '',
    type: 'info',
    color: 'gray'
  })

  const alertColors = {
    success: 'bg-green-100 text-green-800 border-green-200',
    error: 'bg-red-100 text-red-800 border-red-200',
    warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    info: 'bg-blue-100 text-blue-800 border-blue-200'
  }

  const alertClass = computed(() => alertColors[alert.type] || alertColors.info)

  const showAlert = (message, type = 'info', duration = 3000) => {
    alert.message = message
    alert.type = type
    alert.show = true

    if (duration > 0) {
      setTimeout(() => {
        alert.show = false
      }, duration)
    }
  }

  // ====================================
  // SISTEMA DE AUTORIZAÇÃO DE USUÁRIOS
  // ====================================

  /**
   * Usuários disponíveis baseado no perfil do usuário atual
   */
  const availableUsers = computed(() => {
    console.log(page.props.auth?.user)
    if (!currentUser.value || !allUsers.value.length) {
      console.log('❌ availableUsers - Dados insuficientes')
      return []
    }

  const profiles = currentUser.value?.profiles || []
    const userId = currentUser.value?.id

    console.log('👤 availableUsers - Perfis:', profiles, 'UserId:', userId)

    // Proprietário puro - apenas ele mesmo
    if (profiles.includes('proprietario') && !profiles.includes('prestador')) {
        const ownerResult = allUsers.value.filter(user => user.id === userId)
        console.log('🏠 Proprietário - Usuários disponíveis:', ownerResult.length)
        return ownerResult
    }

    // Prestador puro - apenas autorizados
    if (profiles.includes('prestador') && !profiles.includes('proprietario')) {
        const authorizedResult = getAuthorizedUsersForProvider(userId)
        console.log('🔧 Prestador - Usuários disponíveis:', authorizedResult.length)
        return authorizedResult
    }

    // Proprietário/Prestador - ele mesmo + autorizados
    if (profiles.includes('proprietario') && profiles.includes('prestador')) {
        const selfUser = allUsers.value.filter(user => user.id === userId)
        const authorizedUsers = getAuthorizedUsersForProvider(userId)

        // Remove duplicatas
        const combined = [...selfUser, ...authorizedUsers]
        const unique = combined.filter((user, index, self) =>
          index === self.findIndex(u => u.id === user.id)
        )

        console.log('👥 Misto - Usuários disponíveis:', unique.length)
        return unique
    }

    // Caso não tenha perfis válidos
    console.log('❌ Perfis inválidos:', profiles)
    return []
  })

  /**
   * Busca usuários autorizados para um prestador de serviço
   */
  // const getAuthorizedUsersForProvider = (serviceProviderId) => {
  //   if (!allAuthorizations.value.length) {
  //     console.log('❌ Sem autorizações disponíveis')
  //     return []
  //   }

  //   // Filtra autorizações válidas para este prestador
  //   const validAuthorizations = allAuthorizations.value.filter(auth =>
  //     auth.service_provider_id === serviceProviderId &&
  //     (auth.can_create_properties === true || auth.can_create_properties === 1)
  //   )

  //   console.log('🔐 Autorizações válidas:', validAuthorizations.length)

  //   // Extrai IDs dos proprietários autorizados
  //   const authorizedOwnerIds = validAuthorizations.map(auth => auth.owner_id)

  //   // Busca os objetos de usuário correspondentes
  //   const authorizedUsers = allUsers.value.filter(user =>
  //     authorizedOwnerIds.includes(user.id)
  //   )

  //   console.log('✅ Usuários autorizados encontrados:', authorizedUsers.length)
  //   return authorizedUsers
  // }

// const getAuthorizedUsersForProvider = (serviceProviderId) => {
//     // Sempre usar props diretamente para garantir dados atuais
//     const authorizations = props.authorizations || []
//     const users = props.users || []

//     console.log('🚀 getAuthorizedUsersForProvider')
//     console.log('📋 Service Provider ID:', serviceProviderId)
//     console.log('📊 Authorizations:', authorizations.length)
//     console.log('📊 Users:', users.length)

//     if (!authorizations.length) {
//         console.log('❌ Sem autorizações disponíveis')
//         return []
//     }

//     const providerIdNum = Number(serviceProviderId)
//     const validAuthorizations = authorizations.filter(auth =>
//         Number(auth.service_provider_id) === providerIdNum &&
//         Number(auth.can_create_properties) === 1
//     )

//     console.log('🔐 Autorizações válidas:', validAuthorizations.length)

//     const authorizedOwnerIds = validAuthorizations.map(auth => Number(auth.owner_id))
//     const authorizedUsers = users.filter(user =>
//         authorizedOwnerIds.includes(Number(user.id))
//     )

//     console.log('✅ Usuários autorizados encontrados:', authorizedUsers.length)
//     return authorizedUsers
// }

const getAuthorizedUsersForProvider = (serviceProviderId) => {
    // Se já tem users disponíveis e é prestador, use-os diretamente
    const users = props.users || []

  if (props.currentUser && props.currentUser.profiles && props.currentUser.profiles.includes('prestador') && users.length > 0) {
        // O backend já filtrou os usuários autorizados
        return users.filter(user => user.id !== serviceProviderId)
    }

    return []
}

// Função alternativa mais simples para teste
const getAuthorizedUsersForProviderSimple = (serviceProviderId) => {
  console.log('🧪 TESTE SIMPLES - Service Provider ID:', serviceProviderId)

  // Converte para número para garantir
  const providerIdNum = Number(serviceProviderId)

  const validAuths = allAuthorizations.value.filter(auth =>
    Number(auth.service_provider_id) === providerIdNum &&
    (Number(auth.can_create_properties) === 1)
  )

  console.log('🧪 TESTE SIMPLES - Autorizações válidas:', validAuths)

  const ownerIds = validAuths.map(auth => Number(auth.owner_id))
  console.log('🧪 TESTE SIMPLES - Owner IDs:', ownerIds)

  const users = allUsers.value.filter(user => ownerIds.includes(Number(user.id)))
  console.log('🧪 TESTE SIMPLES - Usuários encontrados:', users)

  return users
}

// Função para verificar estrutura dos dados
const debugDataStructure = () => {
  console.log('🔬 ESTRUTURA DOS DADOS:')
  console.log('📋 allAuthorizations.value:', allAuthorizations.value)
  console.log('👥 allUsers.value:', allUsers.value)

  if (allAuthorizations.value.length > 0) {
    console.log('📋 Primeira autorização:', allAuthorizations.value[0])
    console.log('📋 Chaves da primeira autorização:', Object.keys(allAuthorizations.value[0]))
  }

  if (allUsers.value.length > 0) {
    console.log('👤 Primeiro usuário:', allUsers.value[0])
    console.log('👤 Chaves do primeiro usuário:', Object.keys(allUsers.value[0]))
  }
}

  /**
   * Verifica se pode adicionar proprietários
   */
  const canAddOwners = computed(() => {
    const canAdd = availableUsers.value.length > 0
    console.log('🚦 canAddOwners:', canAdd, `(${availableUsers.value.length} usuários disponíveis)`)
    return canAdd
  })

  /**
   * Mensagem explicativa sobre disponibilidade de usuários
   */
  const getUserFilterMessage = computed(() => {
    if (!currentUser.value) return ''

    const profiles = currentUser.value.profiles || []
    const count = availableUsers.value.length

    // Proprietário puro
    if (profiles.includes('proprietario') && !profiles.includes('prestador')) {
        return 'Como proprietário, você pode cadastrar propriedades apenas para si mesmo.'
    }

    // Prestador puro
    if (profiles.includes('prestador') && !profiles.includes('proprietario')) {
        return count === 0
          ? 'Nenhum proprietário autorizou você a criar propriedades.'
          : `Você pode criar propriedades para ${count} proprietário(s) que te autorizaram.`
    }

    // Proprietário/Prestador
    if (profiles.includes('proprietario') && profiles.includes('prestador')) {
        return count <= 1
          ? 'Você pode criar propriedades para si mesmo.'
          : `Você pode criar propriedades para si mesmo e mais ${count - 1} proprietário(s).`
    }

    return 'Perfil não autorizado a criar propriedades.'
  })

  // ====================================
  // MÉTODOS DE BUSCA DE USUÁRIOS
  // ====================================

  /**
   * Busca usuários por nome
   */
  const searchUsers = (term) => {
    console.log('🔍 searchUsers:', term)

    if (!term || term.length < 2) {
      filteredUsers.value = []
      return
    }

    const lowerTerm = term.toLowerCase()
    filteredUsers.value = availableUsers.value.filter(user =>
      user.name.toLowerCase().includes(lowerTerm)
    ).slice(0, 10) // Limita a 10 resultados

    console.log('🎯 Usuários encontrados:', filteredUsers.value.length)
  }

  // ====================================
  // MÉTODOS DE PROPRIETÁRIOS
  // ====================================

  /**
   * Seleciona um proprietário
   */
  const selectOwner = (user) => {
    console.log('👤 selectOwner:', user.name)

    Object.assign(selectedOwner, {
      id: user.id,
      name: user.name,
      cpf_cnpj: user.cpf_cnpj,
      percent: selectedOwner.percent || '',
      type_ownership: selectedOwner.type_ownership || '',
      observations: selectedOwner.observations || ''
    })

    // Limpa busca
    searchTerm.value = ''
    filteredUsers.value = []
  }

  /**
   * Limpa seleção de proprietário
   */
  const clearOwner = () => {
    console.log('🗑️ clearOwner')

    Object.assign(selectedOwner, {
      id: null,
      name: '',
      cpf_cnpj: '',
      percent: '',
      type_ownership: '',
      observations: ''
    })

    searchTerm.value = ''
    filteredUsers.value = []
  }

  /**
   * Atualiza campo do proprietário selecionado
   */
  const updateSelectedOwner = (field, value) => {
    selectedOwner[field] = value
  }

  /**
   * Adiciona proprietário à lista
   */
  const addOwner = () => {
    console.log('➕ addOwner')

    // Validações
    if (!selectedOwner.id) {
      showAlert('Selecione um usuário', 'error')
      return false
    }

    if (!selectedOwner.percent || selectedOwner.percent <= 0) {
      showAlert('Informe um percentual válido', 'error')
      return false
    }

    if (!selectedOwner.type_ownership) {
      showAlert('Selecione o tipo de propriedade', 'error')
      return false
    }

    const percent = parseFloat(selectedOwner.percent)
    if (percent > 100) {
      showAlert('Percentual não pode ser maior que 100%', 'error')
      return false
    }

    // Verifica duplicação
    const exists = owners.value.find(owner =>
      (owner.user?.id || owner.user_id) === selectedOwner.id
    )

    if (exists) {
      showAlert('Este usuário já foi adicionado', 'warning')
      return false
    }

    // Validação específica para tipo "Proprietário" (id: 1)
    const typeId = parseInt(selectedOwner.type_ownership)
    if (typeId === 1) {
      const currentProprietarioTotal = owners.value
        .filter(owner => (owner.type_ownership?.id || owner.type_ownership_id) === 1)
        .reduce((sum, owner) => sum + parseFloat(owner.percentage || owner.percent || 0), 0)

      if (currentProprietarioTotal + percent > 100) {
        showAlert(`Percentual de proprietários excederia 100%. Atual: ${currentProprietarioTotal.toFixed(2)}%`, 'error')
        return false
      }
    }

    // Busca dados do tipo de propriedade
    const typeOwnership = typeOwners.value.find(type => type.id === typeId)
    if (!typeOwnership) {
      showAlert('Tipo de propriedade inválido', 'error')
      return false
    }

    // Cria novo proprietário
    const newOwner = {
      user_id: selectedOwner.id,
      user: {
        id: selectedOwner.id,
        name: selectedOwner.name,
        cpf_cnpj: selectedOwner.cpf_cnpj
      },
      percentage: percent,
      percent: percent, // Compatibilidade
      type_ownership_id: typeId,
      type_ownership: {
        id: typeOwnership.id,
        name: typeOwnership.name
      },
      observations: selectedOwner.observations || null
    }

    owners.value.push(newOwner)
    form.owners = owners.value

    clearOwner()
    showAlert('Proprietário adicionado com sucesso', 'success')
    return true
  }

  /**
   * Remove proprietário da lista
   */
  const removeOwner = (index) => {
    if (index >= 0 && index < owners.value.length) {
      const removed = owners.value[index]
      owners.value.splice(index, 1)
      form.owners = owners.value

      showAlert(`${removed.user?.name || removed.name} removido`, 'info')
    }
  }

  // ====================================
  // TIPOS DE PROPRIEDADE DISPONÍVEIS
  // ====================================

  const availableOwnershipTypes = computed(() => {
    return typeOwners.value.map(type => {
      if (type.id === 1) { // Tipo "Proprietário"
        const currentTotal = owners.value
          .filter(owner => (owner.type_ownership?.id || owner.type_ownership_id) === 1)
          .reduce((sum, owner) => sum + parseFloat(owner.percentage || owner.percent || 0), 0)

        return {
          ...type,
          remainingPercent: 100 - currentTotal,
          disabled: currentTotal >= 100
        }
      }
      return { ...type, disabled: false }
    })
  })

  // ====================================
  // MÉTODOS DE DOCUMENTOS
  // ====================================

  const convertToBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => {
        try {
          const result = reader.result
          if (typeof result !== 'string') return reject(new Error('Leitura de arquivo inválida'))
          // Garantir prefixo data: presente
          const hasPrefix = result.startsWith('data:')
          const output = hasPrefix ? result : `data:${file.type || 'application/octet-stream'};base64,${result}`
          resolve(output)
        } catch (e) {
          reject(e)
        }
      }
      reader.onerror = reject
      reader.readAsDataURL(file)
    })
  }

  const handleDocumentUpload = async (event) => {
    const file = event.target?.files?.[0]

    if (!file) {
      // Limpa os dados se não há arquivo
      newDocument.file = null
      newDocument.file_name = ''
      return
    }

    const allowedTypes = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.google-earth.kml+xml',
      'application/vnd.google-earth.kmz'
    ]
    const allowedExts = ['pdf', 'doc', 'docx', 'kml', 'kmz']
    const ext = (file.name.split('.').pop() || '').toLowerCase()

    const isAllowed = allowedTypes.includes(file.type) || allowedExts.includes(ext)
    if (!isAllowed) {
      showAlert('Tipo de arquivo não permitido', 'error')
      event.target.value = ''
      newDocument.file = null
      newDocument.file_name = ''
      return
    }

    if (file.size > 6 * 1024 * 1024) { // 6MB
      showAlert('Arquivo muito grande (máx: 6MB)', 'error')
      event.target.value = ''
      newDocument.file = null
      newDocument.file_name = ''
      return
    }

    try {
      console.log('🔄 Processando arquivo:', file.name)
  const base64 = await convertToBase64(file)
  newDocument.file = base64
      newDocument.file_name = file.name
  // Log auxiliar para depurar estado
  console.log('📦 Base64 prefix preview:', base64.substring(0, 30))
      console.log('✅ Arquivo processado com sucesso:', file.name)
      console.log('📦 newDocument atualizado:', {
        file: base64.substring(0, 50) + '...',
        file_name: file.name
      })
    } catch (error) {
      console.error('❌ Erro ao processar arquivo:', error)
      showAlert('Erro ao processar arquivo', 'error')
      newDocument.file = null
      newDocument.file_name = ''
    }
  }

  const handleAddDocument = (documentData) => {
    if (!documentData.name?.trim()) {
      showAlert('Nome do documento é obrigatório', 'error')
      return false
    }

    if (!documentData.file) {
      showAlert('Arquivo é obrigatório', 'error')
      return false
    }

    // Verifica duplicatas
    const exists = documents.value.find(doc =>
      doc.name.toLowerCase() === documentData.name.toLowerCase()
    )

    if (exists) {
      showAlert('Já existe um documento com este nome', 'warning')
      return false
    }

    const doc = {
      id: Date.now(), // ID temporário
      name: documentData.name,
      date: documentData.date || null,
      show: documentData.show ?? true,
      file: documentData.file,
      file_name: documentData.file_name
    }

    documents.value.push(doc)
    form.documents = documents.value

    // Reset
    Object.assign(newDocument, {
      name: '',
      date: '',
      show: true,
      file: null,
      file_name: ''
    })

    showAlert('Documento adicionado com sucesso', 'success')
    return true
  }

  const removeDocument = (index) => {
    if (index >= 0 && index < documents.value.length) {
      const removed = documents.value[index]

      // Se o documento tem ID (é existente no banco), adiciona à lista de exclusão
      if (removed.id) {
        documentsToDelete.value.push(removed.id)
      }

      documents.value.splice(index, 1)
      form.documents = documents.value

      showAlert(`Documento "${removed.name}" removido`, 'info')
    }
  }

  // ====================================
  // MÉTODOS DE CIDADES
  // ====================================

  const loadCities = async () => {
    if (allCities.value.length > 0) return // Já carregadas

    try {
      isLoadingCities.value = true
      const response = await axios.get(
        'https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome'
      )

      allCities.value = response.data
        .filter(city => city.microrregiao?.mesorregiao?.UF)
        .map(city => ({
          id: city.id,
          nome: `${city.nome} / ${city.microrregiao.mesorregiao.UF.sigla}`
        }))

      console.log('🏙️ Cidades carregadas:', allCities.value.length)
    } catch (error) {
      console.error('Erro ao carregar cidades:', error)
      showAlert('Erro ao carregar cidades', 'error')
    } finally {
      isLoadingCities.value = false
    }
  }

  const filterCities = (query) => {
    if (!query || query.length < 3) {
      filteredCities.value = []
      showSuggestions.value = false
      return
    }

    const lowerQuery = query.toLowerCase()
    filteredCities.value = allCities.value
      .filter(city => city.nome.toLowerCase().includes(lowerQuery))
      .slice(0, 15)

    showSuggestions.value = filteredCities.value.length > 0
  }

  const closeSuggestions = () => {
    setTimeout(() => {
      showSuggestions.value = false
    }, 200)
  }

  // ====================================
  // UPLOAD DE FOTO
  // ====================================

  const handleFileChange = async (event) => {
    const file = event.target?.files?.[0]
    if (!file) {
      // ✅ CORREÇÃO: Se não há arquivo, não limpa a foto atual em modo de edição
      if (!isEditMode.value) {
        form.file_photo = null
      }
      return
    }

    if (!file.type.startsWith('image/')) {
      showAlert('Apenas imagens são permitidas', 'error')
      event.target.value = ''
      return
    }

    if (file.size > 5 * 1024 * 1024) { // 5MB
      showAlert('Imagem muito grande (máx: 5MB)', 'error')
      event.target.value = ''
      return
    }

    try {
      form.file_photo = await convertToBase64(file)
      showAlert('Foto carregada com sucesso', 'success', 2000)
    } catch (error) {
      console.error('Erro ao processar imagem:', error)
      showAlert('Erro ao processar imagem', 'error')
    }
  }

  const removeCurrentPhoto = () => {
    form.file_photo = null
    showAlert('Foto removida', 'info', 2000)
  }

  const cancelNewPhoto = () => {
    if (isEditMode.value && initialData?.file_photo) {
      form.file_photo = initialData.file_photo
    } else {
      form.file_photo = null
    }
    showAlert('Nova foto cancelada', 'info', 2000)
  }

  // ====================================
  // SUBMISSÃO DO FORMULÁRIO
  // ====================================

  const validateForm = () => {
    const errors = []

    if (!form.type_property) {
      errors.push('Tipo de propriedade é obrigatório')
    }

    if (!form.title_deed) {
      errors.push('Título de propriedade é obrigatório')
    }

    if (!form.title_deed_number?.trim()) {
      errors.push('Número/descrição do título é obrigatório')
    }

    if (owners.value.length === 0) {
      errors.push('Adicione pelo menos um proprietário')
    }

    // Validação específica para proprietários
    const proprietarios = owners.value.filter(owner =>
      (owner.type_ownership?.id || owner.type_ownership_id) === 1
    )

    if (proprietarios.length > 0) {
      const totalPercent = proprietarios.reduce((sum, owner) =>
        sum + parseFloat(owner.percentage || owner.percent || 0), 0
      )

      if (Math.abs(totalPercent - 100) > 0.01) {
        errors.push(`Proprietários devem somar 100%. Atual: ${totalPercent.toFixed(2)}%`)
      }
    }

    if (errors.length > 0) {
      showAlert(errors[0], 'error', 5000)
      return false
    }

    return true
  }

  const submitForm = (propertyId = null) => {
    console.log('📤 submitForm')

    if (!validateForm()) return

    // Prepara dados dos proprietários (inclui nome/cpf_cnpj para co-proprietários)
    const formattedOwners = owners.value.map(owner => {
      const userId = owner.user_id || owner.user?.id || null
      const typeOwnershipId = owner.type_ownership_id || owner.type_ownership?.id || null
      const rawPercent = owner.percentage ?? owner.percent
      const percentage = rawPercent !== undefined && rawPercent !== null ? parseFloat(rawPercent) : null
      const isCoOwner = !userId

      const base = {
        id: isEditMode.value ? (owner.id || null) : null,
        user_id: userId,
        type_ownership_id: typeOwnershipId,
        percentage,
        observations: owner.observations || null
      }

      if (isCoOwner) {
        return {
          ...base,
          name: owner.name || owner.user?.name || '',
          cpf_cnpj: owner.cpf_cnpj || owner.user?.cpf_cnpj || ''
        }
      }

      return base
    })

    // Prepara dados dos documentos - apenas documentos com file (novos documentos)
    const formattedDocuments = documents.value
      .filter(doc => doc.file && doc.file.trim() !== '') // Só inclui documentos com file preenchido
      .map(doc => ({
        name: doc.name,
        date: doc.date,
        show: doc.show,
        file: doc.file,
        file_name: doc.file_name
      }))

  // Determina proprietário principal (apenas usuários cadastrados)
  const registeredOwners = formattedOwners.filter(o => !!o.user_id)
  const mainOwner = registeredOwners.find(o => o.percentage === 100) || registeredOwners[0] || null

    // Atualiza formulário
    form.owners = formattedOwners
  form.owner_id = mainOwner?.user_id || null

    // ✅ CORREÇÃO: Só inclui documents se houver documentos com file
    if (formattedDocuments.length > 0) {
      form.documents = formattedDocuments
    } else {
      // Remove documents do formulário para não enviar array vazio
      delete form.documents
    }

    // ✅ CORREÇÃO PRINCIPAL: Para edição, só envia file_photo/documents se foram alterados
    if (isEditMode.value && form.file_photo === initialData?.file_photo) {
      // Remove file_photo do envio para manter a foto atual
      const formData = { ...form.data() }
      delete formData.file_photo

      // Se não há novos documentos, também remove documents do envio
      if (!formData.documents || formData.documents.length === 0) {
        delete formData.documents
      }

      // ✅ ADICIONAR: Incluir documentos para deletar
      if (documentsToDelete.value.length > 0) {
        formData.documents_to_delete = documentsToDelete.value
      }

      console.log('📋 Dados preparados (mantendo foto atual e sem novos documentos)')

      // Submete sem file_photo/documents desnecessários usando transform
      form.transform(() => formData)[propertyId ? 'put' : 'post'](
        propertyId ? `/property/${propertyId}` : '/property',
        {
          onSuccess: () => {
            showAlert(
              propertyId ? 'Propriedade atualizada!' : 'Propriedade criada!',
              'success'
            )
          },
          onError: (errors) => {
            console.error('Erro na submissão:', errors)
            const firstError = Object.values(errors)[0]
            showAlert(firstError || 'Erro ao salvar propriedade', 'error')
          }
        }
      )
    } else {
      console.log('📋 Dados preparados com nova foto ou criação')

      // Para submissão normal, também verificar se há documents vazios
      if (!form.documents || form.documents.length === 0) {
        const formData = { ...form.data() }
        delete formData.documents

        // ✅ ADICIONAR: Incluir documentos para deletar
        if (documentsToDelete.value.length > 0) {
          formData.documents_to_delete = documentsToDelete.value
        }

        // Submete usando transform para remover documents vazios
        form.transform(() => formData)[propertyId ? 'put' : 'post'](
          propertyId ? `/property/${propertyId}` : '/property',
          {
            onSuccess: () => {
              showAlert(
                propertyId ? 'Propriedade atualizada!' : 'Propriedade criada!',
                'success'
              )
            },
            onError: (errors) => {
              console.error('Erro na submissão:', errors)
              const firstError = Object.values(errors)[0]
              showAlert(firstError || 'Erro ao salvar propriedade', 'error')
            }
          }
        )
      } else {
        // ✅ ADICIONAR: Incluir documentos para deletar no formulário
        if (documentsToDelete.value.length > 0) {
          form.documents_to_delete = documentsToDelete.value
        }

        // Submete normalmente com documents
        const url = propertyId ? `/property/${propertyId}` : '/property'
        const method = propertyId ? 'put' : 'post'

        form[method](url, {
          onSuccess: () => {
            showAlert(
              propertyId ? 'Propriedade atualizada!' : 'Propriedade criada!',
              'success'
            )
          },
          onError: (errors) => {
            console.error('Erro na submissão:', errors)
            const firstError = Object.values(errors)[0]
            showAlert(firstError || 'Erro ao salvar propriedade', 'error')
          }
        })
      }
    }
  }

  // ====================================
  // UTILITÁRIOS
  // ====================================

  const applyCpfCnpjMask = (value) => {
    if (!value) return ''
    const numbers = value.replace(/\D/g, '')

    if (numbers.length <= 11) {
      return numbers
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    } else {
      return numbers
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
    }
  }

  // ====================================
  // INICIALIZAÇÃO
  // ====================================

  onMounted(() => {
    console.log('🔧 usePropertyForm montado')

    if (isEditMode.value && initialData?.file_photo && !form.file_photo) {
      form.file_photo = initialData.file_photo
      console.log('📸 Foto atual carregada para edição')
    }

    // Disponibiliza dados globalmente
    window.typeOwners = typeOwners.value
    window.usersData = allUsers.value

    // Carrega cidades
    loadCities()

    // Auto-seleciona usuário para perfil proprietário
  if (currentUser.value?.profiles && currentUser.value.profiles.includes('proprietario') && !currentUser.value.profiles.includes('prestador') && !selectedOwner.id && availableUsers.value.length === 1) {
      console.log('🏠 Auto-selecionando proprietário')
      selectOwner(availableUsers.value[0])
    }
  })

  // ====================================
  // WATCHERS
  // ====================================

  watch(() => form.city, (newCity) => {
    if (newCity) filterCities(newCity)
  })

  // ====================================
  // RETORNO PÚBLICO
  // ====================================

  return {
    // Estado principal
    form,
    isEditMode,
    currentUser,

    // Proprietários
    owners,
    selectedOwner,
    searchTerm,
    filteredUsers,
    availableUsers,
    canAddOwners,
    getUserFilterMessage,
    availableOwnershipTypes,

    // Documentos
    documents,
    documentsToDelete,
    newDocument,
    docDate,

    // Modais
    showModalOwner,
    showModalCoOwner,
    showModalDocument,

    // Cidades
    allCities,
    filteredCities,
    showSuggestions,
    isLoadingCities,

    // Alertas
    alert,
    alertClass,

    // Métodos - Proprietários
    searchUsers,
    selectOwner,
    clearOwner,
    updateSelectedOwner,
    addOwner,
    removeOwner,

    // Métodos - Documentos
    handleDocumentUpload,
    handleAddDocument,
    removeDocument,

    // Métodos - Cidades
    loadCities,
    filterCities,
    closeSuggestions,

    // Métodos - Arquivo
    handleFileChange,
    removeCurrentPhoto,
    cancelNewPhoto,

    // Métodos - Formulário
    submitForm,
    showAlert,

    // Utilitários
    applyCpfCnpjMask
  }
}
