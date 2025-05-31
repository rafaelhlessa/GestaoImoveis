<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { watch, defineProps} from 'vue';

const statuses = {
  Paid: 'text-green-700 bg-green-50 ring-green-600/20',
  Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
  Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
}

const props = defineProps({
    serviceProviders: Array,
});

const property = (id) => {
    console.log(id);
    router.get(route('clients.property', { id }));
}

// Função para aplicar a máscara de Telefone
const applyPhoneMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 10) {
        // TELEFONE: (00) 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2')
    } else {
        // CELULAR: (00) 0 0000-0000
        return numericValue
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{1})(\d{4})(\d)/, '$1 $2-$3');
    }
};
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
                            <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 sm:py-10 lg:max-w-7xl lg:px-8">
                                <div class="bg-white ">
                                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                                        <div class="mx-auto max-w-2xl lg:mx-0">
                                            <h2 class="text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">Seus Clientes</h2>
                                            <p class="mt-6 mb-6 text-lg/8 text-gray-600">Lista dos clientes, proprietários que concederam acesso as informações das suas propriedades.</p>
                                        </div>
                                            <ul role="list" v-if="props.serviceProviders" v-for="client in props.serviceProviders" :key="client" class="grid md:grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                <a href="#" class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow m-2">
                                                    <li @click="property(client.id)" class="lg:w-96 md:w-auto col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
                                                        <div class="flex w-full items-center justify-between space-x-6 p-6">
                                                        <div class="flex-1 truncate">
                                                            <div class="flex items-center space-x-3">
                                                            <h3 class="truncate text-sm font-medium text-gray-900">{{ client.name }}</h3>
                                                            <span v-if="client.profile_id === 1" class="inline-flex shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Proprietário</span>
                                                            </div>
                                                            <p class="mt-1 truncate text-sm text-gray-500">{{client.city}}</p>
                                                        </div>

                                                        <img class="size-10 shrink-0 rounded-full bg-gray-300 ring-2 ring-gray-300" src="/storage/user.jpg" alt="">
                                                        </div>
                                                        <div>
                                                        <div class="-mt-px flex divide-x divide-gray-200">
                                                            <div class="flex w-0 flex-1">
                                                            <a href="#" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-2 text-xs font-semibold text-gray-900">
                                                                <svg class="size-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                                    <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                                                                    <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                                                                </svg>
                                                                {{client.email}}
                                                            </a>
                                                            </div>
                                                            <div class="-ml-px flex w-0 flex-1">
                                                            <a href="tel:+1-202-555-0170" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                                                                <svg class="size-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                                <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5Z" clip-rule="evenodd" />
                                                                </svg>
                                                                {{applyPhoneMask(client.phone)}}
                                                            </a>
                                                            </div>
                                                        </div>
                                                        </div>
                                                    </li>
                                                </a>
                                            </ul>
                                            <div v-else class="flex items-center justify-center h-96">
                                                <div class="text-center">
                                                    <h2 class="text-2xl font-semibold text-gray-900">Nenhum cliente encontrado</h2>
                                                    <p class="mt-2 text-sm text-gray-600">Não há clientes cadastrados.</p>
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
