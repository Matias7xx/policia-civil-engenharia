<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const toasts = ref([]);
let toastCounter = 0;

// Computed para determinar a posição baseada no tamanho da tela
const toastPosition = computed(() => {
    // No mobile, mostra no topo
    // No desktop, mostra no canto superior direito
    return 'top-right';
});

// Função para criar uma nova notificação
const createToast = (message, type = 'info', duration = 5000) => {
    const id = ++toastCounter;
    const toast = {
        id,
        message,
        type,
        duration,
        visible: true,
        progress: 100
    };
    
    toasts.value.push(toast);
    
    // Progress bar e remoção automática
    if (duration > 0) {
        const interval = setInterval(() => {
            toast.progress -= (100 / duration) * 100; // Atualiza a cada 100ms
            if (toast.progress <= 0) {
                clearInterval(interval);
                removeToast(id);
            }
        }, 100);
        
        toast.intervalId = interval;
    }
    
    return id;
};

// Função para remover uma notificação
const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index > -1) {
        const toast = toasts.value[index];
        
        // Limpar interval se existir
        if (toast.intervalId) {
            clearInterval(toast.intervalId);
        }
        
        // Animação de saída
        toast.visible = false;
        
        // Remover do array após a animação
        setTimeout(() => {
            const newIndex = toasts.value.findIndex(t => t.id === id);
            if (newIndex > -1) {
                toasts.value.splice(newIndex, 1);
            }
        }, 300);
    }
};

// Função para pausar o progresso quando mouse hover
const pauseToast = (id) => {
    const toast = toasts.value.find(t => t.id === id);
    if (toast && toast.intervalId) {
        clearInterval(toast.intervalId);
        toast.intervalId = null;
    }
};

// Função para retomar o progresso
const resumeToast = (id) => {
    const toast = toasts.value.find(t => t.id === id);
    if (toast && !toast.intervalId && toast.progress > 0) {
        const interval = setInterval(() => {
            toast.progress -= (100 / toast.duration) * 100;
            if (toast.progress <= 0) {
                clearInterval(interval);
                removeToast(id);
            }
        }, 100);
        toast.intervalId = interval;
    }
};

// Função para obter as classes de estilo baseadas no tipo
const getToastClasses = (type) => {
    const baseClasses = 'flex items-start p-4 rounded-lg shadow-lg border-l-4 backdrop-blur-sm max-w-sm w-full';
    
    switch (type) {
        case 'success':
            return `${baseClasses} bg-green-50 border-green-400 text-green-800`;
        case 'error':
            return `${baseClasses} bg-red-50 border-red-400 text-red-800`;
        case 'warning':
            return `${baseClasses} bg-yellow-50 border-yellow-400 text-yellow-800`;
        case 'info':
        default:
            return `${baseClasses} bg-blue-50 border-blue-400 text-blue-800`;
    }
};

// Função para obter o ícone baseado no tipo
const getToastIcon = (type) => {
    switch (type) {
        case 'success':
            return `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`;
        case 'error':
            return `<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`;
        case 'warning':
            return `<svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>`;
        case 'info':
        default:
            return `<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`;
    }
};

// Watch para mensagens flash do Inertia
watch(() => page.props.flash, (flash) => {
    if (flash) {
        if (flash.success) {
            createToast(flash.success, 'success');
        }
        if (flash.error) {
            createToast(flash.error, 'error');
        }
        if (flash.warning) {
            createToast(flash.warning, 'warning');
        }
        if (flash.info) {
            createToast(flash.info, 'info');
        }
    }
}, { deep: true, immediate: true });

// funções para uso externo
defineExpose({
    success: (message, duration = 5000) => createToast(message, 'success', duration),
    error: (message, duration = 7000) => createToast(message, 'error', duration),
    warning: (message, duration = 6000) => createToast(message, 'warning', duration),
    info: (message, duration = 5000) => createToast(message, 'info', duration),
    clear: () => {
        toasts.value.forEach(toast => {
            if (toast.intervalId) {
                clearInterval(toast.intervalId);
            }
        });
        toasts.value = [];
    }
});

onMounted(() => {
    const flash = page.props.flash;
    if (flash) {
        if (flash.success) createToast(flash.success, 'success');
        if (flash.error) createToast(flash.error, 'error');
        if (flash.warning) createToast(flash.warning, 'warning');  
        if (flash.info) createToast(flash.info, 'info');
    }
});
</script>

<template>
    <!-- Container de Toasts -->
    <Teleport to="body">
        <div 
            v-if="toasts.length > 0"
            class="fixed inset-0 pointer-events-none flex"
            style="z-index: 9999;"
            :class="{
                'items-start justify-end p-6': toastPosition === 'top-right',
                'items-start justify-center p-4': toastPosition === 'top-center',
                'items-end justify-center p-4': toastPosition === 'bottom-center'
            }"
        >
            <!-- Lista de Toasts -->
            <div class="space-y-3 pointer-events-auto">
                <TransitionGroup
                    name="toast"
                    tag="div"
                    class="space-y-3"
                >
                    <div
                        v-for="toast in toasts"
                        :key="toast.id"
                        :class="[
                            getToastClasses(toast.type),
                            toast.visible ? 'transform translate-x-0 opacity-100' : 'transform translate-x-full opacity-0'
                        ]"
                        class="transition-all duration-200 ease-out relative overflow-hidden"
                        @mouseenter="pauseToast(toast.id)"
                        @mouseleave="resumeToast(toast.id)"
                        role="alert"
                        :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
                    >
                        <!-- Progress Bar -->
                        <div 
                            v-if="toast.duration > 0"
                            class="absolute bottom-0 left-0 h-1 bg-current opacity-30 transition-all duration-100 ease-linear"
                            :style="{ width: `${Math.max(0, toast.progress)}%` }"
                        ></div>
                        
                        <!-- Ícone -->
                        <div 
                            class="flex-shrink-0 mr-3"
                            v-html="getToastIcon(toast.type)"
                        ></div>
                        
                        <!-- Conteúdo -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium leading-5">
                                {{ toast.message }}
                            </p>
                        </div>
                        
                        <!-- Botão Fechar -->
                        <button
                            @click="removeToast(toast.id)"
                            class="flex-shrink-0 ml-3 text-current hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-current focus:ring-opacity-25 rounded-full p-1 transition-opacity duration-200"
                            :aria-label="`Fechar notificação: ${toast.message}`"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Animações para os toasts */
.toast-enter-active {
    transition: all 0.2s ease-out;
}

.toast-leave-active {
    transition: all 0.2s ease-in;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

.toast-move {
    transition: transform 0.2s ease;
}

/* Responsividade */
@media (max-width: 640px) {
    .max-w-sm {
        max-width: calc(100vw - 2rem);
    }
}
</style>