<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Button from '@/Components/Button.vue'

const { props } = usePage()
const property = ref(props.property)
const evaluations = ref(props.evaluations)
const columns = [
  { label: 'Avaliador', field: 'user.name' },
  { label: 'Data', field: 'created_at', format: dt => new Date(dt).toLocaleDateString() },
  { label: 'Nota', field: 'score' },
  { label: 'Ações', field: 'actions' },
]

function destroy(id) {
  if (confirm('Excluir avaliação?')) {
    fetch(route('properties.evaluations.destroy', [property.id, id]), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => window.location.reload())
  }
}
</script>

<template>
    <PageLayout>
      <PageHeader :title="`Avaliações de ${property.name}`">
        <Link :href="route('properties.evaluations.create', property.id)" class="btn-primary">Nova Avaliação</Link>
      </PageHeader>

      <DataTable :data="evaluations" :columns="columns">
        <template #actions="{ row }">
          <Link :href="route('properties.evaluations.show', [property.id, row.id])" class="btn-sm">Ver</Link>
          <Link :href="route('properties.evaluations.edit', [property.id, row.id])" class="btn-sm">Editar</Link>
          <Button @click="destroy(row.id)" size="sm" variant="danger">Excluir</Button>
        </template>
      </DataTable>
    </PageLayout>
</template>

<style scoped>

</style>
