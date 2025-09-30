<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                    Meu Perfil
                </h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Gerencie suas informações pessoais e configurações de conta
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-8 sm:px-6 lg:px-8">
                <!-- Informações do Perfil -->
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-2xl dark:bg-gray-800">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">
                            Informações Pessoais
                        </h3>
                        <p class="text-indigo-100 text-sm">
                            Atualize suas informações pessoais e perfis de acesso
                        </p>
                    </div>
                    <div class="p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            :user="user"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Segurança da Conta -->
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-2xl dark:bg-gray-800">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">
                            Segurança da Conta
                        </h3>
                        <p class="text-emerald-100 text-sm">
                            Mantenha sua conta segura atualizando sua senha regularmente
                        </p>
                    </div>
                    <div class="p-6">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <!-- Excluir Conta -->
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-2xl dark:bg-gray-800">
                    <div class="bg-gradient-to-r from-red-500 to-pink-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">
                            Zona de Perigo
                        </h3>
                        <p class="text-red-100 text-sm">
                            Ações irreversíveis para sua conta
                        </p>
                    </div>
                    <div class="p-6">
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
