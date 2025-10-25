<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    BuildingOfficeIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    EyeIcon,
    PencilIcon,
    PlusIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    ArrowRightIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    unidadeCadastrada: {
        type: Boolean,
        default: false,
    },
    isSuperAdmin: {
        type: Boolean,
        default: false,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    isServidor: {
        type: Boolean,
        default: false,
    },
    unidadesCount: {
        type: Number,
        default: 0,
    },
    unidadesPendentes: {
        type: Number,
        default: 0,
    },
    unidadeStatus: {
        type: String,
        default: null,
    },
    rejectionReason: {
        type: String,
        default: null,
    },
    isDraft: {
        // Novo prop para is_draft
        type: Boolean,
        default: false,
    },
});

const page = usePage();

const welcomeMessage = computed(() => {
    if (props.isSuperAdmin) {
        return "Bem-vindo(a) ao Painel de Administração";
    } else if (props.isAdmin) {
        return props.unidadeCadastrada
            ? "Obrigado por participar do Censo de Imóveis da DIERI-PCPB"
            : "Bem-vindo(a) ao Censo de Imóveis da DIERI-PCPB";
    } else {
        return "Bem-vindo(a) ao Censo de Imóveis da DIERI-PCPB";
    }
});

// Determinar a rota e texto do link com base no papel, unidade e is_draft
const getUnidadeLink = computed(() => {
    if (props.isAdmin || props.isServidor) {
        if (props.unidadeCadastrada) {
            const teamId = page.props.auth.user.current_team_id;
            const unidadeId = page.props.auth.unidade_id;
            return {
                href: route("unidades.show", {
                    team: teamId,
                    unidade: unidadeId,
                }),
                text: "Visualizar Minha Unidade",
                icon: EyeIcon,
            };
        } else {
            if (props.isAdmin) {
                return {
                    href: route("unidades.create"),
                    text: props.isDraft
                        ? "Continuar Cadastro da Unidade"
                        : "Cadastrar Minha Unidade",
                    icon: PlusIcon,
                };
            }
        }
    }
    return null;
});

const getEditUnidadeLink = computed(() => {
    if (props.isAdmin || props.isServidor) {
        if (props.unidadeCadastrada && props.unidadeStatus !== "aprovada") {
            // Só mostra o botão para editar se a unidade não estiver aprovada
            const teamId = page.props.auth.user.current_team_id;
            const unidadeId = page.props.auth.unidade_id;
            return {
                href: route("unidades.edit", {
                    team: teamId,
                    unidade: unidadeId,
                }),
                text: "Editar Informações",
                icon: PencilIcon,
            };
        }
    }
    return null;
});

