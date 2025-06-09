// composables/useCurrentUser.js
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useCurrentUser() {
  const page = usePage()
  return computed(() => page.props.auth?.user || null)
}
