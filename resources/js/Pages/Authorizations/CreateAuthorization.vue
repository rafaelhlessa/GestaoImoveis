<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';


const form = useForm({
    owner_id: '',
    service_provider_id: '',
    can_view_documents: false,
    can_create_properties: false,
});

const submit = () => {
    form.post('/authorizations');
};
</script>

<template>

    <Head title="Autorização" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Conceder Autorização
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow p-8">
                            <form @submit.prevent="submit" enctype="multipart/form-data">
                                <div class="space-y-12">
                                    <div class="border-b border-gray-900/10 pb-12">
                                        <h2 class="text-base/7 font-semibold text-gray-900">Autorização de Registro e Visualização</h2>
                                        <p class="mt-1 text-sm/6 text-gray-600">Este formulário será utilizado para fornecer autorização as informações da propriedade ao prestador de serviço.</p>

                                            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                                <div class="col-span-3">
                                                    <label for="propriety-name"
                                                        class="block text-sm/6 font-medium text-gray-900">Proprietário</label>
                                                    <div class="mt-2">
                                                        <input v-if="$page.props.auth.user.profile_id === 1 || $page.props.auth.user.profile_id === 3" type="text" name="propriety-name" id="propriety-name" autocomplete="propriety-name" :value="$page.props.auth.user.name" readonly required 
                                                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                                            
                                                    </div>
                                                </div>
                                                <div class="col-span-3">
                                                    <label for="propriety-name"
                                                        class="block text-sm/6 font-medium text-gray-900">ID do Prestador de Serviço</label>
                                                    <div class="mt-2">
                                                        <input type="text" name="propriety-name" id="propriety-name" autocomplete="propriety-name" v-model="form.service_provider_id" required
                                                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">   
                                                <div class="col-span-6"></div> 
                                                <div class="col-span-3">
                                                    <label class="inline-flex items-center mb-5 cursor-pointer">
                                                        <p class="text-gray-800">Pode visualizar documentos?</p>
                                                        <input type="checkbox" v-model="form.can_view_documents" class="sr-only peer">
                                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                    </label>
                                                    <!-- <label for="propriety-name"
                                                        class="block text-sm/6 font-medium text-gray-900">Pode visualizar documentos?</label>
                                                    <div class="mt-2">
                                                        <input type="checkbox" name="propriety-name" id="propriety-name" autocomplete="propriety-name" v-model="form.can_view_documents" required
                                                            class="block rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                                    </div> -->

                                                </div>
                                                <div class="col-span-3">
                                                    <label class="inline-flex items-center mb-5 cursor-pointer">
                                                        <p class="text-gray-800">Pode cadastrar propriedades?</p>
                                                        <input type="checkbox" v-model="form.can_create_properties" class="sr-only peer">
                                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mt-10">        
                                                <div class="col-span-6 flex justify-end">
                                                    <button type="submit" class="text-gray-900 border border-gray-800 rounded p-2 bg-green-400">Conceder Permissão</button>
                                                </div>    
                                            </div>
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
