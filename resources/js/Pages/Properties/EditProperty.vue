<template>
  <PropertyForm 
    mode="edit"
    :property="property"
    :type-owners="typeOwners"
    :users="users"
    :owners="owners"
    :documents="documents"
    :current-user="currentUser"
  />
</template>

<script setup>
import PropertyForm from '@/Components/Property/PropertyForm.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentUser = computed(() => page.props.auth?.user || null)

defineProps({
  mode: {
    type: String,
    default: 'edit'
  },
  property: {
    type: Object,
    required: true
  },
  typeOwners: {
    type: Array,
    required: true
  },
  authorizations: {
    type: Array,
    required: true
  },
  users: {
    type: Array,
    required: true
  },
  owners: {
    type: Array,
    required: true
  },
  documents: {
    type: Array,
    required: true
  }
})
</script>