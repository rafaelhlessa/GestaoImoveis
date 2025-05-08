<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/GuestLayoutLogin.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const status = ref(null);

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: (page) => {
            status.value = page.props.status;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Esqueceu a Senha" />

    <AuthLayout>
        <div>
            
        </div>
        <div class="mb-4 text-sm text-gray-300 max-w-600">
            <p>Informe seu endereço de e-mail e enviaremos um link para redefinição de senha.</p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Enviar Link de Redefinição de Senha
                </PrimaryButton>
            </div>
        </form>
    </AuthLayout>
</template>