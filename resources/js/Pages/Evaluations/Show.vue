<script setup>
import { usePage, Link } from '@inertiajs/inertia-vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import CardBody from '@/Components/CardBody.vue'
import CardFooter from '@/Components/CardFooter.vue'

const { props } = usePage()
const property = props.property
const evaluation = props.evaluation
</script>

<template>
    <PageLayout>
      <PageHeader title="Detalhes da Avaliação" />
      <Card>
        <CardBody>
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Score: {{ evaluation.score }}</h3>
            <p>Comentários: {{ evaluation.comments || '—' }}</p>
          </div>
          <div v-for="item in evaluation.items" :key="item.id" class="mb-4 border-t pt-2">
            <div class="flex justify-between">
              <span>{{ item.criterion.name }} (Peso: {{ item.criterion.weight }})</span>
              <span>Nota: {{ item.note }}</span>
            </div>
            <p v-if="item.observation" class="mt-1 text-sm text-gray-600">Observação: {{ item.observation }}</p>
          </div>
        </CardBody>
        <CardFooter>
          <Link :href="route('properties.evaluations.index', property.id)" class="btn-secondary">Voltar</Link>
        </CardFooter>
      </Card>
    </PageLayout>
</template>
