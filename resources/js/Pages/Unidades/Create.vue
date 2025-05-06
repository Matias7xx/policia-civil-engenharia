<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UnidadeDetailsForm from '@/Pages/Unidades/Partials/UnidadeDetailsForm.vue';
import AcessibilidadeForm from '@/Pages/Unidades/Partials/AcessibilidadeForm.vue';
import InformacoesUnidadeForm from '@/Pages/Unidades/Partials/InformacoesUnidadeForm.vue';
import MidiasUnidadeForm from '@/Pages/Unidades/Partials/MidiasUnidadeForm.vue';
import { ref, watch, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

// Inicializa o completedTabs com base nos dados existentes
const initializeCompletedTabs = () => {
    completedTabs.value['dados-gerais'] = !!props.unidade?.id; // Verifica se há dados preenchidos
    completedTabs.value['acessibilidade'] = !!props.acessibilidade?.id; // verifica se acessibilidade foi salva
    completedTabs.value['informacoes'] = !!props.informacoes?.id; // verifica se informações foi salva
    completedTabs.value['midias'] = props.midias?.length > 0; // Verifica se há mídias
};

onMounted(() => {
    initializeCompletedTabs();
});

const page = usePage();
const activeTab = ref('dados-gerais');
const errorMessage = ref(null);
const completedTabs = ref({
    'dados-gerais': false,
    'acessibilidade': false,
    'informacoes': false,
    'midias': false,
});

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    permissions: Object,
});

// Observar mensagens flash
watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        errorMessage.value = flash.success; // Exibe a mensagem de sucesso
        setTimeout(() => { errorMessage.value = null }, 5000); // Desaparece após 5 segundos
    } else if (flash.error) {
        errorMessage.value = flash.error; // Exibe a mensagem de erro
        setTimeout(() => { errorMessage.value = null }, 5000); // Desaparece após 5 segundos
    }
});

const tabs = [
    { id: 'dados-gerais', label: 'Dados Gerais', icon: 'fa-building' },
    { id: 'acessibilidade', label: 'Acessibilidade', icon: 'fa-wheelchair' },
    { id: 'informacoes', label: 'Estruturais', icon: 'fa-info-circle' },
    { id: 'midias', label: 'Mídias', icon: 'fa-camera' },
];

// Função para verificar se uma aba pode ser acessada
const canAccessTab = (tabId) => {
    if (!props.unidade?.is_draft) return true; // Permite visualização se não for rascunho
    const tabOrder = ['dados-gerais', 'acessibilidade', 'informacoes', 'midias'];
    const currentIndex = tabOrder.indexOf(tabId);
    // Permite acesso à aba atual ou anteriores se já preenchidas
    return currentIndex === 0 || tabOrder.slice(0, currentIndex).every((tab) => completedTabs.value[tab]);
};

// Função para mudar a aba
const changeTab = (tabId) => {
    if (!canAccessTab(tabId)) {
        errorMessage.value = 'Preencha todas as abas anteriores antes de acessar esta aba.';
        return;
    }
    errorMessage.value = null;
    activeTab.value = tabId;
};

// Função para lidar com o evento 'saved' dos componentes filhos
const handleSaved = (nextTab, tabId, error = null) => {
    if (error) {
        errorMessage.value = error;
        return;
    }
    if (tabId) {
        completedTabs.value[tabId] = true;
    }
    if (nextTab) {
        activeTab.value = nextTab;
    }
    errorMessage.value = null;
};

// Função para lidar com o salvamento final da aba Mídias
const handleFinalSave = (nextTab, error = null) => {
    if (error) {
        errorMessage.value = error;
        return;
    }
    if (!allTabsCompleted()) {
        errorMessage.value = 'Todas as abas devem ser preenchidas antes de finalizar o cadastro.';
        return;
    }
    completedTabs.value['midias'] = true;
    errorMessage.value = 'Cadastro finalizado com sucesso!';
};

// Verifica se todas as abas estão completas
const allTabsCompleted = () => {
    return (
        completedTabs.value['dados-gerais'] &&
        completedTabs.value['acessibilidade'] &&
        completedTabs.value['informacoes']
    );
};

