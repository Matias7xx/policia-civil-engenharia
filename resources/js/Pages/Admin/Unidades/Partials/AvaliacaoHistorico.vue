<script setup>
import { computed, ref, onMounted } from "vue";
import {
    CalendarIcon,
    UserIcon,
    DocumentTextIcon,
    PencilSquareIcon,
    ChartBarIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    avaliacoes: Array,
    unidade: Object,
    isSuperAdmin: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["new", "edit"]);

// Estado para exibição detalhada de uma avaliação
const selectedAvaliacao = ref(null);
const showDetails = ref(false);

onMounted(() => {
    if (props.avaliacoes && props.avaliacoes.length > 0) {
        selectedAvaliacao.value = sortedAvaliacoes.value[0];
    }
});

// Ordenar avaliações por data (mais recentes primeiro)
const sortedAvaliacoes = computed(() => {
    if (!props.avaliacoes || props.avaliacoes.length === 0) return [];
    return [...props.avaliacoes].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

// Função para formatar a data
const formatDate = (dateString) => {
    if (!dateString) return "Data não informada";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

// Função para obter a letra correspondente à nota
const getNotaLetra = (nota) => {
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return "";
    if (notaNum >= 9.5) return "A+";
    if (notaNum >= 9.0) return "A";
    if (notaNum >= 8.0) return "B";
    if (notaNum >= 7.0) return "C";
    if (notaNum >= 6.0) return "D";
    if (notaNum >= 5.0) return "E";
    if (notaNum >= 4.0) return "F";
    if (notaNum >= 3.0) return "G";
    if (notaNum >= 2.0) return "H";
    if (notaNum >= 1.0) return "I";
    return "J";
};

// Função para obter a classe CSS correspondente à nota
const getNotaClass = (nota) => {
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return "";
    if (notaNum >= 9.0) return "bg-green-600 text-white";
    if (notaNum >= 7.0) return "bg-green-500 text-white";
    if (notaNum >= 5.0) return "bg-yellow-500 text-white";
    if (notaNum >= 3.0) return "bg-orange-500 text-white";
    return "bg-red-500 text-white";
};

// Funcão do Modal de detalhes da avaliação
const toggleDetails = (avaliacao = null) => {
    if (avaliacao) {
        selectedAvaliacao.value = avaliacao;
        showDetails.value = true;
    } else {
        showDetails.value = !showDetails.value;
    }
};

// Calcula se temos dados históricos suficientes
const hasTrendData = computed(() => {
    return props.avaliacoes && props.avaliacoes.length >= 2;
});

const getTrend = (field) => {
    if (!hasTrendData.value) return "neutro";

    const last = parseFloat(sortedAvaliacoes.value[0][field] || 0);
    const previous = parseFloat(sortedAvaliacoes.value[1][field] || 0);

    if (last > previous) return "positivo";
    if (last < previous) return "negativo";
    return "neutro";
};

// Classe dos icons
const getTrendIconClass = (trend) => {
    switch (trend) {
        case "positivo":
            return "text-green-500 transform rotate-180";
        case "negativo":
            return "text-red-500";
        default:
            return "text-gray-400";
    }
};

const getTrendText = (field) => {
    const trend = getTrend(field);
    const last = parseFloat(sortedAvaliacoes.value[0][field] || 0);
    const previous = parseFloat(sortedAvaliacoes.value[1][field] || 0);
    const diff = Math.abs(last - previous).toFixed(1);

    switch (trend) {
        case "positivo":
            return `+${diff} desde última avaliação`;
        case "negativo":
            return `-${diff} desde última avaliação`;
        default:
            return "Sem alteração";
    }
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div
            class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center"
        >
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                <ChartBarIcon class="w-5 h-5 mr-2 text-gray-600" />
                Histórico de Avaliações
            </h3>
        </div>

        <!-- Se não houver avaliações -->
        <div
            v-if="!avaliacoes || avaliacoes.length === 0"
            class="text-center py-10 px-6 text-gray-500"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-12 h-12 mx-auto mb-3 text-gray-400"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <p>Não há avaliações registradas para esta unidade.</p>
            <p class="mt-2 text-sm">
                <span v-if="isSuperAdmin"
                    >Clique em "Nova Avaliação" para realizar a primeira
                    avaliação.</span
                >
                <span v-else
                    >Aguarde que um administrador realize a avaliação da
                    unidade.</span
                >
            </p>
        </div>

        <!-- Listagem de avaliações -->
        <div v-else class="divide-y divide-gray-200">
            <div
                v-for="(avaliacao, index) in sortedAvaliacoes"
                :key="avaliacao.id"
                class="p-4 hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                @click="toggleDetails(avaliacao)"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between"
                >
                    <!-- Cabeçalho da avaliação -->
                    <div class="flex items-center">
                        <div
                            class="mr-4 flex-shrink-0 size-10 rounded-full bg-[#bea55a] bg-opacity-20 flex items-center justify-center"
                        >
                            <DocumentTextIcon class="h-5 w-5 text-[#816d33]" />
                        </div>
                        <div>
                            <div class="font-medium">
                                Avaliação #{{ avaliacoes.length - index }}
                            </div>
                            <div
                                class="text-sm text-gray-500 flex items-center"
                            >
                                <CalendarIcon class="w-3.5 h-3.5 mr-1" />
                                {{ formatDate(avaliacao.created_at) }}
                                <span class="mx-2">•</span>
                                <UserIcon class="w-3.5 h-3.5 mr-1" />
                                {{
                                    avaliacao.avaliador?.name || "Não informado"
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Notas em formato de pills -->
                    <div class="flex flex-wrap gap-2 mt-3 sm:mt-0">
                        <div
                            class="p-1 bg-gray-100 rounded-full flex items-center"
                        >
                            <span class="text-xs text-gray-500 ml-1 mr-2"
                                >Geral:</span
                            >
                            <span
                                :class="getNotaClass(avaliacao.nota_geral)"
                                class="px-2 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ avaliacao.nota_geral }}
                                {{ getNotaLetra(avaliacao.nota_geral) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de detalhes -->
        <div
            v-if="showDetails && selectedAvaliacao"
            class="fixed inset-0 bg-black bg-opacity-50 z-[1000] flex items-center justify-center p-4"
        >
            <div
                class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
            >
                <div
                    class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center"
                >
                    <h3 class="text-lg font-medium text-gray-900">
                        Detalhes da Avaliação
                    </h3>
                    <button
                        @click="showDetails = false"
                        class="text-gray-400 hover:text-gray-500"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <!-- Informações básicas -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm text-gray-500">
                                Unidade avaliada em:
                                {{ formatDate(selectedAvaliacao.created_at) }}
                            </div>
                        </div>
                        <div class="text-sm flex items-center">
                            <UserIcon class="w-4 h-4 mr-2 text-gray-500" />
                            <span class="font-medium mr-1">Avaliador(a): </span>
                            {{
                                selectedAvaliacao.avaliador?.name ||
                                "Não informado"
                            }}
                        </div>
                    </div>

                    <!-- Notas em cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <div class="p-3 border rounded-lg">
                            <div class="text-xs text-gray-500 mb-1">
                                Nota Geral
                            </div>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold mr-2">{{
                                    selectedAvaliacao.nota_geral
                                }}</span>
                                <span
                                    :class="
                                        getNotaClass(
                                            selectedAvaliacao.nota_geral,
                                        )
                                    "
                                    class="text-xs font-medium p-1 px-2 rounded"
                                >
                                    {{
                                        getNotaLetra(
                                            selectedAvaliacao.nota_geral,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 border rounded-lg">
                            <div class="text-xs text-gray-500 mb-1">
                                Nota Estrutura
                            </div>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold mr-2">{{
                                    selectedAvaliacao.nota_estrutura
                                }}</span>
                                <span
                                    :class="
                                        getNotaClass(
                                            selectedAvaliacao.nota_estrutura,
                                        )
                                    "
                                    class="text-xs font-medium p-1 px-2 rounded"
                                >
                                    {{
                                        getNotaLetra(
                                            selectedAvaliacao.nota_estrutura,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 border rounded-lg">
                            <div class="text-xs text-gray-500 mb-1">
                                Nota Acessibilidade
                            </div>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold mr-2">{{
                                    selectedAvaliacao.nota_acessibilidade
                                }}</span>
                                <span
                                    :class="
                                        getNotaClass(
                                            selectedAvaliacao.nota_acessibilidade,
                                        )
                                    "
                                    class="text-xs font-medium p-1 px-2 rounded"
                                >
                                    {{
                                        getNotaLetra(
                                            selectedAvaliacao.nota_acessibilidade,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div v-if="selectedAvaliacao.observacoes" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                            Observações e Recomendações:
                        </h4>
                        <div
                            class="p-4 bg-gray-50 rounded-lg border text-sm text-gray-600 whitespace-pre-line"
                        >
                            {{ selectedAvaliacao.observacoes }}
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="flex justify-end gap-2 mt-6">
                        <button
                            @click="showDetails = false"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none transition"
                        >
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animações para o modal */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #bea55a;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a89043;
}
</style>
