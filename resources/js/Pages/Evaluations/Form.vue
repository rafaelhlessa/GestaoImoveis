<script setup>
import { reactive, computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import CardBody from '@/Components/CardBody.vue'
import CardFooter from '@/Components/CardFooter.vue'
import Textarea from '@/Components/Textarea.vue'
import TextInput from '@/Components/TextInput.vue'
import Button from '@/Components/Button.vue'

const { props } = usePage()
const isEdit = computed(() => !!props.evaluation)
const property = props.property
const criteria = props.criteria
const form = useForm({ comments: props.evaluation?.comments || '' })
const notes = reactive({})
const observations = reactive({})
criteria.forEach(c => {
  notes[c.id] = isEdit.value
    ? props.evaluation.items.find(i => i.criterion_id===c.id)?.note||0
    : 0
  observations[c.id] = isEdit.value
    ? props.evaluation.items.find(i=>i.criterion_id===c.id)?.observation||''
    : ''
})

function submit() {
  const payload = {
    comments: form.comments,
    notes: criteria.map(c=>({criterion_id:c.id, note:notes[c.id], observation:observations[c.id]}))
  }
  const method = isEdit.value?'put':'post'
  const routeName = isEdit.value?'properties.evaluations.update':'properties.evaluations.store'
  form[method](route(routeName, [property.id, props.evaluation?.id]), payload, {
    onSuccess:()=>window.location.href=route('properties.evaluations.index',property.id)
  })
}
</script>

<template>
    <PageLayout>
      <PageHeader :title="isEdit ? 'Editar Avaliação' : 'Nova Avaliação'" />

      <form @submit.prevent="submit">
        <Card>
          <CardBody>
            <Textarea v-model="form.comments" label="Comentários" :error="form.errors.comments" />
            <div v-for="criterion in criteria" :key="criterion.id" class="mb-4">
              <div class="flex justify-between">
                <label>{{ criterion.name }} (peso: {{ criterion.weight }})</label>
                <TextInput v-model.number="notes[criterion.id]" type="number" min="0" max="10" step="0.1" :error="form.errors[`notes.${criterion.id}.note`]" />
              </div>
              <Textarea v-model="observations[criterion.id]" label="Observação" :error="form.errors[`notes.${criterion.id}.observation`]" />
            </div>
          </CardBody>
          <CardFooter>
            <Button type="submit" :loading="form.processing">Salvar</Button>
            <Link :href="route('properties.evaluations.index', property.id)" class="ml-2">Cancelar</Link>
          </CardFooter>
        </Card>
      </form>
    </PageLayout>
</template>

<style scoped>
/* ... */
</style>
