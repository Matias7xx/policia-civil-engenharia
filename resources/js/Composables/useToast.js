import { getCurrentInstance } from "vue";

export function useToast() {
    const instance = getCurrentInstance();

    // Referência ao componente Toast global
    const getToastComponent = () => {
        // Primeiro, tentar acessar via window (registrado globalmente no AppLayout)
        if (typeof window !== "undefined" && window.__toastInstance) {
            return window.__toastInstance;
        }

        // Fallback: procurar na árvore de componentes
        let current = instance;
        while (current) {
            if (current.refs?.toast || current.exposed?.success) {
                return current.refs?.toast || current.exposed;
            }
            current = current.parent;
        }

        console.warn(
            "Toast component não encontrado. Certifique-se de que o componente Toast está registrado globalmente.",
        );
        return null;
    };

    const showToast = (message, type = "info", duration = 5000) => {
        const component = getToastComponent();
        if (component && typeof component[type] === "function") {
            component[type](message, duration);
        } else {
            // Fallback para console se toast não estiver disponível
            console.log(`${type.toUpperCase()}:`, message);

            // Fallback para alert (apenas para erros)
            if (type === "error") {
                alert(`Erro: ${message}`);
            }
        }
    };

    const toast = {
        success: (message, options = {}) => {
            showToast(message, "success", options.duration || 5000);
        },

        error: (message, options = {}) => {
            showToast(message, "error", options.duration || 7000);
        },

        warning: (message, options = {}) => {
            showToast(message, "warning", options.duration || 6000);
        },

        info: (message, options = {}) => {
            showToast(message, "info", options.duration || 5000);
        },

        // mostrar erros de validação
        validationErrors: (errors, options = {}) => {
            if (!errors || typeof errors !== "object") {
                return;
            }

            // Se errors é um objeto com múltiplos campos
            const errorMessages = [];

            Object.keys(errors).forEach((field) => {
                const fieldErrors = Array.isArray(errors[field])
                    ? errors[field]
                    : [errors[field]];
                fieldErrors.forEach((error) => {
                    if (error && typeof error === "string") {
                        errorMessages.push(error);
                    }
                });
            });

            // Mostrar o primeiro erro ou todos os erros
            if (errorMessages.length > 0) {
                if (options.showAll && errorMessages.length > 1) {
                    // Mostrar todos os erros
                    errorMessages.forEach((message, index) => {
                        setTimeout(() => {
                            showToast(`❌ ${message}`, "error", 7000);
                        }, index * 500); // Delay entre as mensagens
                    });
                } else {
                    // Mostrar apenas o primeiro erro
                    showToast(`❌ ${errorMessages[0]}`, "error", 7000);
                }
            }
        },

        // Função para mostrar erro de servidor
        serverError: (
            message = "Erro interno do servidor. Tente novamente.",
            options = {},
        ) => {
            showToast(`🔥 ${message}`, "error", options.duration || 8000);
        },

        clear: () => {
            const component = getToastComponent();
            if (component?.clear) {
                component.clear();
            }
        },
    };

    return toast;
}
