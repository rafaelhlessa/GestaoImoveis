<script setup>
import { reactive, ref } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import CardBody from '@/Components/CardBody.vue'
import CardFooter from '@/Components/CardFooter.vue'
import TextInput from '@/Components/TextInput.vue'
import Button from '@/Components/Button.vue'
import axios from 'axios'

const card = reactive({ number:'', expiry:'', cvc:'' })
const processing = ref(false)

async function submit() {
  processing.value = true
  try {
    await axios.post(route('subscriptions.subscribe'), { /* pagamentos processados via gateway */ })
  } catch(e) {
    console.error(e)
  } finally {
    processing.value = false
  }
}
</script>

<template>
    <PageLayout>
      <PageHeader title="Pagamento da Assinatura" />
      <form @submit.prevent="submit">
        <Card>
          <CardBody>
            <TextInput v-model="card.number" label="Número do Cartão" />
            <TextInput v-model="card.expiry" label="Validade (MM/AA)" />
            <TextInput v-model="card.cvc" label="CVC" />
          </CardBody>
          <CardFooter>
            <Button type="submit" :loading="processing">Pagar</Button>
          </CardFooter>
        </Card>
      </form>
    </PageLayout>
</template>
