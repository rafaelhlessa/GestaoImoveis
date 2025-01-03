import '../css/app.css';
import './bootstrap';



import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Criação do app Vue
        const vueApp = createApp({ render: () => h(App, props) });

        // Adiciona os plugins e registra o componente Select2 globalmente
        vueApp
            .use(plugin)
            .use(ZiggyVue)
            
            .mount(el);

        return vueApp;
    },
    progress: {
        // color: '#4B5563',
    },
});

