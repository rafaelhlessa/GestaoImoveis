<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { defineComponent, h, ref } from 'vue'
import { Dialog, DialogPanel, RadioGroup, RadioGroupOption } from '@headlessui/vue'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/20/solid'

const navigation = [
  { name: 'Product', href: '#' },
  { name: 'Features', href: '#' },
  { name: 'Marketplace', href: '#' },
  { name: 'Company', href: '#' },
]
const pricing = {
  frequencies: [
    { value: 'monthly', label: 'Mensal', priceSuffix: '/mês' },
    { value: 'annually', label: 'Anual', priceSuffix: '/ano' },
  ],
  tiers: [
    {
      name: 'Proprietário Inteligente',
      id: 'tier-freelancer',
      href: '#',
      price: { monthly: 'R$49', annually: 'R$499' },
      description: 'Plano Básico, para gestores de baixo numero de propriedades.',
      features: ['3 propriedades', 'Alertas de Vencimento', 'Suporte Remoto'],
      mostPopular: false,
    },
    {
      name: 'Gestor de Propriedades',
      id: 'tier-startup',
      href: '#',
      price: { monthly: 'R$150', annually: 'R$1.500' },
      description: 'Plano intermediário, para gestores de um numero médop de propriedades.',
      features: [
        '10 propriedades',
        'Alertas de Vencimento',
        'Análises Avançadas',
        'Suporte Remoto',
      ],
      mostPopular: true,
    },
    {
      name: 'Gestor Imobiliário Avançado',
      id: 'tier-enterprise',
      href: '#',
      price: { monthly: 'R$250', annually: 'R$2.500' },
      description: 'Plano avançado para gestores de muitas propriedades.',
      features: [
        'Propriedades ilimitadas',
        'Alertas de Vencimento',
        'Análises Avançadas',
        'Suporte Dedicado',
        'Liberação de novas features primeiro',
        'Customização de ambiente',
      ],
      mostPopular: false,
    },
  ],
}
const faqs = [
  {
    id: 1,
    question: "Como o sistema de gestão de imóveis pode ajudar na organização dos meus documentos?",
    answer:
      "Nosso sistema centraliza e digitaliza todos os documentos relacionados às suas propriedades, como escrituras, contratos e certidões, em um único local. Com uma ferramenta de busca avançada e categorização intuitiva, você pode localizar rapidamente qualquer documento, economizando tempo e reduzindo o risco de extravios.",
  },
  {
    id: 2,
    question: "É seguro compartilhar o acesso com prestadores de serviços?",
    answer:
        "Sim, o sistema conta com permissões personalizáveis e criptografia avançada para garantir a segurança das suas informações. Você pode definir quem tem acesso a quais documentos e acompanhar um histórico detalhado de alterações e acessos, garantindo total controle e transparência."
  },
  {
    id: 3,
    question: "O sistema é adequado tanto para imóveis urbanos quanto rurais?",
    answer:
        "Sim! O sistema foi desenvolvido para atender propriedades de qualquer tipo, seja em área urbana ou rural. Ele permite o cadastro de informações específicas de cada tipo de imóvel e organiza todos os documentos pertinentes, tornando-se uma solução completa e flexível."
  },
  {
    id: 4,
    question: "O sistema de gestão de imóveis é compatível com dispositivos móveis?",
    answer:
        "Sim, o sistema é responsivo e pode ser acessado de qualquer dispositivo com conexão à internet. Com um aplicativo móvel dedicado, você pode gerenciar suas propriedades e documentos de qualquer lugar, a qualquer hora."
  }
  
]
const footerNavigation = {
  solutions: [
    { name: 'Marketing', href: '#' },
    { name: 'Analytics', href: '#' },
    { name: 'Automation', href: '#' },
    { name: 'Commerce', href: '#' },
    { name: 'Insights', href: '#' },
  ],
  support: [
    { name: 'Submit ticket', href: '#' },
    { name: 'Documentation', href: '#' },
    { name: 'Guides', href: '#' },
  ],
  company: [
    { name: 'About', href: '#' },
    { name: 'Blog', href: '#' },
    { name: 'Jobs', href: '#' },
    { name: 'Press', href: '#' },
  ],
  legal: [
    { name: 'Terms of service', href: '#' },
    { name: 'Privacy policy', href: '#' },
    { name: 'License', href: '#' },
  ],
  social: [
    {
      name: 'Facebook',
      href: '#',
      icon: defineComponent({
        render: () =>
          h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z',
              'clip-rule': 'evenodd',
            }),
          ]),
      }),
    },
    {
      name: 'Instagram',
      href: '#',
      icon: defineComponent({
        render: () =>
          h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z',
              'clip-rule': 'evenodd',
            }),
          ]),
      }),
    },
    {
      name: 'X',
      href: '#',
      icon: defineComponent({
        render: () =>
          h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', {
              d: 'M13.6823 10.6218L20.2391 3H18.6854L12.9921 9.61788L8.44486 3H3.2002L10.0765 13.0074L3.2002 21H4.75404L10.7663 14.0113L15.5685 21H20.8131L13.6819 10.6218H13.6823ZM11.5541 13.0956L10.8574 12.0991L5.31391 4.16971H7.70053L12.1742 10.5689L12.8709 11.5655L18.6861 19.8835H16.2995L11.5541 13.096V13.0956Z',
            }),
          ]),
      }),
    },
    {
      name: 'GitHub',
      href: '#',
      icon: defineComponent({
        render: () =>
          h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z',
              'clip-rule': 'evenodd',
            }),
          ]),
      }),
    },
    {
      name: 'YouTube',
      href: '#',
      icon: defineComponent({
        render: () =>
          h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z',
              'clip-rule': 'evenodd',
            }),
          ]),
      }),
    },
  ],
}

