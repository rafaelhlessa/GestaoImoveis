<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/20/solid'

const people = [
  {
    name: 'Leslie Alexander',
    email: 'leslie.alexander@example.com',
    role: 'Co-Founder / CEO',
    imageUrl:
      'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: '3h ago',
    lastSeenDateTime: '2023-01-23T13:23Z',
  },
  {
    name: 'Michael Foster',
    email: 'michael.foster@example.com',
    role: 'Co-Founder / CTO',
    imageUrl:
      'https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: '3h ago',
    lastSeenDateTime: '2023-01-23T13:23Z',
  },
  {
    name: 'Dries Vincent',
    email: 'dries.vincent@example.com',
    role: 'Business Relations',
    imageUrl:
      'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: null,
  },
  {
    name: 'Lindsay Walton',
    email: 'lindsay.walton@example.com',
    role: 'Front-end Developer',
    imageUrl:
      'https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: '3h ago',
    lastSeenDateTime: '2023-01-23T13:23Z',
  },
  {
    name: 'Courtney Henry',
    email: 'courtney.henry@example.com',
    role: 'Designer',
    imageUrl:
      'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: '3h ago',
    lastSeenDateTime: '2023-01-23T13:23Z',
  },
  {
    name: 'Tom Cook',
    email: 'tom.cook@example.com',
    role: 'Director of Product',
    imageUrl:
      'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    href: '#',
    lastSeen: null,
  },
]

const newAuthorization = () => {
    router.get(route('authorizations.create'));
}
</script>

<template>

    <Head title="Lista de Autorizações" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <h1 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                  Lista de Autorizações
                </h1>
                <div class="flex justify-end">
                    <button @click="newAuthorization" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Adicionar Autorização
                    </button>
                </div>

            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow p-8">
                            <form @submit.prevent="submit" enctype="multipart/form-data">
                                <div class="space-y-12">
                                    <div class="border-b border-gray-900/10 pb-12">
                                        <h2 class="text-base/7 font-semibold text-gray-900">Prestadores de Serviço Autorizados</h2>
                                        <p class="mt-1 text-sm/6 text-gray-600">Esta lista mostra os prestadores de serviço autorizados pelo proprietário e a respectiva autorização.</p>

                                        <ul role="list" class="pt-12 divide-y divide-gray-100">
                                            <li v-for="person in people" :key="person.email" class="relative py-5 hover:bg-gray-50 border-t">
                                            <div class="px-4 py-1 sm:px-6 lg:px-8">
                                                <div class="mx-auto flex max-w-4xl justify-between gap-x-6">
                                                <div class="flex min-w-0 gap-x-4">
                                                    <img class="size-12 flex-none rounded-full bg-gray-50" :src="person.imageUrl" alt="" />
                                                    <div class="min-w-0 flex-auto">
                                                    <p class="text-sm/6 font-semibold text-gray-900">
                                                        <a :href="person.href">
                                                        <span class="absolute inset-x-0 -top-px bottom-0" />
                                                        {{ person.name }}
                                                        </a>
                                                    </p>
                                                    <p class="mt-1 flex text-xs/5 text-gray-500">
                                                        <a :href="`mailto:${person.email}`" class="relative truncate hover:underline">{{ person.email }}</a>
                                                    </p>
                                                    <p class="mt-1 flex text-xs/5 text-gray-500">
                                                        <a :href="`mailto:${person.email}`" class="relative truncate hover:underline">12.265.298/0001-59</a>
                                                    </p>
                                                    </div>
                                                </div>
                                                <div class="flex shrink-0 items-center gap-x-4">
                                                    <div class="hidden sm:flex sm:flex-col sm:items-start">
                                                    <p class="text-sm/6 text-gray-900">Autorizações</p>
                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                            <div class="flex-none rounded-full bg-red-500/20 p-1">
                                                            <div class="size-1.5 rounded-full bg-red-500" />
                                                            </div>
                                                            <p class="text-xs/5 text-gray-500">Cadastro</p>
                                                        </div>
                                                        <div class="mt-1 flex items-center gap-x-1.5">
                                                            <div class="flex-none rounded-full bg-emerald-500/20 p-1">
                                                            <div class="size-1.5 rounded-full bg-emerald-500" />
                                                            </div>
                                                            <p class="text-xs/5 text-gray-500">Visualização</p>
                                                        </div>
                                                    </div>
                                                    <ChevronRightIcon class="size-5 flex-none text-gray-400" aria-hidden="true" />
                                                </div>
                                                </div>
                                            </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>                
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
