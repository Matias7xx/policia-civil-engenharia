/**
 * Bootstrap.js - Configura bibliotecas e serviços essenciais para a aplicação
 */

import axios from "axios";

// Configuração global do axios
window.axios = axios;

// Configuração de headers padrão
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.headers.common["Accept"] = "application/json";

// Detecção de conexão para suporte offline
const updateOnlineStatus = () => {
    const status = navigator.onLine ? "online" : "offline";
    document.documentElement.setAttribute("data-connection", status);

    if (!navigator.onLine) {
        console.warn(
            "Aplicação offline - algumas funcionalidades podem não estar disponíveis",
        );
    }
};

window.addEventListener("online", updateOnlineStatus);
window.addEventListener("offline", updateOnlineStatus);
updateOnlineStatus();

// Detectar se o dispositivo é móvel
const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent,
    );
document.documentElement.setAttribute(
    "data-device",
    isMobile ? "mobile" : "desktop",
);

// Configuração para debug condicional
window.debug = (message, ...args) => {
    if (import.meta.env.DEV) {
        console.log(`[DEBUG] ${message}`, ...args);
    }
};

// Utilitário para formatação de dados
window.formatters = {
    currency: (value) =>
        new Intl.NumberFormat("pt-BR", {
            style: "currency",
            currency: "BRL",
        }).format(value),
    date: (value) =>
        value ? new Intl.DateTimeFormat("pt-BR").format(new Date(value)) : "",
    percentage: (value) => `${(value * 100).toFixed(2)}%`,
};

// Inicializar configurações regionais
if (navigator.language) {
    document.documentElement.setAttribute("lang", navigator.language);
}

console.log(`🚀 Aplicação inicializada (${import.meta.env.MODE})`);
