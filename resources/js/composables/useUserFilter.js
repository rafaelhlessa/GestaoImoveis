// composables/useUserFilter.js - VERSÃO CORRIGIDA
import { computed, ref } from 'vue'

export function useUserFilter(users, authorizations, currentUser) {
  const debugMode = ref(true)

  /**
   * USUÁRIOS DISPONÍVEIS BASEADO NO PERFIL - SEMPRE ATIVO
   */
  const availableUsers = computed(() => {
    console.log('🔍 availableUsers - Iniciando cálculo')

    if (!currentUser.value || !users.value) {
      console.log('❌ availableUsers - Dados faltando:', {
        hasCurrentUser: !!currentUser.value,
        hasUsers: !!users.value,
        usersLength: users.value?.length || 0
      })
      return []
    }

  const userProfiles = currentUser.value.profiles || []
    const userId = currentUser.value.id

    console.log('👤 availableUsers - Dados:', {
      userId,
      userProfile,
      userName: currentUser.value.name,
      totalUsers: users.value.length,
      totalAuthorizations: authorizations.value?.length || 0
    })

    switch (userProfile) {
      case 1: // Perfil Proprietário - apenas ele mesmo
        const ownerResult = users.value.filter(user => user.id === userId)
        console.log('🏠 Proprietário - Resultado:', ownerResult.length, 'usuários')
        return ownerResult

      case 2: // Perfil Prestador - apenas autorizados
        const prestadorResult = getAuthorizedUsers(userId)
        console.log('🔧 Prestador - Resultado:', prestadorResult.length, 'usuários')
        return prestadorResult

      case 3: // Perfil Misto - ele mesmo + autorizados
        const ownUser = users.value.filter(user => user.id === userId)
        const authorizedUsers = getAuthorizedUsers(userId)

        // Combina e remove duplicatas
        const combined = [...ownUser, ...authorizedUsers]
        const uniqueUsers = combined.filter((user, index, self) =>
          index === self.findIndex(u => u.id === user.id)
        )

        console.log('👥 Misto - Resultado:', {
          proprio: ownUser.length,
          autorizados: authorizedUsers.length,
          total: uniqueUsers.length
        })
        return uniqueUsers

      default:
        console.log('❌ Perfil inválido:', userProfile)
        return []
    }
  })

  /**
   * BUSCA USUÁRIOS AUTORIZADOS PARA UM PRESTADOR
   */
  const getAuthorizedUsers = (serviceProviderId) => {
    console.log('🔐 getAuthorizedUsers - Para prestador:', serviceProviderId)

    if (!authorizations.value || !Array.isArray(authorizations.value)) {
      console.log('❌ Sem autorizações válidas')
      return []
    }

    console.log('📋 Autorizações disponíveis:', authorizations.value.map(a => ({
      id: a.id,
      owner_id: a.owner_id,
      service_provider_id: a.service_provider_id,
      can_create_properties: a.can_create_properties
    })))

    // IDs dos proprietários que autorizaram
    const authorizedOwnerIds = authorizations.value
      .filter(auth => {
        const isForThisProvider = auth.service_provider_id === serviceProviderId
        const canCreate = auth.can_create_properties === true || auth.can_create_properties === 1
        const isValid = isForThisProvider && canCreate

        console.log(`   Auth ${auth.id}: provider=${auth.service_provider_id} (${isForThisProvider}), can_create=${auth.can_create_properties} (${canCreate}), válida=${isValid}`)
        return isValid
      })
      .map(auth => auth.owner_id)

    console.log('✅ IDs autorizados:', authorizedOwnerIds)

    // Busca os usuários correspondentes
    const authorizedUsers = users.value.filter(user => {
      const isAuthorized = authorizedOwnerIds.includes(user.id)
      console.log(`   User ${user.id} (${user.name}): ${isAuthorized ? '✅' : '❌'}`)
      return isAuthorized
    })

    console.log('🎯 Usuários autorizados encontrados:', authorizedUsers.length)
    return authorizedUsers
  }

  /**
   * BUSCA POR NOME NOS USUÁRIOS DISPONÍVEIS
   */
  const searchFilteredUsers = (searchTerm) => {
    console.log('🔍 searchFilteredUsers - Termo:', searchTerm)

    if (!searchTerm || searchTerm.length < 2) {
      console.log('❌ Termo muito curto')
      return []
    }

    const lowerTerm = searchTerm.toLowerCase()
    console.log('🔍 Buscando em', availableUsers.value.length, 'usuários disponíveis')

    const result = availableUsers.value.filter(user => {
      const name = user.name ? user.name.toLowerCase() : ''
      const match = name.includes(lowerTerm)
      console.log(`   ${user.name}: ${match ? '✅' : '❌'}`)
      return match
    })

    console.log('🎯 Encontrados:', result.length, 'usuários')
    return result
  }

  /**
   * VERIFICA SE PODE ADICIONAR PROPRIETÁRIOS - VERSÃO SIMPLES
   */
  const canAddOwners = computed(() => {
    console.log('🚦 canAddOwners - Verificando...')

    if (!currentUser.value) {
      console.log('❌ Sem usuário atual')
      return false
    }

  const profiles = currentUser.value.profiles || []
    const usersCount = availableUsers.value.length

    console.log('📊 Dados para verificação:', {
      profile,
      usersCount,
      availableUsers: availableUsers.value.map(u => ({ id: u.id, name: u.name }))
    })

    // REGRA SIMPLES: Se tem usuários disponíveis, pode adicionar
    const canAdd = usersCount > 0

    console.log('🎯 canAddOwners - RESULTADO:', canAdd, `(${usersCount} usuários disponíveis)`)
    return canAdd
  })

  /**
   * MENSAGEM EXPLICATIVA
   */
  const getUserFilterMessage = computed(() => {
    if (!currentUser.value) return ''

  const profiles = currentUser.value.profiles || []
    const count = availableUsers.value.length

    let message = ''
    switch (profile) {
      case 1:
        message = 'Como proprietário, você pode cadastrar propriedades para si mesmo.'
        break

      case 2:
        if (count === 0) {
          message = 'Nenhum proprietário autorizou você a criar propriedades.'
        } else {
          message = `Você pode criar propriedades para ${count} proprietário(s) que te autorizaram.`
        }
        break

      case 3:
        if (count <= 1) {
          message = 'Você pode criar propriedades para si mesmo.'
        } else {
          message = `Você pode criar propriedades para si mesmo e mais ${count - 1} proprietário(s).`
        }
        break

      default:
        message = 'Perfil não autorizado.'
    }

    console.log('💬 Mensagem:', message)
    return message
  })

  /**
   * COMPUTED PARA COMPATIBILIDADE (pode ser removido depois)
   */
  const filteredUsers = computed(() => {
    // Retorna sempre os usuários disponíveis para não quebrar o código existente
    return availableUsers.value
  })

  const debugInfo = computed(() => {
    return {
      currentUser: currentUser.value,
      totalUsers: users.value?.length || 0,
      availableUsers: availableUsers.value.length,
      canAddOwners: canAddOwners.value,
      message: getUserFilterMessage.value,
      authorizations: authorizations.value?.length || 0
    }
  })

  console.log('🏁 useUserFilter - Setup completo')

  return {
    // PRINCIPAIS
    availableUsers,        // ✨ NOVO: Lista sempre disponível
    canAddOwners,         // ✅ CORRIGIDO: Baseado em availableUsers
    getUserFilterMessage, // ✅ FUNCIONAL

    // COMPATIBILIDADE
    filteredUsers,        // 🔄 Para não quebrar código existente

    // MÉTODOS
    searchFilteredUsers,  // ✅ FUNCIONAL
    getAuthorizedUsers,   // ✨ NOVO: Método exposto

    // DEBUG
    debugMode,
    debugInfo
  }
}
