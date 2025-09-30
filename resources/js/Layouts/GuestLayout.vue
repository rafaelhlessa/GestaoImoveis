<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Logo from '/storage/app/public/logo-sem fundo.png'
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Conteúdo Principal -->
        <div class="flex justify-center items-center min-h-screen px-4 py-4">
            <div class="w-full max-w-7xl">
                <slot />
            </div>
        </div>
    </div>
</template>
