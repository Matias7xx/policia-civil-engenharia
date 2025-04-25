<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Ícones do Heroicons
import { BuildingOfficeIcon, ExclamationTriangleIcon, CheckCircleIcon, EyeIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    unidadeCadastrada: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    isServidor: {
        type: Boolean,
        default: false
    },
    unidadesCount: {
        type: Number,
        default: 0
    },
    unidadesPendentes: {
        type: Number,
        default: 0
    }
});

const welcomeMessage = computed(() => {
    if (props.isSuperAdmin) {
        return "Bem-vindo ao Sistema de Censo Anual da Engenharia";
    } else if (props.isAdmin) {
        return props.unidadeCadastrada 
            ? "Obrigado por participar do Censo Anual da Engenharia" 
            : "Bem-vindo ao Sistema de Censo Anual da Engenharia";
    } else {
        return "Bem-vindo ao Sistema de Censo Anual da Engenharia";
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-serif pl-2 border-b-2 border-[#bea55a] pb-1">ENGENHARIA</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensagem de boas-vindas -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4 flex items-center font-serif">
                        <BuildingOfficeIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                        {{ welcomeMessage }}
                    </h1>
                    
                    <!-- Conteúdo para Super Administradores -->
                    <div v-if="isSuperAdmin" class="mb-6">
                        <p class="text-lg mb-4 font-medium text-gray-900">Resumo do Sistema:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-100 p-4 rounded-lg flex items-center justify-between">
                                <div>
                                    <p class="text-gray-800 font-semibold">Total de Unidades:</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ unidadesCount }}</p>
                                </div>
                                <BuildingOfficeIcon class="h-8 w-8 text-[#bea55a]" />
                            </div>
                            <div class="bg-amber-50 p-4 rounded-lg flex items-center justify-between">
                                <div>
                                    <p class="text-amber-800 font-semibold">Pendentes de Avaliação:</p>
                                    <p class="text-2xl font-bold text-amber-900">{{ unidadesPendentes }}</p>
                                </div>
                                <ExclamationTriangleIcon class="h-8 w-8 text-[#bea55a]" />
                            </div>
                        </div>
                        <div class="mt-6">
                            <Link 
                                :href="route('admin.unidades.index')" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150"
                            >
                                <BuildingOfficeIcon class="h-5 w-5 mr-2 -ml-1 text-black" />
                                Gerenciar Unidades
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Administradores com Unidade Cadastrada -->
                    <div v-else-if="isAdmin && unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">Sua unidade policial já está cadastrada no sistema!</p>
                        <div class="mt-6">
                            <Link 
                                :href="route('teams.show', $page.props.auth.user.current_team_id)" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150"
                            >
                                <EyeIcon class="h-5 w-5 mr-2 -ml-1 text-black" />
                                Gerenciar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Administradores sem Unidade Cadastrada -->
                    <div v-else-if="isAdmin && !unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">Você ainda não cadastrou sua unidade policial no censo anual.</p>
                        <div class="p-4 rounded-lg bg-amber-50 text-amber-900 mb-6 flex items-center">
                            <ExclamationTriangleIcon class="h-6 w-6 text-[#bea55a] mr-3" />
                            <p>Para participar do censo anual da engenharia, cadastre sua unidade policial no sistema.</p>
                        </div>
                        <div class="mt-6">
                            <Link 
                                :href="route('teams.create')" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150"
                            >
                                <PlusIcon class="h-5 w-5 mr-2 -ml-1 text-black" />
                                Cadastrar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Servidores com Unidade Cadastrada -->
                    <div v-else-if="isServidor && unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">Bem-vindo ao sistema de gerenciamento de unidades policiais!</p>
                        <div class="p-4 rounded-lg bg-[#f5e6b8] text-gray-900 mb-6 flex items-center">
                            <CheckCircleIcon class="h-6 w-6 text-[#bea55a] mr-3" />
                            <p>Sua unidade está cadastrada no sistema.</p>
                        </div>
                        <div class="mt-6">
                            <Link 
                                :href="route('teams.show', $page.props.auth.user.current_team_id)" 
                                class="inline-flex items-center px-6 py-3 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-black uppercase tracking-wider hover:bg-[#d4bf7a] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150"
                            >
                                <EyeIcon class="h-5 w-5 mr-2 -ml-1 text-black" />
                                Visualizar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Servidores sem Unidade Cadastrada -->
                    <div v-else-if="isServidor && !unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4 text-gray-700">Bem-vindo ao sistema de gerenciamento de unidades policiais!</p>
                        <div class="p-4 rounded-lg bg-amber-50 text-amber-900 mb-6 flex items-center">
                            <ExclamationTriangleIcon class="h-6 w-6 text-[#bea55a] mr-3" />
                            <p>Sua unidade ainda não foi cadastrada no sistema. Por favor, entre em contato com um administrador.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>