// Novo computed para obter classe CSS e texto baseado no status
const statusInfo = computed(() => {
    switch (props.unidadeStatus) {
        case "pendente_avaliacao":
            return {
                class: "bg-yellow-50 text-yellow-800 border-yellow-200",
                icon: ExclamationTriangleIcon,
                iconColor: "text-yellow-500",
                title: "Pendente de Avaliação",
                message:
                    "Sua unidade está aguardando avaliação pela Engenharia.",
            };
        case "aprovada":
            return {
                class: "bg-green-50 text-green-800 border-green-200",
                icon: CheckCircleIcon,
                iconColor: "text-green-500",
                title: "Aprovado",
                message: "Seu formulário foi aprovado pela Engenharia.",
            };
        case "reprovada":
            return {
                class: "bg-red-50 text-red-800 border-red-200",
                icon: ExclamationTriangleIcon,
                iconColor: "text-red-500",
                title: "Reprovado",
                message: props.rejectionReason
                    ? `Seu formulário foi reprovado pelo seguinte motivo: "${props.rejectionReason}". Por favor, faça as correções necessárias.`
                    : "Seu formulário foi reprovado. Por favor, faça as correções necessárias.",
            };
        case "em_revisao":
            return {
                class: "bg-blue-50 text-blue-800 border-blue-200",
                icon: PencilIcon,
                iconColor: "text-blue-500",
                title: "Em Revisão",
                message: "Seu formulário está em processo de revisão.",
            };
        default:
            return {
                class: "bg-gray-50 text-gray-800 border-gray-200",
                icon: BuildingOfficeIcon,
                iconColor: "text-gray-500",
                title: "Status não definido",
                message: "O status da sua unidade ainda não foi definido.",
            };
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link href="/dashboard">
                        <img
                            src="/images/logo-pc-branca.png"
                            alt="Logo da Polícia Civil"
                            class="h-14 w-auto"
                        />
                    </Link>
                    <div
                        class="border-l border-white h-8 hidden sm:block"
                    ></div>
                    <span
                        class="text-white text-xl font-bold pl-2 border-b-2 border-[#bea55a] pb-1 hidden sm:inline-block"
                        >ENGENHARIA</span
                    >
                </div>
                <div class="text-white text-sm font-medium hidden md:block">
                    Censo de Imóveis
                    <span class="bg-[#bea55a] text-black px-2 py-1 rounded">{{
                        new Date().getFullYear()
                    }}</span>
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
                    <h1
                        class="text-2xl font-bold text-gray-900 mb-4 flex items-center"
                    >
                        <BuildingOfficeIcon
                            class="h-6 w-6 text-[#bea55a] mr-2"
                        />
                        {{ welcomeMessage }}
                    </h1>

                    <!-- Conteúdo para Super Administradores -->
                    <div v-if="isSuperAdmin" class="mb-6">
                        <p class="text-lg mb-6 font-medium text-gray-900">
                            Resumo do Sistema:
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                ref="statCards"
                                class="bg-gray-50 p-6 rounded-lg shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div>
                                    <p class="text-gray-600 font-semibold">
                                        Total de Unidades:
                                    </p>
                                    <p
                                        class="text-3xl font-bold text-gray-900 mt-2"
                                    >
                                        {{ unidadesCount }}
                                    </p>
                                </div>
                                <div
                                    class="h-14 w-14 bg-[#f5e6b8] rounded-lg flex items-center justify-center"
                                >
                                    <BuildingOfficeIcon
                                        class="h-8 w-8 text-[#816d33]"
                                    />
                                </div>
                            </div>
                            <div
                                ref="statCards"
                                class="bg-amber-50 p-6 rounded-lg shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div>
                                    <p class="text-amber-800 font-semibold">
                                        Pendentes de Avaliação:
                                    </p>
                                    <p
                                        class="text-3xl font-bold text-amber-900 mt-2"
                                    >
                                        {{ unidadesPendentes }}
                                    </p>
                                </div>
                                <div
                                    class="h-14 w-14 bg-amber-200 rounded-lg flex items-center justify-center"
                                >
                                    <ExclamationTriangleIcon
                                        class="h-8 w-8 text-amber-600"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="mt-10 flex flex-col sm:flex-row gap-4">
                            <Link
                                ref="actionButton"
                                :href="route('admin.unidades.index')"
                                class="inline-flex items-center justify-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-150"
                            >
                                <BuildingOfficeIcon
                                    class="h-5 w-5 mr-2 -ml-1 text-black"
                                />
                                Gerenciar Unidades
                            </Link>

                            <Link
                                ref="actionButton"
                                :href="route('admin.users.index')"
                                class="inline-flex items-center justify-center px-6 py-3 bg-black border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider hover:bg-gray-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-black active:bg-gray-900 transition ease-in-out duration-150"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-5 w-5 mr-2 -ml-1"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                                    />
                                </svg>
                                Gerenciar Usuários
                            </Link>

                            <Link
                                ref="actionButton"
                                :href="route('admin.orgaos.index')"
                                class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-100 active:bg-gray-200 transition ease-in-out duration-150"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-5 w-5 mr-2 -ml-1"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"
                                    />
                                </svg>
                                Gerenciar Órgãos
                            </Link>
                        </div>
                    </div>

                    <!-- Conteúdo para Administradores -->
                    <div v-if="isAdmin" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">
                            {{
                                unidadeCadastrada
                                    ? "Sua unidade já está cadastrada no sistema!"
                                    : "Sua unidade não está cadastrada no sistema."
                            }}
                        </p>
                        <div
                            v-if="!unidadeCadastrada"
                            class="p-5 rounded-lg bg-amber-50 text-amber-800 mb-6 flex items-start space-x-3 shadow-sm"
                        >
                            <ExclamationTriangleIcon
                                class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5"
                            />
                            <div>
                                <h3 class="font-semibold">Ação Necessária</h3>
                                <p class="mt-1">
                                    Para participar do censo, cadastre sua
                                    unidade.
                                </p>
                            </div>
                        </div>

                        <!-- Status da unidade, se estiver cadastrada -->
                        <div
                            v-if="unidadeCadastrada && unidadeStatus"
                            :class="[
                                'p-5 rounded-lg mb-6 flex items-start space-x-3 shadow-sm border',
                                statusInfo.class,
                            ]"
                        >
                            <component
                                :is="statusInfo.icon"
                                :class="[
                                    'h-6 w-6 flex-shrink-0 mt-0.5',
                                    statusInfo.iconColor,
                                ]"
                            />
                            <div>
                                <h3 class="font-semibold">
                                    Status: {{ statusInfo.title }}
                                </h3>
                                <p class="mt-1">{{ statusInfo.message }}</p>

                                <!-- Se houver um motivo de reprovação e o status for 'reprovada' -->
                                <div
                                    v-if="
                                        unidadeStatus === 'reprovada' &&
                                        rejectionReason
                                    "
                                    class="mt-3 p-3 bg-red-100 rounded-md"
                                >
                                    <h4 class="font-medium text-red-800 mb-1">
                                        Motivo da reprovação:
                                    </h4>
                                    <p class="text-red-700 text-sm">
                                        {{ rejectionReason }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="unidadeCadastrada"
                            class="p-5 rounded-lg bg-emerald-50 text-emerald-800 mb-6 flex items-start space-x-3 shadow-sm"
                        >
                            <CheckCircleIcon
                                class="h-6 w-6 text-emerald-500 flex-shrink-0 mt-0.5"
                            />
                            <div>
                                <h3 class="font-semibold">
                                    Cadastro Realizado
                                </h3>
                                <p class="mt-1">
                                    Sua unidade está cadastrada. Você pode
                                    visualizar as informações a qualquer
                                    momento.
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-8 flex justify-center space-x-4 flex-wrap"
                        >
                            <Link
                                v-if="getUnidadeLink"
                                ref="actionButton"
                                :href="getUnidadeLink.href"
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-300 transform hover:scale-105 mb-4"
                            >
                                <component
                                    :is="getUnidadeLink.icon"
                                    class="h-5 w-5 mr-2 -ml-1 text-black"
                                />
                                {{ getUnidadeLink.text }}
                            </Link>

                            <!-- Botão para editar a unidade (apenas se o status não for 'aprovada') -->
                            <Link
                                v-if="getEditUnidadeLink"
                                ref="editButton"
                                :href="getEditUnidadeLink.href"
                                class="inline-flex items-center px-6 py-3 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-wider hover:bg-gray-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 active:bg-gray-300 transition ease-in-out duration-300 transform hover:scale-105 mb-4"
                            >
                                <component
                                    :is="getEditUnidadeLink.icon"
                                    class="h-5 w-5 mr-2 -ml-1 text-gray-700"
                                />
                                {{ getEditUnidadeLink.text }}
                            </Link>
                        </div>
                    </div>

                    <!-- Conteúdo para Servidores -->
                    <div v-if="isServidor" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">
                            Bem-vindo ao Censo de Imóveis!
                        </p>

                        <!-- Status da unidade cadastrada -->
                        <div
                            v-if="unidadeCadastrada && unidadeStatus"
                            :class="[
                                'p-5 rounded-lg mb-6 flex items-start space-x-3 shadow-sm border',
                                statusInfo.class,
                            ]"
                        >
                            <component
                                :is="statusInfo.icon"
                                :class="[
                                    'h-6 w-6 flex-shrink-0 mt-0.5',
                                    statusInfo.iconColor,
                                ]"
                            />
                            <div>
                                <h3 class="font-semibold">
                                    Status: {{ statusInfo.title }}
                                </h3>
                                <p class="mt-1">{{ statusInfo.message }}</p>

                                <!-- Se houver um motivo de reprovação e o status for 'reprovada', exibimos em destaque -->
                                <div
                                    v-if="
                                        unidadeStatus === 'reprovada' &&
                                        rejectionReason
                                    "
                                    class="mt-3 p-3 bg-red-100 rounded-md"
                                >
                                    <h4 class="font-medium text-red-800 mb-1">
                                        Motivo da reprovação:
                                    </h4>
                                    <p class="text-red-700 text-sm">
                                        {{ rejectionReason }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="unidadeCadastrada"
                            class="p-5 rounded-lg bg-[#f5e6b8] text-gray-800 mb-6 flex items-start space-x-3 shadow-sm"
                        >
                            <CheckCircleIcon
                                class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5"
                            />
                            <div>
                                <h3 class="font-semibold">Cadastro Ativo</h3>
                                <p class="mt-1">
                                    Sua unidade está cadastrada no sistema.
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="p-5 rounded-lg bg-amber-50 text-amber-800 mb-6 flex items-start space-x-3 shadow-sm"
                        >
                            <ExclamationTriangleIcon
                                class="h-6 w-6 text-[#bea55a] flex-shrink-0 mt-0.5"
                            />
                            <div>
                                <h3 class="font-semibold">Atenção</h3>
                                <p class="mt-1">
                                    Sua unidade ainda não foi cadastrada no
                                    sistema. Por favor, entre em contato com um
                                    administrador.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="unidadeCadastrada"
                            class="mt-8 flex justify-center space-x-4 flex-wrap"
                        >
                            <Link
                                ref="actionButton"
                                :href="getUnidadeLink.href"
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] active:bg-[#a89043] transition ease-in-out duration-300 transform hover:scale-105 mb-4"
                            >
                                <component
                                    :is="getUnidadeLink.icon"
                                    class="h-5 w-5 mr-2 -ml-1 text-black"
                                />
                                {{ getUnidadeLink.text }}
                            </Link>

                            <!-- Botão para editar a unidade (apenas se o status não for 'aprovada') -->
                            <Link
                                v-if="getEditUnidadeLink"
                                ref="editButton"
                                :href="getEditUnidadeLink.href"
                                class="inline-flex items-center px-6 py-3 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-wider hover:bg-gray-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 active:bg-gray-300 transition ease-in-out duration-300 transform hover:scale-105 mb-4"
                            >
                                <component
                                    :is="getEditUnidadeLink.icon"
                                    class="h-5 w-5 mr-2 -ml-1 text-gray-700"
                                />
                                {{ getEditUnidadeLink.text }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Card adicional com informações do sistema -->
                <div
                    v-if="!isSuperAdmin"
                    class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 transition-all duration-300"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 mb-4 flex items-center"
                    >
                        <ClipboardDocumentListIcon
                            class="h-5 w-5 mr-2 text-[#bea55a]"
                        />
                        Sobre o Sistema
                    </h2>
                    <p class="text-gray-700 mb-4">
                        O Sistema de Censo de Imóveis da Diretoria de Engenharia
                        e Recursos Imobiliários foi desenvolvido para coletar
                        informações sobre as unidades policiais, permitindo
                        melhor planejamento de reformas e adequações
                        estruturais.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900 mb-2">
                                Objetivos
                            </h3>
                            <ul
                                class="list-disc list-inside text-gray-700 space-y-1 text-sm"
                            >
                                <li>Mapeamento das estruturas físicas</li>
                                <li>Levantamento de necessidades</li>
                                <li>Planejamento de intervenções</li>
                                <li>Adequação às normas vigentes</li>
                            </ul>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900 mb-2">
                                Benefícios
                            </h3>
                            <ul
                                class="list-disc list-inside text-gray-700 space-y-1 text-sm"
                            >
                                <li>Melhorias nas condições de trabalho</li>
                                <li>Otimização de recursos</li>
                                <li>Adequação às normas de acessibilidade</li>
                                <li>
                                    Priorização baseada em necessidades reais
                                </li>
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
    0%,
    100% {
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
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
