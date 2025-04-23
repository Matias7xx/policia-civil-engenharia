<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    unidades: Array,
});

// Estado para filtros
const searchQuery = ref('');
const statusFilter = ref('todos');

// Computar unidades filtradas
const filteredUnidades = computed(() => {
    if (!props.unidades) return []; // Retorna array vazio se props.unidades for undefined
    
    return props.unidades.filter(unidade => {
        // Filtrar por pesquisa
        const matchesSearch = 
            unidade.nome.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (unidade.codigo ? unidade.codigo.toLowerCase().includes(searchQuery.value.toLowerCase()) : false) ||
            (unidade.cidade ? unidade.cidade.toLowerCase().includes(searchQuery.value.toLowerCase()) : false);
        
        // Filtrar por status
        const matchesStatus = statusFilter.value === 'todos' || unidade.status === statusFilter.value;
        
        return matchesSearch && matchesStatus;
    });
});

// Limpar filtros
const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'todos';
};

// Status para filtro
const statusOptions = [
    { value: 'todos', label: 'Todos' },
    { value: 'pendente_avaliacao', label: 'Pendente de Avaliação' },
    { value: 'aprovada', label: 'Aprovada' },
    { value: 'reprovada', label: 'Reprovada' },
    { value: 'em_revisao', label: 'Em Revisão' }
];

// Status de cor
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

// Formatação do status
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
</script>

<template>
    <AppLayout title="Gerenciar Unidades Policiais">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gerenciar Unidades Policiais
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Filtros -->
                    <div class="flex flex-col md:flex-row mb-6 gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">Pesquisar</label>
                            <input 
                                id="search" 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Buscar por nome, código ou cidade..." 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="md:w-64">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                id="status" 
                                v-model="statusFilter" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button 
                                @click="clearFilters"
                                class="mt-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md border border-gray-300"
                            >
                                Limpar Filtros
                            </button>
                        </div>
                    </div>

                    <!-- Estatísticas Rápidas -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg border">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total de Unidades</dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">{{ props.unidades ? props.unidades.length : 0 }}</div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white overflow-hidden shadow rounded-lg border">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Pendentes</dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    {{ props.unidades ? props.unidades.filter(u => u.status === 'pendente_avaliacao').length : 0 }}
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg border">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Aprovadas</dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    {{ props.unidades ? props.unidades.filter(u => u.status === 'aprovada').length : 0 }}
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg border">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Reprovadas</dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    {{ props.unidades ? props.unidades.filter(u => u.status === 'reprovada').length : 0 }}
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Unidades -->
                    <div class="mt-4 flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nome
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Código
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Localização
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ações
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="unidade in filteredUnidades" :key="unidade.id">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ unidade.nome }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ unidade.codigo || 'N/A' }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        {{ unidade.cidade || 'N/A' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ unidade.rua ? `${unidade.rua}, ${unidade.numero || 'S/N'}` : 'Endereço não informado' }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(unidade.status)">
                                                        {{ formatStatus(unidade.status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <Link :href="route('admin.unidades.show', unidade.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                                        Visualizar
                                                    </Link>
                                                    <Link :href="route('teams.show', unidade.team_id)" class="text-green-600 hover:text-green-900">
                                                        Editar
                                                    </Link>
                                                </td>
                                            </tr>
                                            <tr v-if="filteredUnidades.length === 0">
                                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                                    Nenhuma unidade encontrada. Tente ajustar os filtros.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>