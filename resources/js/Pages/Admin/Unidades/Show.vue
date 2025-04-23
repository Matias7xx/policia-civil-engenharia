<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AvaliacaoForm from '@/Pages/Admin/Unidades/Partials/AvaliacaoForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';

const props = defineProps({
    unidade: Object,
    team: Object,
    acessibilidade: Object,
    informacoes: Object,
    avaliacao: Object,
    avaliacoes: Array,
});

// Aba ativa
const activeTab = ref('resumo');

// Métodos para navegar entre as abas
const setTab = (tab) => {
    activeTab.value = tab;
};

// Verificar se a unidade tem avaliação
const hasAvaliacao = computed(() => props.avaliacao !== null);

// Formatar os valores de status
const formatStatus = (status) => {
    switch(status) {
        case 'pendente_avaliacao':
            return 'Pendente de Avaliação';
        case 'aprovada':
            return 'Aprovada';
        case 'reprovada':
            return 'Reprovada';
        case 'em_revisao':
            return 'Em Revisão';
        default:
            return 'Desconhecido';
    }
};

// Classe CSS para status
const getStatusClass = (status) => {
    switch(status) {
        case 'pendente_avaliacao':
            return 'bg-yellow-100 text-yellow-800';
        case 'aprovada':
            return 'bg-green-100 text-green-800';
        case 'reprovada':
            return 'bg-red-100 text-red-800';
        case 'em_revisao':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Verificar se diferentes seções de dados estão preenchidas
const hasAcessibilidadeData = computed(() => {
    return props.acessibilidade !== null;
});

const hasInformacoesData = computed(() => {
    return props.informacoes !== null;
});

// Método para ver os detalhes de uma avaliação
const showAvaliacao = (id) => {
    // Implementar navegação para a página de detalhes da avaliação
    console.log(`Ver detalhes da avaliação ${id}`);
    // Aqui poderia redirecionar para uma página específica de avaliação
    // window.location.href = `/admin/avaliacoes/${id}`;
};
</script>

<template>
    <AppLayout :title="`Unidade: ${unidade.nome}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Unidade: {{ unidade.nome }}
                </h2>
                <div class="flex items-center">
                    <span 
                        class="px-3 py-1 rounded-full text-sm font-semibold mr-4"
                        :class="getStatusClass(unidade.status)"
                    >
                        {{ formatStatus(unidade.status) }}
                    </span>
                    <Link 
                        :href="route('admin.unidades.index')" 
                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 active:bg-gray-300 focus:outline-none focus:bg-gray-200 transition ease-in-out duration-150"
                    >
                        Voltar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Menu de abas -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6 pt-6">
                            <button
                                @click="setTab('resumo')"
                                :class="[
                                    activeTab === 'resumo' 
                                        ? 'border-indigo-500 text-indigo-600' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Resumo
                            </button>
                            <button
                                @click="setTab('dados')"
                                :class="[
                                    activeTab === 'dados' 
                                        ? 'border-indigo-500 text-indigo-600' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Dados Cadastrais
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
                                @click="setTab('estrutura')"
                                :class="[
                                    activeTab === 'estrutura' 
                                        ? 'border-indigo-500 text-indigo-600' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Estrutura
                            </button>
                            <button
                                @click="setTab('avaliacoes')"
                                :class="[
                                    activeTab === 'avaliacoes' 
                                        ? 'border-indigo-500 text-indigo-600' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Avaliações
                            </button>
                        </nav>
                    </div>

                    <!-- Conteúdo das abas -->
                    <div class="p-6">
                        <!-- Aba de Resumo -->
                        <div v-if="activeTab === 'resumo'">
                            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Informações da Unidade -->
                                <div class="bg-white overflow-hidden shadow rounded-lg border">
                                    <div class="px-4 py-5 sm:p-6">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Informações da Unidade
                                        </h3>
                                        <div class="border-t border-gray-200 pt-4">
                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Nome</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ unidade.nome }}</dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Código</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ unidade.codigo || 'Não informado' }}</dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Tipo Estrutural</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ unidade.tipo_estrutural || 'Não informado' }}</dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Cadastro em</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ unidade.created_at }}</dd>
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <dt class="text-sm font-medium text-gray-500">Endereço</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">
                                                        <div v-if="unidade.rua">
                                                            {{ unidade.rua }}, {{ unidade.numero || 'S/N' }}
                                                            <div v-if="unidade.bairro || unidade.cidade_id">
                                                                {{ unidade.bairro || '' }} {{ unidade.cidade_id ? '- Cidade ' + unidade.cidade_id : '' }}
                                                            </div>
                                                            <div v-if="unidade.cep">
                                                                CEP: {{ unidade.cep }}
                                                            </div>
                                                        </div>
                                                        <div v-else>Endereço não informado</div>
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status e Avaliação -->
                                <div class="bg-white overflow-hidden shadow rounded-lg border">
                                    <div class="px-4 py-5 sm:p-6">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Status e Avaliação
                                        </h3>
                                        <div class="border-t border-gray-200 pt-4">
                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                                    <dd class="mt-1">
                                                        <span 
                                                            class="px-2 py-1 text-xs inline-flex leading-5 font-semibold rounded-full"
                                                            :class="getStatusClass(unidade.status)"
                                                        >
                                                            {{ formatStatus(unidade.status) }}
                                                        </span>
                                                    </dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Última Atualização</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ unidade.updated_at }}</dd>
                                                </div>
                                                <div v-if="hasAvaliacao" class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Nota Geral</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ avaliacao.nota_geral }}</dd>
                                                </div>
                                                <div v-if="hasAvaliacao" class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Última Avaliação</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ avaliacao.created_at }}</dd>
                                                </div>
                                                <div v-if="!hasAvaliacao" class="sm:col-span-2">
                                                    <dt class="text-sm font-medium text-gray-500">Avaliação</dt>
                                                    <dd class="mt-1 text-sm text-red-600">
                                                        Esta unidade ainda não foi avaliada.
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Responsável -->
                            <div class="bg-white overflow-hidden shadow rounded-lg border mb-6">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Responsável
                                    </h3>
                                    <div class="border-t border-gray-200 pt-4">
                                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                            <div class="sm:col-span-1">
                                                <dt class="text-sm font-medium text-gray-500">Nome</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ team.owner.name }}</dd>
                                            </div>
                                            <div class="sm:col-span-1">
                                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ team.owner.email }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- Links Rápidos -->
                            <div class="flex space-x-4">
                                <Link 
                                    :href="route('teams.show', team.id)" 
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:bg-indigo-700 transition ease-in-out duration-150"
                                >
                                    Editar Unidade
                                </Link>
                                <button 
                                    @click="setTab('avaliacoes')" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:bg-green-700 transition ease-in-out duration-150"
                                >
                                    {{ hasAvaliacao ? 'Atualizar Avaliação' : 'Avaliar Unidade' }}
                                </button>
                            </div>
                        </div>

                        <!-- Aba de Dados Cadastrais -->
                        <div v-if="activeTab === 'dados'">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Dados Cadastrais
                            </h3>
                            
                            <div class="bg-white overflow-hidden rounded-lg">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Nome</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.nome }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Código</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.codigo || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Tipo Estrutural</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.tipo_estrutural || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">SRPC</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.srpc || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">DSPC</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.dspc || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Nível</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.nivel || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Sede</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.sede ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <dt class="text-sm font-medium text-gray-500">Endereço</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <div v-if="unidade.rua">
                                                {{ unidade.rua }}, {{ unidade.numero || 'S/N' }}
                                                <div v-if="unidade.bairro || unidade.cidade_id">
                                                    {{ unidade.bairro || '' }} {{ unidade.cidade_id ? '- Cidade ' + unidade.cidade_id : '' }}
                                                </div>
                                                <div v-if="unidade.cep">
                                                    CEP: {{ unidade.cep }}
                                                </div>
                                                <div v-if="unidade.complemento">
                                                    Complemento: {{ unidade.complemento }}
                                                </div>
                                            </div>
                                            <div v-else>Endereço não informado</div>
                                        </dd>
                                    </div>
                                    
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.email || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Telefone Principal</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.telefone_1 || 'Não informado' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Telefone Secundário</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ unidade.telefone_2 || 'Não informado' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Aba de Acessibilidade -->
                        <div v-if="activeTab === 'acessibilidade'">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Informações de Acessibilidade
                            </h3>
                            
                            <div v-if="!hasAcessibilidadeData" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Não há informações de acessibilidade cadastradas para esta unidade.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="bg-white overflow-hidden rounded-lg">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Rampa de Acesso</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.rampa_acesso ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Corrimão</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.corrimao ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Piso Tátil</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.piso_tatil ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Banheiro Adaptado</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.banheiro_adaptado ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Elevador</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.elevador ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Sinalização em Braile</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.sinalizacao_braile ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                    <div v-if="acessibilidade.observacoes" class="sm:col-span-3">
                                        <dt class="text-sm font-medium text-gray-500">Observações</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ acessibilidade.observacoes }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Aba de Estrutura -->
                        <div v-if="activeTab === 'estrutura'">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Informações Estruturais
                            </h3>
                            
                            <div v-if="!hasInformacoesData" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Não há informações estruturais cadastradas para esta unidade.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else>
                                <!-- Características da via e serviços -->
                                <div class="mb-8">
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Características da via e serviços
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Pavimentação da Rua</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.pavimentacao_rua || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Padrão de Energia</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.padrao_energia || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Subestação</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.subestacao || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Gerador de Energia</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.gerador_energia || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Para-raio</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.para_raio || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Caixa D'água</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.caixa_dagua || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Internet Cabeada</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.internet_cabeada || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Provedor de Internet</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.internet_provedor || 'Não informado' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Características do imóvel -->
                                <div class="mb-8">
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Características do imóvel
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Tipo de Imóvel</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.tipo_imovel || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Contrato de Locação</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.contrato_locacao_id ? 'Sim' : 'Não' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Responsável (Locação/Cessão)</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.responsavel_locacao_cessao || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Escritura Pública</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.escritura_publica || 'Não informado' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Características estruturais -->
                                <div class="mb-8">
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Características estruturais
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Quantidade de Pavimentos</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_pavimentos || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Cercado/Muros</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.cercado_muros ? 'Sim' : 'Não' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Estacionamento Interno</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.estacionamento_interno ? 'Sim' : 'Não' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Estacionamento Externo</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.estacionamento_externo ? 'Sim' : 'Não' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Recuo Frontal</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.recuo_frontal ? informacoes.recuo_frontal + ' m' : 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Recuo Lateral</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.recuo_lateral ? informacoes.recuo_lateral + ' m' : 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Recuo Fundos</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.recuo_fundos ? informacoes.recuo_fundos + ' m' : 'Não informado' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Quantitativos de espaços -->
                                <div class="mb-8">
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Quantitativos de espaços e instalações
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Recepção</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_recepcao || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">WC Público</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_wc_publico || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Gabinetes</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_gabinetes || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Sala de Oitiva</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_sala_oitiva || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">WC Servidores</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_wc_servidores || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Alojamento Masculino</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_alojamento_masculino || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Alojamento Feminino</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_alojamento_feminino || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Celas/Carceragem</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_celas_carceragem || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Sala de Identificação</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_sala_identificacao || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Cozinha</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_cozinha || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Área de Serviço</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_area_servico || '0' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Depósito de Apreensão</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.qtd_deposito_apreensao || '0' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Acabamentos -->
                                <div class="mb-8">
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Acabamentos
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Piso</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.piso || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Parede</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.parede || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Esquadrias</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.esquadrias || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Louças e Metais</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.loucas_metais || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Forro/Laje</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.forro_lage || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Cobertura</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.cobertura || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Pintura</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.pintura || 'Não informado' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Equipamentos de segurança -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-700 mb-3 border-b pb-2">
                                        Equipamentos de segurança
                                    </h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Extintor Pó Químico</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.extintor_po_quimico || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Extintor CO2</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.extintor_co2 || 'Não informado' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Extintor Água</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ informacoes.extintor_agua || 'Não informado' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Aba de Avaliações -->
                        <div v-if="activeTab === 'avaliacoes'">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Avaliações
                            </h3>
                            
                            <!-- Formulário de avaliação -->
                            <AvaliacaoForm :unidade="unidade" :avaliacao="avaliacao" />
                            
                            <SectionBorder />
                            
                            <!-- Histórico de avaliações -->
                            <div class="mt-8">
                                <h4 class="text-md font-medium text-gray-700 mb-3">
                                    Histórico de Avaliações
                                </h4>
                                
                                <div v-if="avaliacoes && avaliacoes.length > 0">
                                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                        <ul role="list" class="divide-y divide-gray-200">
                                            <li v-for="item in avaliacoes" :key="item.id">
                                                <div class="px-4 py-4 sm:px-6">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                                            Avaliação #{{ item.id }}
                                                        </p>
                                                        <div class="ml-2 flex-shrink-0 flex">
                                                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                Nota: {{ item.nota_geral }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 sm:flex sm:justify-between">
                                                        <div class="sm:flex">
                                                            <p class="flex items-center text-sm text-gray-500">
                                                                {{ item.observacoes ? item.observacoes.substring(0, 100) + '...' : 'Sem observações' }}
                                                            </p>
                                                        </div>
                                                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                                            <p>
                                                                Avaliado em {{ item.created_at }}
                                                            </p>
                                                            <button 
                                                                @click="showAvaliacao(item.id)"
                                                                class="ml-4 inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                            >
                                                                Ver Detalhes
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div v-else class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                Não há histórico de avaliações para esta unidade.
                                            </p>
                                        </div>
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