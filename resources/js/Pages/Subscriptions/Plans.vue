<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import CardBody from '@/Components/CardBody.vue'
import CardFooter from '@/Components/CardFooter.vue'
import Button from '@/Components/Button.vue'
import axios from 'axios'

const { props } = usePage()
const plans = ref(props.plans)
const loading = ref({})

async function subscribe(planId, interval) {
  loading.value[planId] = { ...loading.value[planId], [interval]: true }
  try {
    await axios.post(route('subscriptions.subscribe'), { plan_id: planId, interval })
    window.location.reload()
  } catch (error) {
    console.error(error)
  } finally {
    loading.value[planId] = { ...loading.value[planId], [interval]: false }
  }
}
</script>

<template>
    <PageLayout>
      <PageHeader title="Planos Disponíveis" />
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card v-for="plan in plans" :key="plan.id">
          <CardBody>
            <h2 class="text-xl font-semibold">{{ plan.name }}</h2>
            <p>Mensal: R$ {{ plan.price_monthly.toFixed(2) }}</p>
            <p v-if="plan.price_yearly">Anual: R$ {{ plan.price_yearly.toFixed(2) }}</p>
            <ul class="list-disc ml-4 mt-2">
              <li v-for="feature in plan.features" :key="feature">{{ feature }}</li>
            </ul>
          </CardBody>
          <CardFooter class="flex space-x-2">
            <Button @click="subscribe(plan.id,'monthly')" :loading="loading[plan.id]?.monthly">Assinar Mensal</Button>
            <Button @click="subscribe(plan.id,'yearly')" :loading="loading[plan.id]?.yearly" variant="secondary">Assinar Anual</Button>
          </CardFooter>
        </Card>
      </div>
    </PageLayout>
</template>
