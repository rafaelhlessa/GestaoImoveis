<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

const { props } = usePage()
const isEdit = computed(() => !!props.criterion)
const form = useForm({
  name: props.criterion?.name || '',
  weight: props.criterion?.weight || 1,
})

function submit() {
  const routeName = isEdit.value ? 'criteria.update' : 'criteria.store'
  form[ isEdit.value ? 'put' : 'post' ]( route(routeName, props.criterion?.id), {
    onSuccess: () => window.location.href = route('criteria.index')
  })
}
</script>

<template>
      <PageHeader :title="isEdit ? 'Editar Critério' : 'Novo Critério'" />
      <form @submit.prevent="submit">
        <Card>
          <CardBody>
            <TextInput v-model="form.name" label="Nome" :error="form.errors.name" />
            <TextInput v-model.number="form.weight" label="Peso" type="number" step="0.01" :error="form.errors.weight" />
          </CardBody>
          <CardFooter>
            <Button type="submit" :loading="form.processing">Salvar</Button>
            <Link :href="route('criteria.index')" class="ml-2">Cancelar</Link>
          </CardFooter>
        </Card>
      </form>
</template>

<style scoped>
/* estilizações específicas */
</style>
