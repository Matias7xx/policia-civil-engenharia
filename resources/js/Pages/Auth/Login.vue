<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Indicador de carregamento
const isLoading = ref(false);

// Estado para mostrar/ocultar senha
const showPassword = ref(false);
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

// Ano atual para o rodapé
const currentYear = new Date().getFullYear();

// Função para simular carregamento durante o login
const handleSubmit = () => {
    isLoading.value = true;
    submit();
    
    // Resetar loading após 2 segundos (normalmente seria feito após a resposta)
    setTimeout(() => {
        isLoading.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Entrar" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-b from-gray-50 to-gray-200">
        <!-- Background elements decorativos -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-96 h-96 bg-[#f5e6b8] rounded-full opacity-10 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-[#bea55a] rounded-full opacity-10 blur-3xl"></div>
        </div>
        
        <!-- Logo -->
        <div ref="logoRef" class="relative z-10 mb-8 flex flex-col items-center">
            <img src="/images/logo-pc.png" alt="Logo Polícia Civil" class="w-32 sm:w-40 h-auto filter drop-shadow-lg">
            <div class="mt-3 text-center">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Censo de Imóveis</h1>
                <div class="mt-1 text-sm text-gray-600">Diretoria de Engenharia e Recursos Imobiliarios</div>
            </div>
        </div>

        <!-- Card de Login -->
        <div 
            ref="cardRef"
            class="w-full max-w-md px-6 py-8 bg-white shadow-xl overflow-hidden sm:rounded-lg relative z-10 border border-gray-200"
        >
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#bea55a] to-[#d4bf7a]"></div>
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-8">Acesso ao Sistema</h1>
            
            <div v-if="status" class="mb-6 p-4 bg-green-50 rounded-lg text-green-700 text-sm">
                {{ status }}
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Email -->
                <div ref="formElements" class="relative">
                    <InputLabel for="email" value="E-mail" class="text-gray-700" />
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="pl-10 pr-4 block w-full border-gray-300 focus:border-[#bea55a] focus:ring-[#bea55a] rounded-md shadow-sm transition-colors duration-200"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="seu.email@exemplo.com"
                        />
                    </div>
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Senha -->
                <div ref="formElements" class="relative">
                    <InputLabel for="password" value="Senha" class="text-gray-700" />
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <TextInput
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="pl-10 pr-10 block w-full border-gray-300 focus:border-[#bea55a] focus:ring-[#bea55a] rounded-md shadow-sm transition-colors duration-200"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <button 
                            type="button" 
                            @click="togglePasswordVisibility" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg v-if="showPassword" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                            <svg v-else class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <!-- Lembrar-me -->
                <div ref="formElements" class="block">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.remember" name="remember" class="rounded border-gray-300 text-[#bea55a] focus:ring-[#bea55a]" />
                        <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
                    </label>
                </div>

                <!-- Botão de Login e Esqueci a Senha -->
                <div ref="formElements" class="flex items-center justify-between mt-6">
                    <Link 
                        v-if="canResetPassword" 
                        :href="route('password.request')" 
                        class="text-sm text-gray-600 hover:text-[#bea55a] underline transition-colors duration-200"
                    >
                        Esqueceu sua senha?
                    </Link>

                    <PrimaryButton 
                        class="w-full sm:w-auto flex items-center justify-center space-x-2" 
                        :class="{ 'opacity-75 cursor-not-allowed': isLoading || form.processing }" 
                        :disabled="isLoading || form.processing" 
                        color="gold"
                    >
                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isLoading ? 'Entrando...' : 'Entrar' }}</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
        
        <!-- Rodapé -->
        <div ref="formElements" class="mt-8 text-center">
            <div class="text-sm text-gray-600">
                © {{ currentYear }} Polícia Civil - Todos os direitos reservados
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animação de fade para transições */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Animação de carregamento */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Efeitos de hover nos elementos interativos */
a, button {
    transition: all 0.2s ease-in-out;
}

a:hover, button:hover {
    transform: translateY(-1px);
}

/* Responsividade adicional */
@media (max-width: 640px) {
    .max-w-md {
        max-width: 90%;
    }
}

/* Estilização de foco acessível */
:focus {
    outline: 2px solid rgba(190, 165, 90, 0.5);
    outline-offset: 2px;
}

/* Melhoria visual para campos de formulário */
input.border-gray-300:focus {
    border-color: #bea55a;
    box-shadow: 0 0 0 2px rgba(190, 165, 90, 0.25);
}

@media (prefers-reduced-motion: reduce) {
    *, ::before, ::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>