<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOfficeIcon, 
    ExclamationTriangleIcon, 
    CheckCircleIcon, 
    EyeIcon, 
    PlusIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unidadeCadastrada: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    isServidor: {
        type: Boolean,
        default: false
    },
    unidadesCount: {
        type: Number,
        default: 0
    },
    unidadesPendentes: {
        type: Number,
        default: 0
    }
});

const page = usePage();

const welcomeMessage = computed(() => {
    if (props.isSuperAdmin) {
        return "Bem-vindo ao Painel de Administração";
    } else if (props.isAdmin) {
        return props.unidadeCadastrada 
            ? "Obrigado por participar do Censo Anual da Engenharia" 
            : "Bem-vindo ao Sistema de Censo Anual da Engenharia";
    } else {
        return "Bem-vindo ao Sistema de Censo Anual da Engenharia";
    }
});

// Determinar a rota e texto do link com base no papel e unidade
const getUnidadeLink = computed(() => {
    if (props.isAdmin || props.isServidor) {
        if (props.unidadeCadastrada) {
            const teamId = page.props.auth.user.current_team_id;
            const unidadeId = page.props.auth.unidade_id;
            return {
                href: route('unidades.show', { team: teamId, unidade: unidadeId }),
                text: 'Visualizar Minha Unidade',
                icon: EyeIcon
            };
        } else {
            if (props.isAdmin) {
                return {
                    href: route('unidades.create'),
                    text: 'Cadastrar Minha Unidade',
                    icon: PlusIcon
                };
            }
        }
    }
    return null;
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link href="/dashboard">
                        <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                    </Link>
                    <div class="border-l border-white h-8 hidden sm:block"></div>
                    <span class="text-white text-xl font-serif pl-2 border-b-2 border-[#bea55a] pb-1 hidden sm:inline-block">ENGENHARIA</span>
                </div>
                <div class="text-white text-sm font-medium hidden md:block">
                    Sistema de Censo Anual <span class="bg-[#bea55a] text-black px-2 py-1 rounded">{{ new Date().getFullYear() }}</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensagem de boas-vindas -->
                <div 
                    ref="dashboardCard"
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 transition-all duration-300"
                >
                    <h1 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <BuildingOfficeIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                        {{ welcomeMessage }}
                    </h1>
                    
                    <!-- Conteúdo para Super Administradores -->
                    <div v-if="isSuperAdmin" class="mb-6">
                        <p class="text-lg mb-6 font-medium text-gray-900">Resumo do Sistema:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div 
                                ref="statCards"
                                class="bg-gray-50 p-6 rounded-lg shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div>
                                    <p class="text-gray-600 font-semibold">Total de Unidades:</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ unidadesCount }}</p>
                                </div>
                                <div class="h-14 w-14 bg-[#f5e6b8] rounded-lg flex items-center justify-center">
                                    <BuildingOfficeIcon class="h-8 w-8 text-[#816d33]" />
                                </div>
                            </div>
                            <div 
                                ref="statCards"
                                class="bg-amber-50 p-6 rounded-lg shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div>
                                    <p class="text-amber-800 font-semibold">Pendentes de Avaliação:</p>
                                    <p class="text-3xl font-bold text-amber-900 mt-2">{{ unidadesPendentes }}</p>
                                </div>
                                <div class="h-14 w-14 bg-amber-200 rounded-lg flex items-center justify-center">
                                    <ExclamationTriangleIcon class="h-8 w-8 text-amber-600" />
                                </div>
                            </div>
                        </div>
                        <div class="mt-10 flex flex-col sm:flex-row gap-4">
                            <Link 
                                ref="actionButton"
                                :href="route('admin.unidades.index')" 
                                class="inline-flex items-center justify-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-150"
                            >
                                <BuildingOfficeIcon class="h-5 w-5 mr-2 -ml-1 text-black" />
                                Gerenciar Unidades
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Administradores -->
                    <div v-if="isAdmin" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">
                            {{ unidadeCadastrada 
                                ? "Sua unidade já está cadastrada no sistema!" 
                                : "Você ainda não cadastrou sua unidade no censo anual."
                            }}
                        </p>
                        <div v-if="!unidadeCadastrada" class="p-5 rounded-lg bg-amber-50 text-amber-800 mb-6 flex items-start space-x-3 shadow-sm">
                            <ExclamationTriangleIcon class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-semibold">Ação Necessária</h3>
                                <p class="mt-1">Para participar do censo anual da engenharia, cadastre sua unidade no sistema.</p>
                            </div>
                        </div>
                        
                        <div v-else class="p-5 rounded-lg bg-emerald-50 text-emerald-800 mb-6 flex items-start space-x-3 shadow-sm">
                            <CheckCircleIcon class="h-6 w-6 text-emerald-500 flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-semibold">Cadastro Realizado</h3>
                                <p class="mt-1">Sua unidade está cadastrada. Você pode visualizar as informações a qualquer momento.</p>
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-center">
                            <Link 
                                v-if="getUnidadeLink" 
                                ref="actionButton"
                                :href="getUnidadeLink.href" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-300 transform hover:scale-105"
                            >
                                <component :is="getUnidadeLink.icon" class="h-5 w-5 mr-2 -ml-1 text-black" />
                                {{ getUnidadeLink.text }}
                                <ArrowRightIcon class="h-4 w-4 ml-2 animate-bounce-x" />
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Servidores -->
                    <div v-if="isServidor" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">Bem-vindo ao sistema de gerenciamento de unidades!</p>
                        <div v-if="unidadeCadastrada" class="p-5 rounded-lg bg-[#f5e6b8] text-gray-800 mb-6 flex items-start space-x-3 shadow-sm">
                            <CheckCircleIcon class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-semibold">Cadastro Ativo</h3>
                                <p class="mt-1">Sua unidade está cadastrada no sistema de censo anual.</p>
                            </div>
                        </div>
                        <div v-else class="p-5 rounded-lg bg-amber-50 text-amber-800 mb-6 flex items-start space-x-3 shadow-sm">
                            <ExclamationTriangleIcon class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-semibold">Atenção</h3>
                                <p class="mt-1">Sua unidade ainda não foi cadastrada no sistema. Por favor, entre em contato com um administrador.</p>
                            </div>
                        </div>
                        <div v-if="unidadeCadastrada" class="mt-8 flex justify-center">
                            <Link 
                                ref="actionButton"
                                :href="getUnidadeLink.href" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-300 transform hover:scale-105"
                            >
                                <component :is="getUnidadeLink.icon" class="h-5 w-5 mr-2 -ml-1 text-black" />
                                {{ getUnidadeLink.text }}
                                <ArrowRightIcon class="h-4 w-4 ml-2 animate-bounce-x" />
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- Card adicional com informações do sistema -->
                <div v-if="!isSuperAdmin" class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 transition-all duration-300">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <ClipboardDocumentListIcon class="h-5 w-5 mr-2 text-[#bea55a]" />
                        Sobre o Sistema
                    </h2>
                    <p class="text-gray-700 mb-4">
                        O Sistema de Censo Anual da Engenharia foi desenvolvido para coletar informações sobre as unidades policiais, 
                        permitindo melhor planejamento de reformas e adequações estruturais.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900 mb-2">Objetivos</h3>
                            <ul class="list-disc list-inside text-gray-700 space-y-1 text-sm">
                                <li>Mapeamento das estruturas físicas</li>
                                <li>Levantamento de necessidades</li>
                                <li>Planejamento de intervenções</li>
                                <li>Adequação às normas vigentes</li>
                            </ul>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900 mb-2">Benefícios</h3>
                            <ul class="list-disc list-inside text-gray-700 space-y-1 text-sm">
                                <li>Melhorias nas condições de trabalho</li>
                                <li>Otimização de recursos</li>
                                <li>Adequação às normas de acessibilidade</li>
                                <li>Priorização baseada em necessidades reais</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animações personalizadas */
@keyframes bounce-x {
    0%, 100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(3px);
    }
}

.animate-bounce-x {
    animation: bounce-x 1s infinite;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .grid-cols-1 {
        grid-template-columns: 1fr;
    }
}

/* Transições suaves */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Efeitos de hover melhorados */
.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.hover\:scale-105:hover {
    transform: scale(1.05);
}

.hover\:-translate-y-1:hover {
    transform: translateY(-4px);
}

/* Estilização adicional para as estatísticas */
.stat-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 140px;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.stat-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #4b5563;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-top: 0.5rem;
}

.stat-icon {
    height: 2.5rem;
    width: 2.5rem;
    color: #bea55a;
}

/* Print styles */
@media print {
    .print-hidden {
        display: none !important;
    }
    
    .print-break-inside-avoid {
        break-inside: avoid;
    }
}
</style>