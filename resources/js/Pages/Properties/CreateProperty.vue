<template>
  <PropertyForm 
    mode="create"
    :authorizations="authorizations"
    :owners="owners"
    :current-user="currentUser"
    :type-owners="typeOwners"
    :users="users"
  />
</template>

<script setup>
import PropertyForm from '@/Components/Property/PropertyForm.vue'

defineProps({
  mode: {
    type: String,
    default: 'create'
  },
  typeOwners: Array,
  users: Array,
  authorizations: Array,
  owners: Array,
  currentUser: Object,
})
</script>