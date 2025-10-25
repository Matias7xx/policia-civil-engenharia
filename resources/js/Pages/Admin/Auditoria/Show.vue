<template>
    <AppLayout title="Detalhes da Auditoria">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header com informações da unidade -->
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6"
                >
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1
                                    class="text-2xl font-bold text-gray-900 flex items-center"
                                >
                                    <DocumentMagnifyingGlassIcon
                                        class="h-8 w-8 mr-3 text-[#bea55a]"
                                    />
                                    Auditoria da Unidade
                                </h1>
                                <p class="text-gray-600 mt-1">
                                    {{ unidade.nome }}
                                </p>
                                <div
                                    class="mt-2 flex items-center space-x-4 text-sm text-gray-500"
                                >
                                    <span>ID: {{ unidade.id }}</span>
                                    <span
                                        >Código:
                                        {{ unidade.codigo || "N/A" }}</span
                                    >
                                    <span
                                        >Cidade:
                                        {{ unidade.cidade || "N/A" }}</span
                                    >
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('admin.auditoria.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition"
                                >
                                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                                    Voltar
                                </Link>
                                <Link
                                    :href="
                                        route('admin.unidades.show', unidade.id)
                                    "
                                    class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-[#d4bf7a] transition"
                                >
                                    <EyeIcon class="h-4 w-4 mr-2" />
                                    Ver Unidade
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ChartBarIcon
                                        class="h-6 w-6 text-gray-400"
                                    />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Total de Ações
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ auditorias.length }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <PlusCircleIcon
                                        class="h-6 w-6 text-green-400"
                                    />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Criações
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ contarEventos("Criação") }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <PencilIcon class="h-6 w-6 text-blue-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Atualizações
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ contarEventos("Atualização") }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <TrashIcon class="h-6 w-6 text-red-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Exclusões
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ contarEventos("Exclusão") }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros por tipo -->
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Filtrar por Tipo de Alteração
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="filtroTipo = null"
                                :class="[
                                    'px-3 py-1 text-xs font-medium rounded-full transition',
                                    filtroTipo === null
                                        ? 'bg-[#bea55a] text-black'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                ]"
                            >
                                Todos ({{ auditorias.length }})
                            </button>
                            <button
                                v-for="(count, tipo) in tiposDisponiveis"
                                :key="tipo"
                                @click="filtroTipo = tipo"
                                :class="[
                                    'px-3 py-1 text-xs font-medium rounded-full transition',
                                    filtroTipo === tipo
                                        ? 'bg-[#bea55a] text-black'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                ]"
                            >
                                {{ tipo }} ({{ count }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Timeline de auditorias -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">
                            Histórico Detalhado
                            <span
                                v-if="
                                    auditoriasFiltradas.length !==
                                    auditorias.length
                                "
                                class="text-sm text-gray-500"
                            >
                                ({{ auditoriasFiltradas.length }} de
                                {{ auditorias.length }} registros)
                            </span>
                        </h3>

                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <li
                                    v-for="(
                                        auditoria, index
                                    ) in auditoriasFiltradas"
                                    :key="auditoria.id"
                                    class="relative pb-8"
                                >
                                    <!-- Linha conectora -->
                                    <span
                                        v-if="
                                            index !==
                                            auditoriasFiltradas.length - 1
                                        "
                                        class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                                        aria-hidden="true"
                                    />

                                    <div
                                        class="relative flex items-start space-x-3"
                                    >
                                        <!-- Ícone do evento -->
                                        <div class="relative">
                                            <span
                                                :class="[
                                                    'h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-white',
                                                    getEventoIconClass(
                                                        auditoria.evento,
                                                    ),
                                                ]"
                                            >
                                                <component
                                                    :is="
                                                        getEventoIcon(
                                                            auditoria.evento,
                                                        )
                                                    "
                                                    class="h-5 w-5"
                                                />
                                            </span>
                                        </div>

                                        <!-- Conteúdo -->
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="bg-gray-50 rounded-lg p-4"
                                            >
                                                <!-- Cabeçalho da auditoria -->
                                                <div
                                                    class="flex items-center justify-between mb-3"
                                                >
                                                    <div
                                                        class="flex items-center space-x-3"
                                                    >
                                                        <span
                                                            :class="
                                                                getEventoClass(
                                                                    auditoria.evento,
                                                                )
                                                            "
                                                            class="px-3 py-1 text-xs font-semibold rounded-full"
                                                        >
                                                            {{
                                                                auditoria.evento
                                                            }}
                                                        </span>
                                                        <span
                                                            class="text-sm font-medium text-gray-900"
                                                        >
                                                            {{
                                                                auditoria.modelo_tipo
                                                            }}
                                                        </span>
                                                        <span
                                                            v-if="
                                                                auditoria.descricao_item
                                                            "
                                                            class="text-sm text-gray-600"
                                                        >
                                                            -
                                                            {{
                                                                auditoria.descricao_item
                                                            }}
                                                        </span>
                                                    </div>
                                                    <time
                                                        class="text-sm text-gray-500"
                                                    >
                                                        {{
                                                            auditoria.data_hora
                                                        }}
                                                    </time>
                                                </div>

                                                <!-- Informações do usuário -->
                                                <div
                                                    class="flex items-center space-x-4 text-sm text-gray-600 mb-3"
                                                >
                                                    <div
                                                        class="flex items-center"
                                                    >
                                                        <UserIcon
                                                            class="h-4 w-4 mr-1"
                                                        />
                                                        {{ auditoria.usuario }}
                                                        <span
                                                            v-if="
                                                                auditoria.usuario_matricula
                                                            "
                                                            class="ml-1 text-xs text-gray-500"
                                                        >
                                                            ({{
                                                                auditoria.usuario_matricula
                                                            }})
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-if="
                                                            auditoria.ip_address
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <GlobeAltIcon
                                                            class="h-4 w-4 mr-1"
                                                        />
                                                        {{
                                                            auditoria.ip_address
                                                        }}
                                                    </div>
                                                </div>

                                                <!-- Alterações -->
                                                <div
                                                    v-if="
                                                        auditoria.alteracoes
                                                            .length > 0
                                                    "
                                                    class="mb-3"
                                                >
                                                    <h4
                                                        class="text-sm font-medium text-gray-700 mb-2"
                                                    >
                                                        Alterações realizadas:
                                                    </h4>
                                                    <div class="space-y-3">
                                                        <div
                                                            v-for="alteracao in auditoria.alteracoes"
                                                            :key="
                                                                alteracao.campo
                                                            "
                                                            class="bg-white rounded border border-gray-200 p-3"
                                                        >
                                                            <div
                                                                class="font-medium text-gray-800 mb-2"
                                                            >
                                                                {{
                                                                    alteracao.campo
                                                                }}
                                                            </div>
                                                            <div
                                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                                            >
                                                                <div
                                                                    v-if="
                                                                        alteracao.valor_antigo !==
                                                                        null
                                                                    "
                                                                    class="text-red-600"
                                                                >
                                                                    <div
                                                                        class="text-xs font-medium mb-1"
                                                                    >
                                                                        Valor
                                                                        Anterior:
                                                                    </div>
                                                                    <div
                                                                        class="bg-red-50 border border-red-200 rounded px-2 py-1 text-sm"
                                                                    >
                                                                        {{
                                                                            formatarValor(
                                                                                alteracao.valor_antigo,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="text-green-600"
                                                                >
                                                                    <div
                                                                        class="text-xs font-medium mb-1"
                                                                    >
                                                                        {{
                                                                            alteracao.valor_antigo !==
                                                                            null
                                                                                ? "Novo Valor"
                                                                                : "Valor"
                                                                        }}:
                                                                    </div>
                                                                    <div
                                                                        class="bg-green-50 border border-green-200 rounded px-2 py-1 text-sm"
                                                                    >
                                                                        {{
                                                                            formatarValor(
                                                                                alteracao.valor_novo,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Detalhes técnicos -->
                                                <div
                                                    class="border-t border-gray-200 pt-3"
                                                >
                                                    <button
                                                        @click="
                                                            toggleDetalhes(
                                                                auditoria.id,
                                                            )
                                                        "
                                                        class="flex items-center text-xs text-gray-500 hover:text-gray-700"
                                                    >
                                                        <ChevronDownIcon
                                                            :class="[
                                                                'h-4 w-4 mr-1 transition-transform',
                                                                detalhesExpandidos[
                                                                    auditoria.id
                                                                ]
                                                                    ? 'rotate-180'
                                                                    : '',
                                                            ]"
                                                        />
                                                        {{
                                                            detalhesExpandidos[
                                                                auditoria.id
                                                            ]
                                                                ? "Ocultar"
                                                                : "Ver"
                                                        }}
                                                        detalhes técnicos
                                                    </button>

                                                    <div
                                                        v-if="
                                                            detalhesExpandidos[
                                                                auditoria.id
                                                            ]
                                                        "
                                                        class="mt-2 text-xs text-gray-600 space-y-1"
                                                    >
                                                        <div>
                                                            <strong
                                                                >ID da
                                                                Auditoria:</strong
                                                            >
                                                            {{ auditoria.id }}
                                                        </div>
                                                        <div>
                                                            <strong
                                                                >Modelo:</strong
                                                            >
                                                            {{
                                                                auditoria.modelo_tipo
                                                            }}
                                                            (ID:
                                                            {{
                                                                auditoria.modelo_id
                                                            }})
                                                        </div>
                                                        <div
                                                            v-if="auditoria.url"
                                                        >
                                                            <strong
                                                                >URL:</strong
                                                            >
                                                            {{ auditoria.url }}
                                                        </div>
                                                        <div
                                                            v-if="
                                                                auditoria.user_agent
                                                            "
                                                        >
                                                            <strong
                                                                >User
                                                                Agent:</strong
                                                            >
                                                            {{
                                                                auditoria.user_agent
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Estado vazio -->
                        <div
                            v-if="auditoriasFiltradas.length === 0"
                            class="text-center py-12"
                        >
                            <DocumentMagnifyingGlassIcon
                                class="mx-auto h-12 w-12 text-gray-400"
                            />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                Nenhuma auditoria encontrada
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Não há registros de auditoria para o filtro
                                selecionado.
                            </p>
                            <button
                                @click="filtroTipo = null"
                                class="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-[#bea55a] bg-[#bea55a]/10 hover:bg-[#bea55a]/20 transition"
                            >
                                Mostrar Todos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    DocumentMagnifyingGlassIcon,
    ArrowLeftIcon,
    EyeIcon,
    ChartBarIcon,
    PlusCircleIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
    GlobeAltIcon,
    ChevronDownIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    unidade: Object,
    auditorias: Array,
});

const filtroTipo = ref(null);
const detalhesExpandidos = ref({});

const auditoriasFiltradas = computed(() => {
    if (!filtroTipo.value) return props.auditorias;
    return props.auditorias.filter(
        (auditoria) => auditoria.modelo_tipo === filtroTipo.value,
    );
});

const tiposDisponiveis = computed(() => {
    const tipos = {};
    props.auditorias.forEach((auditoria) => {
        tipos[auditoria.modelo_tipo] = (tipos[auditoria.modelo_tipo] || 0) + 1;
    });
    return tipos;
});

const contarEventos = (evento) => {
    return props.auditorias.filter((auditoria) => auditoria.evento === evento)
        .length;
};

const getEventoClass = (evento) => {
    const classes = {
        Criação: "bg-green-100 text-green-800",
        Atualização: "bg-blue-100 text-blue-800",
        Exclusão: "bg-red-100 text-red-800",
        Restauração: "bg-yellow-100 text-yellow-800",
    };
    return classes[evento] || "bg-gray-100 text-gray-800";
};

const getEventoIconClass = (evento) => {
    const classes = {
        Criação: "bg-green-500",
        Atualização: "bg-blue-500",
        Exclusão: "bg-red-500",
        Restauração: "bg-yellow-500",
    };
    return classes[evento] || "bg-gray-500";
};

const getEventoIcon = (evento) => {
    const icons = {
        Criação: PlusCircleIcon,
        Atualização: PencilIcon,
        Exclusão: TrashIcon,
        Restauração: ArrowLeftIcon,
    };
    return icons[evento] || DocumentMagnifyingGlassIcon;
};

const formatarValor = (valor) => {
    if (valor === null || valor === undefined) return "Não Informado";
    if (typeof valor === "boolean") return valor ? "Sim" : "Não";
    if (typeof valor === "object") return JSON.stringify(valor);
    return String(valor);
};

const toggleDetalhes = (id) => {
    detalhesExpandidos.value[id] = !detalhesExpandidos.value[id];
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
