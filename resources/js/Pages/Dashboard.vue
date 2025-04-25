<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
        return "Bem-vindo ao Sistema de Censo Anual da Engenharia - Painel Super Administrador";
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
                <span class="text-white text-xl font-semibold pl-2">ENGENHARIA</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensagem de boas-vindas -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-4">{{ welcomeMessage }}</h1>
                    
                    <!-- Conteúdo para Super Administradores -->
                    <div v-if="isSuperAdmin" class="mb-6">
                        <p class="text-lg mb-4">Resumo do Sistema:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-100 p-4 rounded-lg">
                                <p class="text-blue-800 font-semibold">Total de Unidades:</p>
                                <p class="text-2xl font-bold">{{ unidadesCount }}</p>
                            </div>
                            <div class="bg-yellow-100 p-4 rounded-lg">
                                <p class="text-yellow-800 font-semibold">Pendentes de Avaliação:</p>
                                <p class="text-2xl font-bold">{{ unidadesPendentes }}</p>
                            </div>
                        </div>
                        <div class="mt-6">
                            <Link :href="route('admin.unidades.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                Gerenciar Unidades
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Administradores com Unidade Cadastrada -->
                    <div v-else-if="isAdmin && unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4">Sua unidade policial já está cadastrada no sistema!</p>
                        <div class="mt-6">
                            <Link :href="route('teams.show', $page.props.auth.user.current_team_id)" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                Gerenciar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Administradores sem Unidade Cadastrada -->
                    <div v-else-if="isAdmin && !unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4">Você ainda não cadastrou sua unidade policial no censo anual.</p>
                        <div class="p-4 rounded-lg bg-yellow-100 text-yellow-800 mb-6">
                            <p>Para participar do censo anual da engenharia, cadastre sua unidade policial no sistema.</p>
                        </div>
                        <div class="mt-6">
                            <Link :href="route('teams.create')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition">
                                Cadastrar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Servidores com Unidade Cadastrada -->
                    <div v-else-if="isServidor && unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4">Bem-vindo ao sistema de gerenciamento de unidades policiais!</p>
                        <div class="mt-6">
                            <Link :href="route('teams.show', $page.props.auth.user.current_team_id)" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                Visualizar Minha Unidade
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Conteúdo para Servidores sem Unidade Cadastrada -->
                    <div v-else-if="isServidor && !unidadeCadastrada" class="mb-6">
                        <p class="text-lg mb-4">Bem-vindo ao sistema de gerenciamento de unidades policiais!</p>
                        <div class="p-4 rounded-lg bg-yellow-100 text-yellow-800 mb-6">
                            <p>Sua unidade ainda não foi cadastrada no sistema. Por favor, entre em contato com um administrador.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>