// Classe de progresso para exibir visualmente o status de cada etapa
const tabProgressClass = computed(() => (tabId) => {
    if (activeTab.value === tabId) {
        return 'border-indigo-500 text-indigo-600 bg-indigo-50';
    }
    
    if (completedTabs.value[tabId]) {
        return 'border-green-500 text-green-600 bg-green-50';
    }
    
    if (canAccessTab(tabId)) {
        return 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
    }
    
    return 'opacity-50 cursor-not-allowed text-gray-400 border-transparent';
});
</script>

<template>
    <AppLayout title="Criar Unidade">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-10 sm:h-14 w-auto" />
                </Link>
                <div class="hidden sm:block border-l border-white h-8"></div>
                <span class="text-white text-sm sm:text-xl font-semibold pl-2 truncate">Cadastrar Unidade: {{ unidade.nome }}</span>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-4 sm:p-8 bg-white border-b border-gray-200">
                        <!-- Indicador de progresso -->
                        <div class="mb-6">
                            <div class="text-center mb-2 text-sm text-gray-500">Progresso do cadastro</div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                    :style="{
                                        width: `${(Object.values(completedTabs).filter(Boolean).length / 4) * 100}%`
                                    }"></div>
                            </div>
                        </div>

                        <!-- Mensagem de erro ou sucesso - com animação de fade -->
                        <transition name="fade">
                            <div v-if="errorMessage" class="mb-4 p-4 rounded flex items-center" 
                                :class="errorMessage.includes('sucesso') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                <div v-if="errorMessage.includes('sucesso')" class="mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div v-else class="mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                {{ errorMessage }}
                            </div>
                        </transition>

                        <!-- Tabs - design responsivo para dispositivos móveis -->
                        <div class="border-b border-gray-200 overflow-x-auto pb-2">
                            <nav class="-mb-px flex space-x-4 sm:space-x-8" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="changeTab(tab.id)"
                                    :disabled="!canAccessTab(tab.id)"
                                    :class="[
                                        tabProgressClass(tab.id),
                                        'whitespace-nowrap py-3 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm rounded-t-md transition-all duration-200 flex items-center'
                                    ]"
                                    :aria-current="activeTab === tab.id ? 'page' : undefined"
                                >
                                    <i :class="`fas ${tab.icon} mr-1 sm:mr-2`"></i>
                                    <span class="hidden sm:inline">{{ tab.label }}</span>
                                    <span class="sm:hidden">{{ tab.label.split(' ')[0] }}</span>
                                    
                                    <!-- Ícone de concluído -->
                                    <svg v-if="completedTabs[tab.id]" class="ml-1 h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>

                        <!-- Conteúdo das abas -->
                        <div class="mt-6 transition-all duration-300">
                            <div v-if="activeTab === 'dados-gerais'" class="animate-fade-in">
                                <UnidadeDetailsForm
                                    :team="team"
                                    :unidade="unidade"
                                    :permissions="permissions"
                                    :is-new="!unidade?.id"
                                    :is-editable="unidade?.is_draft ?? true"
                                    @saved="(nextTab, error) => handleSaved(nextTab, 'dados-gerais', error)"
                                />
                            </div>

                            <div v-if="activeTab === 'acessibilidade'" class="animate-fade-in">
                                <AcessibilidadeForm
                                    :team="team"
                                    :unidade="unidade"
                                    :acessibilidade="acessibilidade"
                                    :permissions="permissions"
                                    :is-new="!unidade?.id"
                                    :is-editable="unidade?.is_draft ?? true"
                                    @saved="(nextTab, error) => handleSaved(nextTab, 'acessibilidade', error)"
                                />
                            </div>

                            <div v-if="activeTab === 'informacoes'" class="animate-fade-in">
                                <InformacoesUnidadeForm
                                    :team="team"
                                    :unidade="unidade"
                                    :informacoes="informacoes"
                                    :permissions="permissions"
                                    :is-new="!unidade?.id"
                                    :is-editable="unidade?.is_draft ?? true"
                                    @saved="(nextTab, error) => handleSaved(nextTab, 'informacoes', error)"
                                />
                            </div>

                            <div v-if="activeTab === 'midias'" class="animate-fade-in">
                                <MidiasUnidadeForm
                                    :unidade="unidade"
                                    :midias="midias"
                                    :permissions="permissions"
                                    :is-editable="unidade?.is_draft ?? true"
                                    @saved="handleFinalSave"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    0% { opacity: 0; }
    100% { opacity: 1; }
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