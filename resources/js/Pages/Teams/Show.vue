<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TeamMemberManager from '@/Pages/Teams/Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';
import UnidadeDetailsForm from '@/Pages/Teams/Partials/UnidadeDetailsForm.vue';
import AcessibilidadeForm from '@/Pages/Teams/Partials/AcessibilidadeForm.vue';
import InformacoesUnidadeForm from '@/Pages/Teams/Partials/InformacoesUnidadeForm.vue';
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    team: Object,
    availableRoles: Array,
    permissions: Object,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    avaliacoes: Array,
});

// Verifica se a unidade já tem dados cadastrados
const hasUnidadeDetails = computed(() => props.unidade && Object.keys(props.unidade).length > 0);
const hasAcessibilidade = computed(() => props.acessibilidade && Object.keys(props.acessibilidade).length > 0);
const hasInformacoes = computed(() => props.informacoes && Object.keys(props.informacoes).length > 0);

// Controle das abas
const activeTab = ref('dados-gerais');

// Métodos para navegar entre as abas
const setTab = (tab) => {
    activeTab.value = tab;
};
</script>

<template>
    <AppLayout title="Detalhes da Unidade Policial">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                    <div class="border-l border-white h-8"></div>
                    <span class="text-white font-serif text-xl font-semibold pl-2">{{ team.name }} - Detalhes da Unidade Policial</span>
                </div>
                <div v-if="unidade && unidade.status" class="flex items-center">
                    <span 
                        class="px-3 py-1 rounded-full text-sm font-semibold"
                        :class="{
                            'bg-yellow-100 text-yellow-800': unidade.status === 'pendente_avaliacao',
                            'bg-green-100 text-green-800': unidade.status === 'aprovada',
                            'bg-red-100 text-red-800': unidade.status === 'reprovada',
                            'bg-blue-100 text-blue-800': unidade.status === 'em_revisao'
                        }"
                    >
                        {{ 
                            unidade.status === 'pendente_avaliacao' ? 'Pendente de Avaliação' :
                            unidade.status === 'aprovada' ? 'Aprovada' :
                            unidade.status === 'reprovada' ? 'Reprovada' :
                            unidade.status === 'em_revisao' ? 'Em Revisão' : 'Desconhecido'
                        }}
                    </span>
                </div>
            </div>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <!-- Menu de abas -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="setTab('dados-gerais')"
                            :class="[
                                activeTab === 'dados-gerais' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Dados Gerais
                        </button>
                        <button
                            @click="setTab('acessibilidade')"
                            :class="[
                                activeTab === 'acessibilidade' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Acessibilidade
                        </button>
                        <button
                            @click="setTab('informacoes-estruturais')"
                            :class="[
                                activeTab === 'informacoes-estruturais' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Informações Estruturais
                        </button>
                        <button
                            @click="setTab('servidores')"
                            :class="[
                                activeTab === 'servidores' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Servidores
                        </button>
                        <button
                            v-if="permissions.canDeleteTeam && !team.personal_team"
                            @click="setTab('configuracoes')"
                            :class="[
                                activeTab === 'configuracoes' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Configurações
                        </button>
                    </nav>
                </div>

                <!-- Conteúdo das abas -->
                <div v-if="activeTab === 'dados-gerais'">
                    <UpdateTeamNameForm :team="team" :permissions="permissions" />
                    <SectionBorder />
                    <UnidadeDetailsForm 
                        :team="team" 
                        :unidade="unidade" 
                        :permissions="permissions"
                        :isNew="!hasUnidadeDetails"
                    />
                </div>

                <div v-if="activeTab === 'acessibilidade'">
                    <AcessibilidadeForm 
                        :team="team" 
                        :acessibilidade="acessibilidade" 
                        :permissions="permissions"
                        :isNew="!hasAcessibilidade"
                    />
                </div>

                <div v-if="activeTab === 'informacoes-estruturais'">
                    <InformacoesUnidadeForm 
                        :team="team" 
                        :informacoes="informacoes" 
                        :permissions="permissions"
                        :isNew="!hasInformacoes"
                    />
                </div>

                <div v-if="activeTab === 'servidores'">
                    <TeamMemberManager
                        class="mt-10 sm:mt-0"
                        :team="team"
                        :available-roles="availableRoles"
                        :user-permissions="permissions"
                    />
                </div>

                <div v-if="activeTab === 'configuracoes' && permissions.canDeleteTeam && !team.personal_team">
                    <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>