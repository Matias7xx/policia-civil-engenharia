<template>
    <AppLayout title="Sistema de Auditoria">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
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
                                    Auditoria do Sistema
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Filtros
                        </h3>
                        <form
                            @submit.prevent="aplicarFiltros"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4"
                        >
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Usuário</label
                                >
                                <select
                                    v-model="filtros.usuario_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                >
                                    <option value="">Todos os usuários</option>
                                    <option
                                        v-for="usuario in usuarios"
                                        :key="usuario.id"
                                        :value="usuario.id"
                                    >
                                        {{ usuario.name }} ({{
                                            usuario.matricula
                                        }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Evento</label
                                >
                                <select
                                    v-model="filtros.evento"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                >
                                    <option value="">Todos os eventos</option>
                                    <option value="created">Criação</option>
                                    <option value="updated">Atualização</option>
                                    <option value="deleted">Exclusão</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Tipo de Modelo</label
                                >
                                <select
                                    v-model="filtros.modelo_tipo"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                >
                                    <option value="">Todos os tipos</option>
                                    <option
                                        v-for="(label, value) in modeloTipos"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Unidade</label
                                >
                                <select
                                    v-model="filtros.unidade_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                >
                                    <option value="">Todas as unidades</option>
                                    <option
                                        v-for="unidade in unidades"
                                        :key="unidade.id"
                                        :value="unidade.id"
                                    >
                                        {{ unidade.nome }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Data Início</label
                                >
                                <input
                                    type="date"
                                    v-model="filtros.data_inicio"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Data Fim</label
                                >
                                <input
                                    type="date"
                                    v-model="filtros.data_fim"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                />
                            </div>

                            <div class="flex items-end space-x-2">
                                <button
                                    type="submit"
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-[#d4bf7a] transition"
                                >
                                    <MagnifyingGlassIcon class="h-4 w-4 mr-1" />
                                    Filtrar
                                </button>
                                <button
                                    type="button"
                                    @click="limparFiltros"
                                    class="px-3 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition"
                                    title="Limpar filtros"
                                >
                                    <XMarkIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Auditorias -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Histórico de Ações ({{ auditorias.total }}
                                registros)
                            </h3>
                        </div>

                        <!-- Timeline de auditorias -->
                        <div class="space-y-4">
                            <div
                                v-for="auditoria in auditorias.data"
                                :key="auditoria.id"
                                class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Cabeçalho -->
                                        <div
                                            class="flex items-center space-x-3 mb-2"
                                        >
                                            <span
                                                :class="
                                                    getEventoClass(
                                                        auditoria.evento,
                                                    )
                                                "
                                                class="px-3 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ auditoria.evento }}
                                            </span>
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{
                                                    auditoria.unidade_nome ||
                                                    "Unidade não identificada"
                                                }}
                                            </span>
                                            <span
                                                v-if="auditoria.unidade_id"
                                                class="text-xs text-gray-500"
                                            >
                                                ID: {{ auditoria.unidade_id }}
                                            </span>
                                            <span
                                                class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded"
                                            >
                                                {{ auditoria.modelo_tipo }}
                                            </span>
                                        </div>

                                        <!-- Informações do usuário -->
                                        <div
                                            class="flex items-center space-x-4 text-sm text-gray-600 mb-3"
                                        >
                                            <div class="flex items-center">
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
                                            <div class="flex items-center">
                                                <ClockIcon
                                                    class="h-4 w-4 mr-1"
                                                />
                                                {{ auditoria.data_hora }}
                                            </div>
                                            <div
                                                v-if="auditoria.ip_address"
                                                class="flex items-center"
                                            >
                                                <GlobeAltIcon
                                                    class="h-4 w-4 mr-1"
                                                />
                                                {{ auditoria.ip_address }}
                                            </div>
                                        </div>

                                        <!-- Alterações -->
                                        <div
                                            v-if="
                                                auditoria.alteracoes.length > 0
                                            "
                                            class="bg-gray-50 rounded-lg p-3 mb-3"
                                        >
                                            <h4
                                                class="text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Alterações realizadas:
                                            </h4>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="alteracao in auditoria.alteracoes.slice(
                                                        0,
                                                        3,
                                                    )"
                                                    :key="alteracao.campo"
                                                    class="text-sm"
                                                >
                                                    <span
                                                        class="font-medium text-gray-800"
                                                        >{{
                                                            alteracao.campo
                                                        }}:</span
                                                    >
                                                    <div
                                                        class="ml-4 grid grid-cols-1 md:grid-cols-2 gap-2 mt-1"
                                                    >
                                                        <div
                                                            v-if="
                                                                alteracao.valor_antigo !==
                                                                null
                                                            "
                                                            class="text-red-600"
                                                        >
                                                            <span
                                                                class="font-medium"
                                                                >Antes:</span
                                                            >
                                                            <span
                                                                class="bg-red-100 px-2 py-1 rounded text-xs"
                                                            >
                                                                {{
                                                                    alteracao.valor_antigo ||
                                                                    "Não Informado"
                                                                }}
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="text-green-600"
                                                        >
                                                            <span
                                                                class="font-medium"
                                                                >{{
                                                                    alteracao.valor_antigo !==
                                                                    null
                                                                        ? "Depois"
                                                                        : ""
                                                                }}</span
                                                            >
                                                            <span
                                                                class="bg-green-100 px-2 py-1 rounded text-xs"
                                                            >
                                                                {{
                                                                    alteracao.valor_novo ||
                                                                    "Não Informado"
                                                                }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="
                                                        auditoria.alteracoes
                                                            .length > 3
                                                    "
                                                    class="text-xs text-gray-500"
                                                >
                                                    +
                                                    {{
                                                        auditoria.alteracoes
                                                            .length - 3
                                                    }}
                                                    alterações adicionais
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ações -->
                                    <div class="flex items-center space-x-2">
                                        <Link
                                            v-if="auditoria.unidade_id"
                                            :href="
                                                route(
                                                    'admin.auditoria.show',
                                                    auditoria.unidade_id,
                                                )
                                            "
                                            class="text-[#bea55a] hover:text-[#d4bf7a] text-sm font-medium"
                                            title="Ver histórico completo da auditoria desta unidade"
                                        >
                                            Ver Detalhes
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado vazio -->
                            <div
                                v-if="auditorias.data.length === 0"
                                class="text-center py-12"
                            >
                                <DocumentMagnifyingGlassIcon
                                    class="mx-auto h-12 w-12 text-gray-400"
                                />
                                <h3
                                    class="mt-2 text-sm font-medium text-gray-900"
                                >
                                    Nenhuma auditoria encontrada
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Não há registros de auditoria com os filtros
                                    aplicados.
                                </p>
                            </div>
                        </div>

                        <!-- Paginação -->
                        <div
                            v-if="
                                auditorias.links && auditorias.data.length > 0
                            "
                            class="mt-6"
                        >
                            <nav class="flex items-center justify-between">
                                <div
                                    class="flex-1 flex justify-between sm:hidden"
                                >
                                    <Link
                                        v-if="auditorias.prev_page_url"
                                        :href="auditorias.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Anterior
                                    </Link>
                                    <Link
                                        v-if="auditorias.next_page_url"
                                        :href="auditorias.next_page_url"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Próxima
                                    </Link>
                                </div>
                                <div
                                    class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Mostrando
                                            <span class="font-medium">{{
                                                auditorias.from
                                            }}</span>
                                            a
                                            <span class="font-medium">{{
                                                auditorias.to
                                            }}</span>
                                            de
                                            <span class="font-medium">{{
                                                auditorias.total
                                            }}</span>
                                            resultados
                                        </p>
                                    </div>
                                    <div>
                                        <nav
                                            class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination"
                                        >
                                            <Link
                                                v-if="auditorias.prev_page_url"
                                                :href="auditorias.prev_page_url"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <span class="sr-only"
                                                    >Anterior</span
                                                >
                                                <ChevronLeftIcon
                                                    class="h-5 w-5"
                                                />
                                            </Link>

                                            <template
                                                v-for="link in auditorias.links"
                                                :key="link.label"
                                            >
                                                <Link
                                                    v-if="
                                                        link.url &&
                                                        !link.label.includes(
                                                            'Previous',
                                                        ) &&
                                                        !link.label.includes(
                                                            'Next',
                                                        )
                                                    "
                                                    :href="link.url"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                        link.active
                                                            ? 'z-10 bg-[#bea55a] border-[#bea55a] text-black'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                    ]"
                                                    v-html="link.label"
                                                />
                                                <span
                                                    v-else-if="
                                                        !link.url &&
                                                        !link.label.includes(
                                                            'Previous',
                                                        ) &&
                                                        !link.label.includes(
                                                            'Next',
                                                        )
                                                    "
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                                    v-html="link.label"
                                                />
                                            </template>

                                            <Link
                                                v-if="auditorias.next_page_url"
                                                :href="auditorias.next_page_url"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <span class="sr-only"
                                                    >Próxima</span
                                                >
                                                <ChevronRightIcon
                                                    class="h-5 w-5"
                                                />
                                            </Link>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    DocumentMagnifyingGlassIcon,
    ChartBarIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    UserIcon,
    ClockIcon,
    GlobeAltIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    auditorias: Object,
    usuarios: Array,
    unidades: Array,
    modeloTipos: Object,
    filtros: Object,
});

const filtros = reactive({
    usuario_id: props.filtros?.usuario_id || "",
    evento: props.filtros?.evento || "",
    data_inicio: props.filtros?.data_inicio || "",
    data_fim: props.filtros?.data_fim || "",
    unidade_id: props.filtros?.unidade_id || "",
    modelo_tipo: props.filtros?.modelo_tipo || "",
});

const aplicarFiltros = () => {
    router.get(route("admin.auditoria.index"), filtros, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limparFiltros = () => {
    Object.keys(filtros).forEach((key) => {
        filtros[key] = "";
    });
    aplicarFiltros();
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
</script>

<style scoped>
.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 15px;
    top: 40px;
    bottom: -20px;
    width: 2px;
    background-color: #e5e7eb;
}
</style>
