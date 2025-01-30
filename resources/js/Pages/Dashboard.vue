<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { EllipsisHorizontalIcon } from '@heroicons/vue/20/solid'
import { onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
// import { useRouter } from 'vue-router';

const { props } = usePage();
// const router = useRouter();

onMounted(() => {
    if (props.auth.user.profile_id > 1) {
        router.get(route('propertyNew.index', props.auth.user.id));
        // router.get({ name: 'service-provider.index' });
    }
});

const statuses = {
  Paid: 'text-green-700 bg-green-50 ring-green-600/20',
  Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
  Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
}

const clients = [
  {
    id: 1,
    name: 'Proprietário',
    imageUrl: '',
    lastInvoice: { total: '9', last_year: '12', status: '3' },
  },
  {
    id: 2,
    name: 'Arrendatário',
    imageUrl: '',
    lastInvoice: { total: '4', last_year: '0', status: '2' },
  },
  {
    id: 3,
    name: 'Parceria Agrícola',
    imageUrl: '',
    lastInvoice: { total: '4', last_year: '6', status: '1' },
  },
]
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow">
                            <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
                                <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                                    <li v-for="client in clients" :key="client.id"
                                        class="overflow-hidden rounded-xl border border-gray-200">
                                        <div
                                            class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6">
                                            <img :src="client.imageUrl" :alt="client.name"
                                                class="size-12 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10" />
                                            <div class="text-sm/6 font-medium text-gray-900">{{ client.name }}</div>
                                            <Menu as="div" class="relative ml-auto">
                                                <MenuButton
                                                    class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500">
                                                    <span class="sr-only">Open options</span>
                                                    <EllipsisHorizontalIcon class="size-5" aria-hidden="true" />
                                                </MenuButton>
                                                <transition enter-active-class="transition ease-out duration-100"
                                                    enter-from-class="transform opacity-0 scale-95"
                                                    enter-to-class="transform opacity-100 scale-100"
                                                    leave-active-class="transition ease-in duration-75"
                                                    leave-from-class="transform opacity-100 scale-100"
                                                    leave-to-class="transform opacity-0 scale-95">
                                                    <MenuItems
                                                        class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                                        <MenuItem v-slot="{ active }">
                                                        <a href="#"
                                                            :class="[active ? 'bg-gray-50 outline-none' : '', 'block px-3 py-1 text-sm/6 text-gray-900']">View<span
                                                                class="sr-only">, {{ client.name }}</span></a>
                                                        </MenuItem>
                                                        <MenuItem v-slot="{ active }">
                                                        <a href="#"
                                                            :class="[active ? 'bg-gray-50 outline-none' : '', 'block px-3 py-1 text-sm/6 text-gray-900']">Edit<span
                                                                class="sr-only">, {{ client.name }}</span></a>
                                                        </MenuItem>
                                                    </MenuItems>
                                                </transition>
                                            </Menu>
                                        </div>
                                        <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm/6">
                                            <div class="flex justify-between gap-x-4 py-3">
                                                <dt class="text-gray-500">Total</dt>
                                                <dd class="text-gray-700">
                                                    {{client.lastInvoice.total}}
                                                </dd>
                                            </div>
                                            <div class="flex justify-between gap-x-4 py-3">
                                                <dt class="text-gray-500">Último Ano</dt>
                                                <dd class="flex items-start gap-x-2">
                                                    <div class="font-medium text-gray-900">{{ client.lastInvoice.last_year
                                                        }}%</div>
                                                    <div
                                                        :class="[statuses[client.lastInvoice.status], 'rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset flex items-center gap-x-1']">
                                                        <template v-if="client.lastInvoice.status === '1'">
                                                            <span class="text-red-500">↓</span>
                                                        </template>
                                                        <template v-else-if="client.lastInvoice.status === '2'">
                                                            <span class="text-blue-500">-</span>
                                                        </template>
                                                        <template v-else-if="client.lastInvoice.status === '3'">
                                                            <span class="text-green-500">↑</span>
                                                        </template>
                                                        
                                                    </div>
                                                </dd>
                                            </div>
                                        </dl>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
