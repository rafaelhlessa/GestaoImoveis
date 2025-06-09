// composables/useAuthorization.js - VERSÃO 2.0 SIMPLIFICADA
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Composable para gerenciar autorizações de usuários
 * @param {Array} users - Lista de todos os usuários
 * @param {Array} authorizations - Lista de autorizações 
 * @param {Object} currentUser - Usuário atual (opcional, usa da página se não fornecido)
 */
export function useAuthorization(users = [], authorizations = [], currentUser = null) {
  const page = usePage()
  
  // Usuário atual - da página se não fornecido
  const user = computed(() => 
    currentUser || page.props.auth?.user || null
  )

  /**
   * IDs dos proprietários que autorizaram o usuário atual
   */
  const authorizedOwnerIds = computed(() => {
    if (!user.value || !Array.isArray(authorizations)) {
      return []
    }

    return authorizations
      .filter(auth => 
        auth.service_provider_id === user.value.id &&
        (auth.can_create_properties === true || auth.can_create_properties === 1)
      )
      .map(auth => auth.owner_id)
  })

  /**
   * Objetos completos dos usuários que autorizaram
   */
  const authorizedUsers = computed(() => {
    if (!Array.isArray(users) || !authorizedOwnerIds.value.length) {
      return []
    }
    
    return users.filter(u => authorizedOwnerIds.value.includes(u.id))
  })

  /**
   * Usuários disponíveis baseado no perfil
   */
  const availableUsers = computed(() => {
    if (!user.value || !Array.isArray(users)) {
      console.log('❌ useAuthorization - Dados insuficientes')
      return []
    }

    const profile = user.value.profile_id

    switch (profile) {
      case 1: // Proprietário - apenas ele mesmo
        return users.filter(u => u.id === user.value.id)

      case 2: // Prestador - apenas autorizados
        return authorizedUsers.value

      case 3: // Proprietário/Prestador - ele mesmo + autorizados
        const selfUser = users.filter(u => u.id === user.value.id)
        const authorized = authorizedUsers.value
        
        // Remove duplicatas
        const combined = [...selfUser, ...authorized]
        return combined.filter((u, index, self) => 
          index === self.findIndex(user => user.id === u.id)
        )

      default:
        return []
    }
  })

  /**
   * Verifica se pode adicionar proprietários
   */
  const canAddOwners = computed(() => {
    return availableUsers.value.length > 0
  })

  /**
   * Verifica se o usuário pode visualizar propriedades de um proprietário específico
   * @param {number} ownerId - ID do proprietário
   */
  const canViewProperties = (ownerId) => {
    if (!user.value || !ownerId) return false
    
    // Próprio usuário sempre pode ver
    if (user.value.id === ownerId) return true
    
    // Prestador pode ver se foi autorizado
    return authorizedOwnerIds.value.includes(ownerId)
  }

  /**
   * Verifica se o usuário pode criar propriedades para um proprietário específico
   * @param {number} ownerId - ID do proprietário
   */
  const canCreatePropertiesFor = (ownerId) => {
    if (!user.value || !ownerId) return false
    
    const profile = user.value.profile_id
    
    // Proprietário pode criar apenas para si mesmo
    if (profile === 1) {
      return user.value.id === ownerId
    }
    
    // Prestador pode criar se foi autorizado
    if (profile === 2) {
      return authorizedOwnerIds.value.includes(ownerId)
    }
    
    // Proprietário/Prestador pode criar para si mesmo ou autorizados
    if (profile === 3) {
      return user.value.id === ownerId || authorizedOwnerIds.value.includes(ownerId)
    }
    
    return false
  }

  /**
   * Mensagem explicativa sobre disponibilidade
   */
  const filterMessage = computed(() => {
    if (!user.value) return ''

    const profile = user.value.profile_id
    const count = availableUsers.value.length

    switch (profile) {
      case 1:
        return 'Como proprietário, você pode cadastrar propriedades apenas para si mesmo.'
      
      case 2:
        return count === 0 
          ? 'Nenhum proprietário autorizou você a criar propriedades.'
          : `Você pode criar propriedades para ${count} proprietário(s) que te autorizaram.`
      
      case 3:
        return count <= 1
          ? 'Você pode criar propriedades para si mesmo.'
          : `Você pode criar propriedades para si mesmo e mais ${count - 1} proprietário(s).`
      
      default:
        return 'Perfil não autorizado a criar propriedades.'
    }
  })

  /**
   * Busca usuários disponíveis por nome
   * @param {string} searchTerm - Termo de busca
   */
  const searchUsers = (searchTerm) => {
    if (!searchTerm || searchTerm.length < 2) {
      return []
    }

    const lowerTerm = searchTerm.toLowerCase()
    return availableUsers.value
      .filter(user => user.name.toLowerCase().includes(lowerTerm))
      .slice(0, 10)
  }

  return {
    // Dados do usuário
    currentUser: user,
    
    // Listas de usuários
    authorizedOwnerIds,
    authorizedUsers,
    availableUsers,
    
    // Status
    canAddOwners,
    filterMessage,
    
    // Métodos de verificação
    canViewProperties,
    canCreatePropertiesFor,
    searchUsers
  }
}