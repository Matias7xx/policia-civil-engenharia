import "./bootstrap";
import "../css/app.css";
import axios from "axios";
import "leaflet/dist/leaflet.css";

// Configuração global do axios
axios.defaults.withCredentials = true;
axios.defaults.baseURL =
    import.meta.env.VITE_APP_URL || "http://localhost:8000";
axios.defaults.timeout = 90000; // 90 segundos de timeout

// Cache do token CSRF para evitar requisições desnecessárias
let csrfTokenInitialized = false;

// Inicialização do token CSRF
const initializeCsrfToken = async () => {
    if (csrfTokenInitialized) return;

    try {
        await axios.get("/sanctum/csrf-cookie");
        csrfTokenInitialized = true;
    } catch (error) {
        // Tentar novamente após 3 segundos
        setTimeout(initializeCsrfToken, 3000);
    }
};

// Iniciar a configuração do token CSRF
initializeCsrfToken();

// Interceptador para adicionar o token CSRF a todas as requisições
axios.interceptors.request.use(
    (config) => {
        // Adicionar o token CSRF se disponível
        const xsrfToken = document.cookie
            .split("; ")
            .find((row) => row.startsWith("XSRF-TOKEN="))
            ?.split("=")[1];

        if (xsrfToken) {
            const decodedXsrfToken = decodeURIComponent(xsrfToken);
            config.headers["X-XSRF-TOKEN"] = decodedXsrfToken;
        }

        // Adicionar logs apenas em ambiente de desenvolvimento
        if (import.meta.env.DEV) {
            console.log(
                `ðŸ"¡ ${config.method?.toUpperCase() || "GET"} ${config.url}`,
            );
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// Interceptador para tratamento global de erros nas respostas
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        // Tratamento específico para erros comuns
        if (status === 401) {
            console.error("Sessão expirada ou usuário não autenticado");
            // Redirecionar para login se necessário
            // window.location.href = '/login';
        } else if (status === 403) {
            console.error("Acesso não autorizado ao recurso");
        } else if (status === 422) {
            console.error("Dados de formulário inválidos");
        } else if (status === 500) {
            console.error("Erro interno do servidor");
        } else if (!status) {
            console.error("Erro de rede ou servidor indisponível");
        }

        return Promise.reject(error);
    },
);

// Imports e configuração do Vue e Inertia
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { IMaskDirective } from "vue-imask";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

// Nome da aplicação
const appName =
    import.meta.env.VITE_APP_NAME || "Sistema de Censo da Engenharia";

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        // Em produção: eager loading para melhor performance
        // Em desenvolvimento: lazy loading para hot reload
        if (import.meta.env.PROD) {
            const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
            return pages[`./Pages/${name}.vue`];
        } else {
            return resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob("./Pages/**/*.vue"),
            );
        }
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Registro de plugins
        app.use(plugin);

        // CONFIGURAÇÃO DO ZIGGY
        if (props.initialPage?.props?.ziggy !== undefined) {
            window.Ziggy = props.initialPage.props.ziggy;
        }

        app.use(ZiggyVue);

        // Registro de diretivas
        app.directive("imask", IMaskDirective);

        // Manipulador global de erros
        app.config.errorHandler = (error, vm, info) => {
            console.error("Erro na aplicação Vue:", error, info);
        };

        // Montagem da aplicação
        app.mount(el);

        return app;
    },
    progress: {
        color: "#bea55a",
        showSpinner: true,
        delay: 250,
    },
});

// Service Worker para suporte offline (PWA)
/* if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker registrado com sucesso', registration);
            })
            .catch(error => {
                console.error('Falha ao registrar o Service Worker', error);
            });
    });
} */
