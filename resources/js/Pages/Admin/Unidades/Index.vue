<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { MagnifyingGlassIcon, FunnelIcon, BuildingOfficeIcon, XMarkIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    unidades: Object,
    filters: Object,
    statusOptions: Array,
    notaOptions: Array,
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'todos');
const notaFilter = ref(props.filters.nota || 'todas');
const isLoading = ref(false);

let searchTimeout;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    isLoading.value = true;
    searchTimeout = setTimeout(() => {
        const normalizedSearch = searchQuery.value.trim().toLowerCase();
        
        router.get(
            route('admin.unidades.index'),
            { 
                search: normalizedSearch, 
                status: statusFilter.value,
                nota: notaFilter.value,
            },
            { 
                preserveState: true,
                replace: true,
                preserveScroll: true,
                onFinish: () => {
                    isLoading.value = false;
                },
            }
        );
    }, 400); // Debounce
};

watch(searchQuery, applyFilters);
watch(statusFilter, applyFilters);
watch(notaFilter, applyFilters);

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'todos';
    notaFilter.value = 'todas';
    router.get(route('admin.unidades.index'), {}, { preserveState: true });
};

// Função para baixar relatório
const downloadReport = (format) => {
    window.location.href = route('admin.formularios.relatorio', { format });
};

const getNotaLetra = (nota) => {
    if (!nota) return '';
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return '';
    if (notaNum >= 9.5) return 'A+';
    if (notaNum >= 9.0) return 'A';
    if (notaNum >= 8.0) return 'B';
    if (notaNum >= 7.0) return 'C';
    if (notaNum >= 6.0) return 'D';
    if (notaNum >= 5.0) return 'E';
    if (notaNum >= 4.0) return 'F';
    if (notaNum >= 3.0) return 'G';
    if (notaNum >= 2.0) return 'H';
    if (notaNum >= 1.0) return 'I';
    return 'J';
};

const formatNota = (nota) => {
    if (!nota) return 'N/A';
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return 'N/A';
    return `${notaNum.toFixed(1)} (${getNotaLetra(notaNum)})`;
};

const getNotaClass = (nota) => {
    if (!nota) return 'bg-gray-100 text-gray-800';
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return 'bg-gray-100 text-gray-800';
    if (notaNum >= 9.0) return 'bg-green-600 text-white';
    if (notaNum >= 7.0) return 'bg-green-500 text-white';
    if (notaNum >= 5.0) return 'bg-yellow-500 text-white';
    if (notaNum >= 3.0) return 'bg-orange-500 text-white';
    return 'bg-red-500 text-white';
};

const getNotaTooltip = (nota) => {
    if (!nota) return '';
    const letra = getNotaLetra(nota);
    switch (letra) {
        case 'A+':
        case 'A': return 'Excelente: Condições ideais sem necessidade de melhorias';
        case 'B': return 'Ótimo: Condições muito boas com pequenas melhorias necessárias';
        case 'C': return 'Bom: Condições adequadas com algumas melhorias recomendadas';
        case 'D': return 'Satisfatório: Condições básicas atendidas, melhorias necessárias';
        case 'E': return 'Regular: Atende minimamente, melhorias significativas necessárias';
        case 'F': return 'Insuficiente: Não atende adequadamente, melhorias urgentes';
        case 'G': return 'Ruim: Condições precárias, necessidade de intervenção';
        case 'H': return 'Muito Ruim: Condições críticas, intervenção imediata';
        case 'I': return 'Péssimo: Condições extremamente precárias, risco à segurança';
        case 'J': return 'Crítico: Inapto para uso, riscos severos à segurança';
        default: return '';
    }
};
</script>

