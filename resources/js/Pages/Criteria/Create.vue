<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import PageLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import  Form  from './Form.vue'


</script>
<template>
    <PageLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Critérios de Avaliação
                </h1>
                <div class="flex justify-end">
                    <Link :href="route('criteria.create')" class="btn-primary">Novo Critério</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 bg-gray-50 dark:bg-gray-100 space-y-6 rounded-lg">
                                <Form />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </PageLayout>
</template>
