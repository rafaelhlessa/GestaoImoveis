<script setup>
import { ref } from 'vue'
import { usePage, Link } from '@inertiajs/inertia-vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Button from '@/Components/Button.vue'

const { props } = usePage()
const plans = ref(props.plans)
const columns = [
  { label: 'Nome', field: 'name' },
  { label: 'Slug', field: 'slug' },
  { label: 'Mensal (R$)', field: 'price_monthly', format: v=>v.toFixed(2) },
  { label: 'Anual (R$)', field: 'price_yearly', format: v=>v? v.toFixed(2):'—' },
  { label: 'Ações', field: 'actions' },
]

function destroy(id) {
  if(confirm('Excluir plano?')){
    fetch(route('plans.admin.destroy', id),{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
      .then(()=>window.location.reload())
  }
}
</script>

<template>
    <PageLayout>
      <PageHeader title="Planos de Assinatura (Admin)">
        <Link :href="route('plans.admin.create')" class="btn-primary">Novo Plano</Link>
      </PageHeader>
      <DataTable :data="plans" :columns="columns">
        <template #actions="{ row }">
          <Link :href="route('plans.admin.edit', row.id)" class="btn-sm">Editar</Link>
          <Button @click="destroy(row.id)" size="sm" variant="danger">Excluir</Button>
        </template>
      </DataTable>
    </PageLayout>
</template>
