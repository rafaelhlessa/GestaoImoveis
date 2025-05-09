<script setup>
import { computed } from 'vue'
import { usePage, useForm, Link } from '@inertiajs/inertia-vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import CardBody from '@/Components/CardBody.vue'
import CardFooter from '@/Components/CardFooter.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Button from '@/Components/Button.vue'

const { props } = usePage()
const isEdit = computed(() => !!props.plan)
const initialFeatures = props.plan?.features.join('\n') || ''
const form = useForm({
  name: props.plan?.name || '',
  slug: props.plan?.slug || '',
  price_monthly: props.plan?.price_monthly || 0,
  price_yearly: props.plan?.price_yearly || null,
  featuresText: initialFeatures,
})

function submit() {
  const featuresArr = form.featuresText.split('\n').map(s=>s.trim()).filter(s=>s)
  const payload = { ...form, features: featuresArr }
  const method = isEdit.value?'put':'post'
  const routeName = isEdit.value?'plans.admin.update':'plans.admin.store'
  form[method](route(routeName, props.plan?.id), payload, { onSuccess:()=>window.location.href=route('plans.admin.index') })
}
</script>

<template>
    <PageLayout>
      <PageHeader :title="isEdit?'Editar Plano':'Novo Plano'" />
      <form @submit.prevent="submit">
        <Card>
          <CardBody>
            <TextInput v-model="form.name" label="Nome" :error="form.errors.name" />
            <TextInput v-model="form.slug" label="Slug" :error="form.errors.slug" />
            <TextInput v-model.number="form.price_monthly" label="Preço Mensal" type="number" step="0.01" :error="form.errors.price_monthly" />
            <TextInput v-model.number="form.price_yearly" label="Preço Anual" type="number" step="0.01" :error="form.errors.price_yearly" />
            <Textarea v-model="form.featuresText" label="Recursos (uma por linha)" :error="form.errors.features" />
          </CardBody>
          <CardFooter>
            <Button type="submit" :loading="form.processing">Salvar</Button>
            <Link :href="route('plans.admin.index')" class="ml-2">Cancelar</Link>
          </CardFooter>
        </Card>
      </form>
    </PageLayout>
</template>
