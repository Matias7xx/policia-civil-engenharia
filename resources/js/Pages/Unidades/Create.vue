<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import UnidadeDetailsForm from "@/Pages/Unidades/Partials/UnidadeDetailsForm.vue";
import LocalizacaoForm from "@/Pages/Unidades/Partials/LocalizacaoForm.vue";
import AcessibilidadeForm from "@/Pages/Unidades/Partials/AcessibilidadeForm.vue";
import InformacoesUnidadeForm from "@/Pages/Unidades/Partials/InformacoesUnidadeForm.vue";
import MidiasUnidadeForm from "@/Pages/Unidades/Partials/MidiasUnidadeForm.vue";
import { ref, watch, computed, onMounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { useToast } from "@/Composables/useToast";

const page = usePage();
const toast = useToast();

// Inicializa o completedTabs com base nos dados existentes
const initializeCompletedTabs = () => {
    const telefoneOk = props.unidade?.sem_telefone || !!props.unidade?.telefone_1;
    const dadosGeraisCompleted =
        props.unidade?.id &&
        props.unidade?.nome &&
        props.unidade?.tipo_estrutural &&
        props.unidade?.tipo_judicial &&
        telefoneOk &&
        props.unidade?.numero_medidor_agua &&
        props.unidade?.numero_medidor_energia;
    completedTabs.value["dados-gerais"] = !!dadosGeraisCompleted;
 
    const localizacaoCompleted =
        props.unidade?.id &&
        props.unidade?.cidade &&
        props.unidade?.cep &&
        props.unidade?.rua &&
        props.unidade?.numero &&
        props.unidade?.bairro &&
        props.unidade?.latitude &&
        props.unidade?.longitude;
    completedTabs.value["localizacao"] = !!localizacaoCompleted;
 
    const accFieldOk = (v) => typeof v === "boolean" || v === null;
    const acessibilidadeCompleted =
        props.acessibilidade?.id &&
        accFieldOk(props.acessibilidade.rampa_acesso) &&
        accFieldOk(props.acessibilidade.corrimao) &&
        accFieldOk(props.acessibilidade.piso_tatil) &&
        accFieldOk(props.acessibilidade.banheiro_adaptado) &&
        accFieldOk(props.acessibilidade.elevador) &&
        accFieldOk(props.acessibilidade.sinalizacao_braile);
    completedTabs.value["acessibilidade"] = !!acessibilidadeCompleted;
 
    const inf = props.informacoes;
    const informacoesCompleted =
        inf?.id &&
        inf?.pavimentacao_rua &&
        (inf?.sem_caixa_dagua      || inf?.caixa_dagua      !== undefined) &&
        (inf?.sem_internet_cabeada || inf?.internet_cabeada !== undefined) &&
        (inf?.sem_telefone_fixo    || inf?.telefone_fixo    !== undefined);
    completedTabs.value["informacoes"] = !!informacoesCompleted;
 
    const requiredMediaTypes = [
        "foto_frente","foto_lateral_1","foto_lateral_2",
        "foto_fundos","foto_medidor_agua","foto_medidor_energia",
    ];
    const existingMediaTypes = props.midias?.map((m) => m.midia_tipo?.nome) || [];
    const allRequiredMediaPresent = requiredMediaTypes.every((t) => existingMediaTypes.includes(t));
    completedTabs.value["midias"] = props.midias?.length > 0 && allRequiredMediaPresent;
};

onMounted(() => {
    initializeCompletedTabs();
});

const activeTab = ref("dados-gerais");
const completedTabs = ref({
    "dados-gerais": false,
    localizacao: false,
    acessibilidade: false,
    informacoes: false,
    midias: false,
});

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

// Função para verificar se uma aba pode ser acessada
const canAccessTab = (tabId) => {
    if (!props.unidade?.is_draft) return true; // Permite visualização se não for rascunho
    const tabOrder = [
        "dados-gerais",
        "localizacao",
        "acessibilidade",
        "informacoes",
        "midias",
    ];
    const currentIndex = tabOrder.indexOf(tabId);
    // Permite acesso à aba atual ou anteriores se já preenchidas
    const canAccess =
        currentIndex === 0 ||
        tabOrder
            .slice(0, currentIndex)
            .every((tab) => completedTabs.value[tab]);
    return canAccess;
};

// Função para lidar com o evento 'saved' dos componentes filhos
const handleSaved = (error, nextTab, tabId) => {
    /* if (error) {
        // Toast de erro
        toast.error('❌ : ' + error);
        completedTabs.value[tabId] = false;
        return;
    } */

    // Se não houver erro, atualizamos o estado de completedTabs
    initializeCompletedTabs();

    // Definir próxima aba com base na aba atual se nextTab não foi fornecido
    if (!nextTab && tabId) {
        const tabOrder = [
            "dados-gerais",
            "localizacao",
            "acessibilidade",
            "informacoes",
            "midias",
        ];
        const currentIndex = tabOrder.indexOf(tabId);
        if (currentIndex < tabOrder.length - 1) {
            nextTab = tabOrder[currentIndex + 1];
        }
    }

    if (nextTab && canAccessTab(nextTab)) {
        // Toast informativo sobre próxima aba
        setTimeout(() => {
            toast.info(
                `ℹ️ Avançando para: ${tabs.find((t) => t.id === nextTab)?.label}`,
            );
        }, 1000);

        activeTab.value = nextTab;
    }
};

// Função para lidar com o salvamento final da aba Mídias
const handleFinalSave = (nextTab, error = null) => {
    /* if (error) {
        toast.error('❌ Erro ao finalizar cadastro: ' + error);
        completedTabs.value['midias'] = false;
        return;
    } */

    if (!allTabsCompleted()) {
        toast.error(
            "❌ Todas as abas devem ser preenchidas antes de finalizar o cadastro.",
        );
        completedTabs.value["midias"] = false;
        return;
    }
};

// Função para mudar a aba com toast
const changeTab = (tabId) => {
    if (!canAccessTab(tabId)) {
        toast.warning(
            "⚠️ Preencha todas as abas anteriores antes de acessar esta aba.",
        );
        return;
    }

    activeTab.value = tabId;
};

// Verifica se todas as abas estão completas
const allTabsCompleted = () => {
    const allCompleted =
        completedTabs.value["dados-gerais"] &&
        completedTabs.value["localizacao"] &&
        completedTabs.value["acessibilidade"] &&
        completedTabs.value["informacoes"];
    return allCompleted;
};

// Classe de progresso para exibir visualmente o status de cada etapa
const tabProgressClass = computed(() => (tabId) => {
    if (activeTab.value === tabId) {
        return "border-amber-500 text-amber-600 bg-amber-50";
    }

    if (completedTabs.value[tabId]) {
        return "border-gray-500 text-gray-600 bg-gray-50";
    }

    if (canAccessTab(tabId)) {
        return "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
    }

    return "opacity-50 cursor-not-allowed text-gray-400 border-transparent";
});
</script>

<template>
    <AppLayout title="Criar Unidade">
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
                    >Cadastrar Unidade: {{ unidade.nome }}</span
                >
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-4 sm:p-8 bg-white border-b border-gray-200">
                        <!-- Indicador de progresso -->
                        <div class="mb-6">
                            <div class="text-center mb-2 text-sm text-gray-500">
                                Progresso do cadastro
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="bg-[#bea55a] h-2.5 rounded-full transition-all duration-300"
                                    :style="{
                                        width: `${(Object.values(completedTabs).filter(Boolean).length / 5) * 100}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Aviso sobre salvamento automático -->
                        <div
                            v-if="unidade?.is_draft"
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
                                    <h3
                                        class="text-sm font-medium text-amber-800"
                                    >
                                        💾 Informações são salvas após cada
                                        etapa
                                    </h3>
                                    <div class="mt-2 text-sm text-amber-700">
                                        <p>
                                            Você pode preencher o formulário por
                                            etapas. Todas as informações
                                            inseridas ficam salvas, permitindo
                                            que você continue de onde parou em
                                            outro momento.
                                            <span class="font-medium"
                                                >Apenas após finalizar todas as
                                                abas o formulário será enviado
                                                para avaliação.</span
                                            >
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs - design responsivo para dispositivos móveis -->
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
                                    :disabled="!canAccessTab(tab.id)"
                                    :class="[
                                        tabProgressClass(tab.id),
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

                                    <!-- Ícone de concluído -->
                                    <svg
                                        v-if="completedTabs[tab.id]"
                                        class="ml-1 h-4 w-4 text-gray-800"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </nav>
                        </div>

                        <!-- Conteúdo das abas -->
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
                                :is-new="!unidade?.id"
                                :is-editable="unidade?.is_draft ?? true"
                                @saved="
                                    (error, nextTab) =>
                                        handleSaved(
                                            error,
                                            nextTab,
                                            'dados-gerais',
                                        )
                                "
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
                                :is-new="!unidade?.id"
                                :is-editable="unidade?.is_draft ?? true"
                                @saved="
                                    (error, nextTab) =>
                                        handleSaved(
                                            error,
                                            nextTab,
                                            'localizacao',
                                        )
                                "
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
                                :is-new="!unidade?.id"
                                :is-editable="unidade?.is_draft ?? true"
                                @saved="
                                    (error, nextTab) =>
                                        handleSaved(
                                            error,
                                            nextTab,
                                            'acessibilidade',
                                        )
                                "
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
                                :is-new="!unidade?.id"
                                :is-editable="unidade?.is_draft ?? true"
                                @saved="
                                    (error, nextTab) =>
                                        handleSaved(
                                            error,
                                            nextTab,
                                            'informacoes',
                                        )
                                "
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
                                :is-editable="unidade?.is_draft ?? true"
                                @saved="
                                    (error, nextTab) =>
                                        handleFinalSave(nextTab, error)
                                "
                            />
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
    }
    100% {
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .tabs-overflow {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
    }

    .tabs-overflow::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Edge */
    }
}
</style>