const mobileMenuOpen = ref(false)
const frequency = ref(pricing.frequencies[0])

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Welcome" />
    <!-- $page.props.auth.user -->
    <div class="bg-gray-900">
    <!-- Header -->
    <header>
      <nav class="mx-auto flex max-w-7xl items-center justify-between p-4 lg:px-8" aria-label="Global">
        <div class="flex lg:flex-1">
            <span class="sr-only">Propiedades na Mão</span>
            <img class="h-28 w-auto" src="/storage/logo2.png" alt="" />
        </div>
        <div v-if="$page.props.auth.user" class="flex lg:flex-1 lg:justify-end">
            <a href="dashboard" class="border border-gray-200 p-2 bg-zinc-700 rounded shadow-lg shadow-gray-500/50 text-sm/6 font-semibold text-white hover:bg-zinc-700">Acessar <span aria-hidden="true">&rarr;</span></a>
        </div>
        <div v-else class="flex lg:flex-1 lg:justify-end gap-x-4">
            <a href="login" class="border border-gray-200 py-2 px-4 bg-zinc-700/40 rounded shadow-lg shadow-gray-500/50 text-sm/6 font-semibold text-white hover:bg-zinc-700">Logar</a>
            <a href="register" class="border border-gray-200 p-2 bg-zinc-700/50 rounded shadow-lg shadow-gray-500/50 text-sm/6 font-semibold text-white hover:bg-zinc-700">Cadastrar</a>
        </div>
      </nav>
      <Dialog class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
        <div class="fixed inset-0 z-50" />
        <DialogPanel class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-gray-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
          <div class="flex items-center justify-between">
            <a href="#" class="-m-1.5 p-1.5">
              <span class="sr-only">Your Company</span>
              <img class="h-8 w-auto" src="https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=500" alt="" />
            </a>
            <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-400" @click="mobileMenuOpen = false">
              <span class="sr-only">Close menu</span>
              <XMarkIcon class="size-6" aria-hidden="true" />
            </button>
          </div>
          <div class="mt-6 flow-root">
            <div class="-my-6 divide-y divide-gray-500/25">
              <div class="space-y-2 py-6">
                <a v-for="item in navigation" :key="item.name" :href="item.href" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-white hover:bg-gray-800">{{ item.name }}</a>
              </div>
              <div class="py-6">
                <a href="#" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-white hover:bg-gray-800">Log in</a>
              </div>
            </div>
          </div>
        </DialogPanel>
      </Dialog>
    </header>

    <main>
      <!-- Pricing section -->
      <div class="mx-auto mt-8 max-w-7xl px-6 sm:mt-2 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
          <h1 class="text-base/7 font-semibold text-indigo-400">Preços</h1>
          <p class="mt-2 text-balance text-5xl font-semibold tracking-tight text-white sm:text-6xl">Planos e valores para você</p>
        </div>
        <p class="mx-auto mt-6 max-w-2xl text-pretty text-center text-lg font-medium text-gray-400 sm:text-xl/8">Escolha um plano acessível que inclua os melhores recursos para você fazer uma gestão eficaz dos seus imóveis.</p>
        <div class="mt-16 flex justify-center">
          <fieldset aria-label="Payment frequency">
            <RadioGroup v-model="frequency" class="grid grid-cols-2 gap-x-1 rounded-full bg-white/5 p-1 text-center text-xs/5 font-semibold text-white">
              <RadioGroupOption as="template" v-for="option in pricing.frequencies" :key="option.value" :value="option" v-slot="{ checked }">
                <div :class="[checked ? 'bg-indigo-500' : '', 'cursor-pointer rounded-full px-2.5 py-1']">{{ option.label }}</div>
              </RadioGroupOption>
            </RadioGroup>
          </fieldset>
        </div>
        <div class="isolate mx-auto mt-10 grid max-w-md grid-cols-1 gap-8 lg:mx-0 lg:max-w-none lg:grid-cols-3">
          <div v-for="tier in pricing.tiers" :key="tier.id" :class="[tier.mostPopular ? 'bg-white/5 ring-2 ring-indigo-500' : 'ring-1 ring-white/10', 'rounded-3xl p-8 xl:p-10']">
            <div class="flex items-center justify-between gap-x-4">
              <h2 :id="tier.id" class="text-lg/8 font-semibold text-white">{{ tier.name }}</h2>
              <p v-if="tier.mostPopular" class="rounded-full bg-indigo-500 px-2.5 py-1 text-xs/5 font-semibold text-white">Mais Polular</p>
            </div>
            <p class="mt-4 text-sm/6 text-gray-300">{{ tier.description }}</p>
            <p class="mt-6 flex items-baseline gap-x-1">
              <span class="text-4xl font-semibold tracking-tight text-white">{{ tier.price[frequency.value] }}</span>
              <span class="text-sm/6 font-semibold text-gray-300">{{ frequency.priceSuffix }}</span>
            </p>
            <a :href="tier.href" :aria-describedby="tier.id" :class="[tier.mostPopular ? 'bg-indigo-500 text-white shadow-sm hover:bg-indigo-400 focus-visible:outline-indigo-500' : 'bg-white/10 text-white hover:bg-white/20 focus-visible:outline-white', 'mt-6 block rounded-md px-3 py-2 text-center text-sm/6 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2']">Contratar</a>
            <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-300 xl:mt-10">
              <li v-for="feature in tier.features" :key="feature" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-white" aria-hidden="true" />
                {{ feature }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- FAQ section -->
      <div class="mx-auto mt-24 max-w-7xl px-6 sm:mt-26 lg:px-8">
        <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">Porque ter o Propriedades na Mão</h2>
        <p class="mt-6 max-w-2xl text-base/7 text-gray-300">O Propriedades na Mão é uma plataforma de gestão de imóveis que oferece funcionalidades essenciais para proprietários e prestadores de serviços. Além disso, o Propriedades na Mão é uma plataforma segura e fácil de usar, que permite o gerenciamento de imóveis de forma eficiente e organizada.</p>
        <p class="mt-6 max-w-2xl text-base/7 text-gray-300">Confira abaixo algumas das perguntas mais frequentes sobre o Propriedades na Mão.
        </p>
        <div class="mt-20">
          <dl class="space-y-16 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:gap-y-16 sm:space-y-0 lg:gap-x-10">
            <div v-for="faq in faqs" :key="faq.id">
              <dt class="text-base/7 font-semibold text-white">{{ faq.question }}</dt>
              <dd class="mt-2 text-base/7 text-gray-300">{{ faq.answer }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="mx-auto mt-24 max-w-7xl px-6 sm:mt-26 lg:px-8">
        <div class="mt-16 border-t border-white/10 pt-8 sm:mt-20 md:flex md:items-center md:justify-between lg:mt-24 pb-8">
          <div class="flex gap-x-6 md:order-2">
            <a v-for="item in footerNavigation.social" :key="item.name" :href="item.href" class="text-gray-400 hover:text-gray-300">
              <span class="sr-only">{{ item.name }}</span>
              <component :is="item.icon" class="size-6" aria-hidden="true" />
            </a>
          </div>
          <p class="mt-8 text-sm/6 text-gray-400 md:order-1 md:mt-0">&copy; 2024 Propriedades na mão, Inc. Todos direitos reservados.</p>
        </div>
      <!-- </div> -->
    </footer>
  </div>
</template>
