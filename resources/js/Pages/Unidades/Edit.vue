<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import UnidadeDetailsForm from "@/Pages/Unidades/Partials/UnidadeDetailsForm.vue";
import LocalizacaoForm from "@/Pages/Unidades/Partials/LocalizacaoForm.vue";
import AcessibilidadeForm from "@/Pages/Unidades/Partials/AcessibilidadeForm.vue";
import InformacoesUnidadeForm from "@/Pages/Unidades/Partials/InformacoesUnidadeForm.vue";
import MidiasUnidadeForm from "@/Pages/Unidades/Partials/MidiasUnidadeForm.vue";
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "@/Composables/useToast";

const toast = useToast();
const activeTab = ref("dados-gerais");

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    orgaos: Array,
    unidades: Array,
    permissions: Object,
});

const tabs = [
    { id: "dados-gerais", label: "Dados Gerais", icon: "fa-building" },
    { id: "localizacao", label: "Localização", icon: "fa-map-marker-alt" },
    { id: "acessibilidade", label: "Acessibilidade", icon: "fa-wheelchair" },
    { id: "informacoes", label: "Estruturais", icon: "fa-info-circle" },
    { id: "midias", label: "Mídias", icon: "fa-camera" },
];

// Função para mudar a aba
const changeTab = (tabId) => {
    activeTab.value = tabId;
};

// Função para lidar com o evento 'saved' dos componentes filhos
const handleSaved = (error = null) => {
    if (error) {
        return;
    } else {
        // Aguardar um período e recarregar a página para mostrar dados atualizados
        /* setTimeout(() => {
            // Recarregar a página atual preservando
            router.reload({
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    // Toast adicional para confirmar atualização
                    toast.info('🔄 Dados atualizados com sucesso!');
                }
            });
        }, 1500); */
        // 1,5 segundos para mostrar a mensagem antes do refresh
    }
};

// Função para mostrar feedback de validação
const showValidationError = (message) => {
    toast.error(`⚠️ ${message}`);
};

// Função para mostrar feedback de sucesso
const showSuccessMessage = (message) => {
    toast.success(`✅ ${message}`);
};
</script>

<template>
    <AppLayout title="Editar Unidade">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img
                        src="/images/logo-pc-branca.png"
                        alt="Logo da Polícia Civil"
                        class="h-10 sm:h-14 w-auto"
                    />
                </Link>
                <div class="hidden sm:block border-l border-white h-8"></div>
                <span
                    class="text-white text-sm sm:text-xl font-semibold pl-2 truncate"
                    >Editar Unidade: {{ unidade.nome }}</span
                >
            </div>
        </template>

        <!-- Botão de voltar -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 mb-2">
            <div class="flex justify-end gap-2">
                <!-- Botão de visualização para Super Admin -->
                <Link
                    v-if="$page.props.auth.user?.isSuperAdmin"
                    :href="route('admin.unidades.show', unidade.id)"
                    class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#d4bf7a] focus:bg-[#d4bf7a] active:bg-[#a89043] focus:outline-none focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Visualização da Unidade
                </Link>

                <!-- Botão padrão para visualização USUÁRIO COMUM -->
                <Link
                    v-if="!$page.props.auth.user?.isSuperAdmin"
                    :href="
                        route('unidades.show', {
                            team: team.id,
                            unidade: unidade.id,
                        })
                    "
                    class="inline-flex items-center px-4 py-2 bg-black border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                    </svg>
                    Voltar para Visualização
                </Link>
            </div>
        </div>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-4 sm:p-8 bg-white border-b border-gray-200">
                        <!-- Aviso sobre edição -->
                        <div
                            class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-amber-600"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="mt-2 text-sm text-amber-700">
                                        <p>
                                            Você está alterando uma unidade já
                                            cadastrada.
                                            <span class="font-medium"
                                                >As alterações serão salvas
                                                imediatamente ao clicar em
                                                "Atualizar" em cada aba.</span
                                            >
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div
                            class="border-b border-gray-200 overflow-x-auto pb-2"
                        >
                            <nav
                                class="-mb-px flex space-x-4 sm:space-x-8"
                                aria-label="Tabs"
                            >
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="changeTab(tab.id)"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'border-amber-500 text-amber-600 bg-amber-50'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-3 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm rounded-t-md transition-all duration-200 flex items-center',
                                    ]"
                                    :aria-current="
                                        activeTab === tab.id
                                            ? 'page'
                                            : undefined
                                    "
                                >
                                    <i
                                        :class="`fas ${tab.icon} mr-1 sm:mr-2`"
                                    ></i>
                                    <span class="hidden sm:inline">{{
                                        tab.label
                                    }}</span>
                                    <span class="sm:hidden">{{
                                        tab.label.split(" ")[0]
                                    }}</span>

                                    <!-- Indicador de disponível para edição -->
                                    <svg
                                        class="ml-1 h-4 w-4 text-gray-600"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                        />
                                    </svg>
                                </button>
                            </nav>
                        </div>

                        <!-- Conteúdo das abas -->
                        <div class="mt-6 transition-all duration-300">
                            <div
                                v-if="activeTab === 'dados-gerais'"
                                class="animate-fade-in"
                            >
                                <UnidadeDetailsForm
                                    :team="team"
                                    :unidade="unidade"
                                    :orgaos="orgaos"
                                    :unidades="unidades"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div
                                v-if="activeTab === 'localizacao'"
                                class="animate-fade-in"
                            >
                                <LocalizacaoForm
                                    :team="team"
                                    :unidade="unidade"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div
                                v-if="activeTab === 'acessibilidade'"
                                class="animate-fade-in"
                            >
                                <AcessibilidadeForm
                                    :team="team"
                                    :unidade="unidade"
                                    :acessibilidade="acessibilidade"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div
                                v-if="activeTab === 'informacoes'"
                                class="animate-fade-in"
                            >
                                <InformacoesUnidadeForm
                                    :team="team"
                                    :unidade="unidade"
                                    :informacoes="informacoes"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div
                                v-if="activeTab === 'midias'"
                                class="animate-fade-in"
                            >
                                <MidiasUnidadeForm
                                    :unidade="unidade"
                                    :midias="midias"
                                    :acessibilidade="acessibilidade"
                                    :permissions="permissions"
                                    :is-editable="true"
                                    :is-new="false"
                                    @saved="handleSaved"
                                />
                            </div>
                        </div>

                        <!-- Dicas de uso -->
                        <div
                            class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4
                                        class="text-sm font-medium text-gray-800"
                                    >
                                        💡 Dicas de Edição
                                    </h4>
                                    <div class="mt-1 text-sm text-gray-600">
                                        <ul
                                            class="list-disc list-inside space-y-1"
                                        >
                                            <li>
                                                Cada aba pode ser editada
                                                independentemente
                                            </li>
                                            <li>
                                                As alterações são salvas
                                                imediatamente ao confirmar
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 640px) {
    .tabs-overflow {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .tabs-overflow::-webkit-scrollbar {
        display: none;
    }
}

/* Hover effects para as tabs */
.tab-hover:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
