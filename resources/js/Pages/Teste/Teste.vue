<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { EllipsisHorizontalIcon } from '@heroicons/vue/20/solid'

const statuses = {
  Paid: 'text-green-700 bg-green-50 ring-green-600/20',
  Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
  Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
}

const props = defineProps({
    serviceProviders: Array,
});

const clients = [
  {
    id: 1,
    name: 'Proprietário',
    imageUrl: 'https://tailwindui.com/plus/img/logos/48x48/tuple.svg',
    lastInvoice: { total: '9', last_year: '12', status: '3' },
  },
  {
    id: 2,
    name: 'Arrendatário',
    imageUrl: 'https://tailwindui.com/plus/img/logos/48x48/savvycal.svg',
    lastInvoice: { total: '4', last_year: '0', status: '2' },
  },
  {
    id: 3,
    name: 'Parceria Agrícola',
    imageUrl: 'https://tailwindui.com/plus/img/logos/48x48/reform.svg',
    lastInvoice: { total: '4', last_year: '6', status: '1' },
  },
]
</script>

<template>

    <Head title="Clientes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Clientes
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow">
                            <h1 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-800 p-4">Clientes</h1>
                            <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 sm:py-10 lg:max-w-7xl lg:px-8">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div v-for="client in props.serviceProviders" :key="client">
                                        <div class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400">
                                            <div class="shrink-0">
                                                <img class="size-10 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                <a href="#" class="focus:outline-none">
                                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                                    <p class="text-sm font-medium text-gray-900">{{client.owner.name}}</p>
                                                    <p class="truncate text-sm text-gray-500">{{ client.owner.phone }}</p>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
