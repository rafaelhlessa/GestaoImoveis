<script setup>
import { PhotoIcon, UserCircleIcon } from '@heroicons/vue/20/solid'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'
import { CheckIcon } from '@heroicons/vue/24/solid'

const steps = [
    { id: '01', name: 'Job Details', description: 'Vitae sed mi luctus laoreet.', href: '#', status: 'complete' },
    { id: '02', name: 'Application form', description: 'Cursus semper viverra.', href: '#', status: 'current' },
    { id: '03', name: 'Preview', description: 'Penatibus eu quis ante.', href: '#', status: 'upcoming' },
]

</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="py-12 bg-gray-50 text-gray-900 dark:bg-gray-700 dark:text-white/50 rounded-lg">

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-700 bg-gray-50 ">
                    <div class="lg:border-b lg:border-t lg:border-gray-200 p-4 flex items-center justify-between">
                        <nav aria-label="Progress">
                            <ol role="list" class="flex items-center">
                                <li v-for="(step, stepIdx) in steps" :key="step.name"
                                    :class="[stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : '', 'relative']">
                                    <template v-if="step.status === 'complete'">
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="h-0.5 w-full bg-indigo-600" />
                                        </div>
                                        <a href="#"
                                            class="relative flex size-8 items-center justify-center rounded-full bg-indigo-600 hover:bg-indigo-900">
                                            <CheckIcon class="size-5 text-white" aria-hidden="true" />
                                            <span class="sr-only">{{ step.name }}</span>
                                        </a>
                                    </template>
                                    <template v-else-if="step.status === 'current'">
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="h-0.5 w-full bg-gray-200" />
                                        </div>
                                        <a href="#"
                                            class="relative flex size-8 items-center justify-center rounded-full border-2 border-indigo-600 bg-white"
                                            aria-current="step">
                                            <span class="size-2.5 rounded-full bg-indigo-600" aria-hidden="true" />
                                            <span class="sr-only">{{ step.name }}</span>
                                        </a>
                                    </template>
                                    <template v-else>
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="h-0.5 w-full bg-gray-200" />
                                        </div>
                                        <a href="#"
                                            class="group relative flex size-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white hover:border-gray-400">
                                            <span class="size-2.5 rounded-full bg-transparent group-hover:bg-gray-300"
                                                aria-hidden="true" />
                                            <span class="sr-only">{{ step.name }}</span>
                                        </a>
                                    </template>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <form>
                            <div class="space-y-12">
                                <div class="border-b border-gray-900/10 pb-12">
                                    <h2 class="text-base/7 font-semibold text-gray-900">Cadastro Proprietário</h2>
                                    <p class="mt-1 text-sm/6 text-gray-600">Cadastre as informações</p>

                                    <div class="relative mt-6 mb-6">
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="w-full border-t border-gray-300" />
                                        </div>
                                        <div class="relative flex justify-center">
                                            <span
                                                class="bg-white px-3 text-base font-semibold text-gray-900">Informações</span>
                                        </div>
                                    </div>


                                    <div>

                                    </div>
                                    <div class="grid grid-cols-2 gap-2 flex space-x-4">
                                        <div class="px-4">
                                            <label for="name"
                                                class="block text-sm/6 font-medium text-gray-900">Nome</label>
                                            <div class="mt-2 mx-2">
                                                <input type="text" name="name" id="name" autocomplete="given-name"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>


                                        <div class="px-4">
                                            <label for="email"
                                                class="block text-sm/6 font-medium text-gray-900">Email</label>
                                            <div class="mt-2">
                                                <input id="email" name="email" type="email" autocomplete="email"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>

                                        <div class="px-4">
                                            <label for="cpf_cnpj" class="block text-sm/6 font-medium text-gray-900">CPF
                                                ou CNPJ</label>
                                            <div class="mt-2 mx-2">
                                                <input type="text" name="cpf_cnpj" id="cpf_cnpj" autocomplete="cpf_cnpj"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>

                                        <div class="px-4">
                                            <label for="phone"
                                                class="block text-sm/6 font-medium text-gray-900">Telefone</label>
                                            <div class="mt-2 mx-2">
                                                <input type="text" name="phone" id="phone" autocomplete="phone"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>

                                        <div class="px-4">
                                            <label for="city"
                                                class="block text-sm/6 font-medium text-gray-900">Cidade</label>
                                            <div class="mt-2">
                                                <input type="text" name="city" id="city" autocomplete="city"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>

                                        <div class="px-4">
                                            <label for="address"
                                                class="block text-sm/6 font-medium text-gray-900">Endereço</label>
                                            <div class="mt-2">
                                                <input type="text" name="address" id="address" autocomplete="address"
                                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-b border-gray-900/10 py-4">
                                    <h2 class="text-base/7 font-semibold text-gray-900">Perfil</h2>
                                    <p class="mt-1 text-sm/6 text-gray-600">Escolha o perfil de sua assinatura.</p>

                                    <div class="mt-2 space-y-2">

                                        <fieldset>
                                            <div class="mt-6 space-y-6">
                                                <div class="flex items-center gap-x-3">
                                                    <input id="owner" name="push-notifications" type="radio" checked=""
                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden" />
                                                    <label for="push-everything"
                                                        class="block text-sm/6 font-medium text-gray-900 px-2">Proprietário</label>
                                                </div>
                                                <div class="flex items-center gap-x-3">
                                                    <input id="service" name="push-notifications" type="radio"
                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden" />
                                                    <label for="push-email"
                                                        class="block text-sm/6 font-medium text-gray-900 px-2">Prestador
                                                        de Serviço</label>
                                                </div>
                                                <div class="flex items-center gap-x-3">
                                                    <input id="service_owner" name="push-notifications" type="radio"
                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden" />
                                                    <label for="push-nothing"
                                                        class="block text-sm/6 font-medium text-gray-900 px-2">Proprietário
                                                        / Prestador de
                                                        Serviço</label>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end">
                                <div class="px-4">
                                    <button type="button"
                                        class="text-sm/6 font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 my-2 shadow-md">Cancelar</button>

                                </div>
                                <div class="px-4">
                                    <button type="submit"
                                        class="text-sm/6 bg-indigo-300 font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 shadow-md">Salvar</button>
                                </div>
                                <!-- <button type="button" class="text-sm/6 font-semibold text-gray-900 border border-gray-400 rounded px-4 py-1 my-2">Cancelar</button>
                                <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold text-gray-400 shadow-sm">Salvar</button> -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>