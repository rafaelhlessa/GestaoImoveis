// Components/Property/index.js
// Arquivo de índice para facilitar os imports

// Componente Principal
export { default as PropertyForm } from './PropertyForm.vue'

// Componentes de Formulário
export { default as PropertyFormFields } from './PropertyFormFields.vue'

// Componentes de Proprietários
export { default as OwnersTable } from './OwnersTable.vue'
export { default as OwnerModal } from './OwnerModal.vue'

// Componentes de Documentos
export { default as DocumentsTable } from './DocumentsTable.vue'
export { default as DocumentModal } from './DocumentModal.vue'
export { default as RequiredDocumentsInfo } from './RequiredDocumentsInfo.vue'

// Uso:
// import { PropertyForm, OwnersTable } from '@/Components/Property'