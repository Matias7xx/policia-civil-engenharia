<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { MagnifyingGlassIcon, FunnelIcon, PlusIcon, XMarkIcon, PencilIcon, TrashIcon, BuildingOffice2Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
    orgaos: Object,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'todos');
const isLoading = ref(false);

const confirmingOrgaoDeletion = ref(false);
const orgaoBeingDeleted = ref(null);

const deleteForm = useForm({});

let searchTimeout;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    isLoading.value = true;
    searchTimeout = setTimeout(() => {
        const normalizedSearch = searchQuery.value.trim().toLowerCase();
        
        router.get(
            route('admin.orgaos.index'),
            { 
                search: normalizedSearch, 
                status: statusFilter.value 
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
    }, 400);
};

watch(searchQuery, applyFilters);
watch(statusFilter, applyFilters);

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'todos';
    router.get(route('admin.orgaos.index'), {}, { preserveState: true });
};

const statusOptions = [
    { value: 'todos', label: 'Todos' },
    { value: 'ativo', label: 'Ativo' },
    { value: 'inativo', label: 'Inativo' },
];

const confirmOrgaoDeletion = (orgao) => {
    orgaoBeingDeleted.value = orgao;
    confirmingOrgaoDeletion.value = true;
};

const deleteOrgao = () => {
    deleteForm.delete(route('admin.orgaos.destroy', orgaoBeingDeleted.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => closeModal(),
        onFinish: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingOrgaoDeletion.value = false;
    orgaoBeingDeleted.value = null;
};
</script>

<template>
    <AppLayout title="Gerenciar Órgãos">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2">ENGENHARIA - Gerenciar Órgãos</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="bg-amber-50 border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <BuildingOffice2Icon class="h-6 w-6 text-[#bea55a] mr-2" />
                            Lista de Órgãos
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Gerencie os órgãos do sistema
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
                                    placeholder="Buscar por nome..." 
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
                                    Status
                                </label>
                                <select 
                                    id="status" 
                                    v-model="statusFilter" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] text-base"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button 
                                    @click="clearFilters"
                                    class="mt-1 px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition ease-in-out duration-150 flex items-center"
                                >
                                    <XMarkIcon class="h-5 w-5 mr-2" />
                                    Limpar Filtros
                                </button>
                            </div>
                        </div>

                        <!-- Botão de Criar Novo Órgão -->
                        <div class="mb-6">
                            <Link 
                                :href="route('admin.orgaos.create')"
                                class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-300 transition ease-in-out duration-150"
                            >
                                <PlusIcon class="h-5 w-5 mr-2 -ml-1" />
                                Criar Novo Órgão
                            </Link>
                        </div>

                        <!-- Tabela de Órgãos -->
                        <div class="mt-4 flex flex-col">
                            <div class="overflow-x-auto">
                                <div class="align-middle inline-block min-w-full">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="w-2/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nome
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Ações
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="orgao in orgaos.data" :key="orgao.id" class="hover:bg-gray-50 transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :href="route('admin.orgaos.show', orgao.id)" class="text-base font-medium text-[#bea55a] hover:text-[#d4bf7a]">
                                                            {{ orgao.nome }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full" :class="orgao.status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                                            {{ orgao.status || 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <Link :href="route('admin.orgaos.edit', orgao.id)" class="text-indigo-600 hover:text-indigo-900 mr-4 flex items-center">
                                                            <PencilIcon class="h-5 w-5 mr-1" />
                                                            Editar
                                                        </Link>
                                                        <button @click="confirmOrgaoDeletion(orgao)" class="text-red-600 hover:text-red-900 flex items-center">
                                                            <TrashIcon class="h-5 w-5 mr-1" />
                                                            Excluir
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr v-if="orgaos.data.length === 0" class="transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                                        Nenhum órgão encontrado. Tente ajustar os filtros.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paginação -->
                        <div class="mt-6" v-if="orgaos.data.length > 0">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Mostrando {{ orgaos.from }}-{{ orgaos.to }} de {{ orgaos.total }} órgãos
                                </div>
                                <Pagination :links="orgaos.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmação de exclusão -->
        <DialogModal :show="confirmingOrgaoDeletion" @close="closeModal">
            <template #title>
                Excluir Órgão
            </template>

            <template #content>
                <div v-if="orgaoBeingDeleted">
                    Tem certeza que deseja excluir o órgão <strong>{{ orgaoBeingDeleted.nome }}</strong>?
                    <p class="mt-2 text-sm text-gray-500">Esta ação não pode ser desfeita.</p>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteOrgao"
                >
                    Excluir Órgão
                </DangerButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>