<template>
    <AppLayout title="Gerenciar Unidades">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2">ENGENHARIA - Gerenciar Unidades</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="bg-amber-50 border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <BuildingOfficeIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                            Lista de Unidades
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Gerencie as unidades do sistema
                        </p>
                    </div>

                    <div class="p-6">
                        <!-- Filtros -->
                        <div class="flex flex-col md:flex-row mb-6 gap-4">
                            <div class="flex-1 relative">
                                <label for="search" class="block text-sm font-medium text-gray-700 flex items-center">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 mr-2" />
                                    Pesquisar
                                </label>
                                <input 
                                    id="search" 
                                    v-model="searchQuery" 
                                    type="text" 
                                    placeholder="Buscar por nome, cidade, unidade gestora..." 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] text-base"
                                />
                                <div v-if="isLoading" class="absolute right-3 top-10 text-gray-500">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="md:w-64">
                                <label for="status" class="block text-sm font-medium text-gray-700 flex items-center">
                                    <FunnelIcon class="h-5 w-5 text-gray-400 mr-2" />
                                    Status Formulário
                                </label>
                                <select 
                                    id="status" 
                                    v-model="statusFilter" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] text-base"
                                >
                                    <option v-for="option in statusOptions" :key="option.key" :value="option.key">
                                        {{ option.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="md:w-64">
                                <label for="nota" class="block text-sm font-medium text-gray-700 flex items-center">
                                    <FunnelIcon class="h-5 w-5 text-gray-400 mr-2" />
                                    Nota Geral
                                </label>
                                <select 
                                    id="nota" 
                                    v-model="notaFilter" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] text-base"
                                >
                                    <option v-for="option in notaOptions" :key="option.key" :value="option.key">
                                        {{ option.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button 
                                    @click="clearFilters"
                                    class="mt-1 px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition ease-in-out duration-150 flex items-center"
                                >
                                    <XMarkIcon class="h-5 w-5 mr-2" />
                                    Limpar Filtros
                                </button>
                                <button 
                                    @click="downloadReport('pdf')"
                                    class="mt-1 px-4 py-2 bg-[#bea55a] border border-[#bea55a] rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150 flex items-center"
                                >
                                    <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                                    PDF
                                </button>
                                <button 
                                    @click="downloadReport('excel')"
                                    class="mt-1 px-4 py-2 bg-[#bea55a] border border-[#bea55a] rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] focus:outline-none focus:ring-2 focus:ring-[#bea55a] transition ease-in-out duration-150 flex items-center"
                                >
                                    <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                                    Excel
                                </button>
                            </div>
                        </div>

                        <!-- Tabela de Unidades -->
                        <div class="mt-4 flex flex-col">
                            <div class="overflow-x-auto">
                                <div class="align-middle inline-block min-w-full">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nome
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Cidade
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Unidade Gestora
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Unidade Sub-Gestora
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Status Formulário
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nota Geral
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="unidade in unidades.data" :key="unidade.id" class="hover:bg-gray-50 transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <Link :href="route('admin.unidades.show', unidade.id)" class="text-base font-medium text-[#bea55a] hover:text-[#d4bf7a]">
                                                            {{ unidade.nome }}
                                                        </Link>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-base text-gray-900">{{ unidade.cidade }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-base text-gray-900">{{ unidade.srpc }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-base text-gray-900">{{ unidade.dspc }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full" :class="unidade.status_class || 'bg-gray-100 text-gray-800'">
                                                            {{ unidade.status_formatado || 'Sem Status' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span 
                                                            v-if="unidade.nota_geral" 
                                                            class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full" 
                                                            :class="getNotaClass(unidade.nota_geral)"
                                                            :title="getNotaTooltip(unidade.nota_geral)"
                                                        >
                                                            {{ formatNota(unidade.nota_geral) }}
                                                        </span>
                                                        <span v-else class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            N/A
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="unidades.data.length === 0" class="transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                                        Nenhuma unidade encontrada. Tente ajustar os filtros.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paginação -->
                        <div class="mt-6" v-if="unidades.data.length > 0">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Mostrando {{ unidades.from }}-{{ unidades.to }} de {{ unidades.total }} unidades
                                </div>
                                <Pagination :links="unidades.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>