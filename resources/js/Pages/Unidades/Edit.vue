<!-- resources/js/Pages/Unidades/Edit.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UnidadeDetailsForm from '@/Pages/Unidades/Partials/UnidadeDetailsForm.vue';
import LocalizacaoForm from '@/Pages/Unidades/Partials/LocalizacaoForm.vue';
import AcessibilidadeForm from '@/Pages/Unidades/Partials/AcessibilidadeForm.vue';
import InformacoesUnidadeForm from '@/Pages/Unidades/Partials/InformacoesUnidadeForm.vue';
import MidiasUnidadeForm from '@/Pages/Unidades/Partials/MidiasUnidadeForm.vue';
import { ref, watch } from 'vue';
import { usePage, Link, useForm, router } from '@inertiajs/vue3';

const page = usePage();
const activeTab = ref('dados-gerais');
const errorMessage = ref(null);

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    orgaos: Array,
    permissions: Object,
});

// Observar mensagens flash
watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        errorMessage.value = flash.success;
        setTimeout(() => { errorMessage.value = null }, 5000);
    } else if (flash.error) {
        errorMessage.value = flash.error;
        setTimeout(() => { errorMessage.value = null }, 5000);
    }
});

const tabs = [
    { id: 'dados-gerais', label: 'Dados Gerais', icon: 'fa-building' },
    { id: 'localizacao', label: 'Localização', icon: 'fa-map-marker-alt' },
    { id: 'acessibilidade', label: 'Acessibilidade', icon: 'fa-wheelchair' },
    { id: 'informacoes', label: 'Estruturais', icon: 'fa-info-circle' },
    { id: 'midias', label: 'Mídias', icon: 'fa-camera' },
];

// Função para mudar a aba
const changeTab = (tabId) => {
    errorMessage.value = null;
    activeTab.value = tabId;
};

// Função para lidar com o evento 'saved' dos componentes filhos
const handleSaved = (error = null) => {
    if (error) {
        errorMessage.value = error;
    } else {
        errorMessage.value = 'Dados salvos com sucesso!';
        
        // Aguardar um período e recarregar a página
        setTimeout(() => {
            // Recarregar a página atual preservando o histórico
            router.reload();
        }, 1500); // 1,5 segundos para mostrar a mensagem antes do refresh
    }
};
</script>

<template>
    <AppLayout title="Editar Unidade">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-10 sm:h-14 w-auto" />
                </Link>
                <div class="hidden sm:block border-l border-white h-8"></div>
                <span class="text-white text-sm sm:text-xl font-semibold pl-2 truncate">Editar Unidade: {{ unidade.nome }}</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 mb-2">
            <div class="flex justify-end">
                <Link :href="route('unidades.show', { team: team.id, unidade: unidade.id })" 
                    class="inline-flex items-center px-4 py-2 bg-black border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <i class="fas fa-edit mr-2"></i> Voltar para Visualização de Unidade
                </Link>
            </div>
        </div>
        
        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-4 sm:p-8 bg-white border-b border-gray-200">
                        <!-- Mensagem de erro ou sucesso -->
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

                        <!-- Tabs -->
                        <div class="border-b border-gray-200 overflow-x-auto pb-2">
                            <nav class="-mb-px flex space-x-4 sm:space-x-8" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="changeTab(tab.id)"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600 bg-indigo-50'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-3 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm rounded-t-md transition-all duration-200 flex items-center'
                                    ]"
                                    :aria-current="activeTab === tab.id ? 'page' : undefined"
                                >
                                    <i :class="`fas ${tab.icon} mr-1 sm:mr-2`"></i>
                                    <span class="hidden sm:inline">{{ tab.label }}</span>
                                    <span class="sm:hidden">{{ tab.label.split(' ')[0] }}</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Conteúdo das abas -->
                        <div class="mt-6 transition-all duration-300">
                            <div v-if="activeTab === 'dados-gerais'" class="animate-fade-in">
                                <UnidadeDetailsForm
                                    :team="team"
                                    :unidade="unidade"
                                    :orgaos="orgaos"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div v-if="activeTab === 'localizacao'" class="animate-fade-in">
                                <LocalizacaoForm
                                    :team="team"
                                    :unidade="unidade"
                                    :permissions="permissions"
                                    :is-new="false"
                                    :is-editable="true"
                                    @saved="handleSaved"
                                />
                            </div>

                            <div v-if="activeTab === 'acessibilidade'" class="animate-fade-in">
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

                            <div v-if="activeTab === 'informacoes'" class="animate-fade-in">
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

                            <div v-if="activeTab === 'midias'" class="animate-fade-in">
                                <MidiasUnidadeForm
                                    :unidade="unidade"
                                    :midias="midias"
                                    :permissions="permissions"
                                    :is-editable="true"
                                    :is-new="false"
                                    @saved="handleSaved"
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
        scrollbar-width: none;
    }
    
    .tabs-overflow::-webkit-scrollbar {
        display: none;
    }
}
</style>