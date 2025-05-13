<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { MagnifyingGlassIcon, FunnelIcon, PlusIcon, XMarkIcon, PencilIcon, TrashIcon, UsersIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Object,
    teams: Array,
    roles: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || 'todos');
const isLoading = ref(false);

const confirmingUserDeletion = ref(false);
const userBeingDeleted = ref(null);

const deleteForm = useForm({});

let searchTimeout;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    isLoading.value = true;
    searchTimeout = setTimeout(() => {
        const normalizedSearch = searchQuery.value.trim().toLowerCase();
        
        router.get(
            route('admin.users.index'),
            { 
                search: normalizedSearch, 
                role: roleFilter.value 
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
    }, 400); //Debounce para simular delay
};

watch(searchQuery, applyFilters);
watch(roleFilter, applyFilters);

const clearFilters = () => {
    searchQuery.value = '';
    roleFilter.value = 'todos';
    router.get(route('admin.users.index'), {}, { preserveState: true });
};

const formatRole = (role) => {
    const roleObj = props.roles?.find(r => r.key === role);
    return roleObj ? roleObj.name : role;
};

const roleOptions = computed(() => {
    const options = [{ value: 'todos', label: 'Todos' }];
    if (props.roles && props.roles.length > 0) {
        props.roles.forEach(role => {
            options.push({
                value: role.key,
                label: role.name
            });
        });
    }
    return options;
});

const confirmUserDeletion = (user) => {
    userBeingDeleted.value = user;
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    deleteForm.delete(route('admin.users.destroy', userBeingDeleted.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => closeModal(),
        onFinish: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    userBeingDeleted.value = null;
};
</script>

<template>
    <AppLayout title="Gerenciar Usuários">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2">ENGENHARIA - Gerenciar Usuários</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="bg-amber-50 border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <UsersIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                            Lista de Usuários
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Gerencie os usuários do sistema
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
                                    placeholder="Buscar por nome, matrícula ou unidade..." 
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
                                <label for="role" class="block text-sm font-medium text-gray-700 flex items-center">
                                    <FunnelIcon class="h-5 w-5 text-gray-400 mr-2" />
                                    Função
                                </label>
                                <select 
                                    id="role" 
                                    v-model="roleFilter" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] text-base"
                                >
                                    <option v-for="option in roleOptions" :key="option.value" :value="option.value">
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

                        <!-- Botão de Criar Novo Usuário -->
                        <div class="mb-6">
                            <Link 
                                :href="route('admin.users.create')"
                                class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-300 transition ease-in-out duration-150"
                            >
                                <PlusIcon class="h-5 w-5 mr-2 -ml-1" />
                                Criar Novo Usuário
                            </Link>
                        </div>

                        <!-- Tabela de Usuários -->
                        <div class="mt-4 flex flex-col">
                            <div class="overflow-x-auto">
                                <div class="align-middle inline-block min-w-full">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nome
                                                    </th>
                                                    <th scope="col" class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Matrícula
                                                    </th>
                                                    <th scope="col" class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Unidade
                                                    </th>
                                                    <th scope="col" class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Função
                                                    </th>
                                                    <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Ações
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <Link :href="route('admin.users.show', user.id)" class="text-base font-medium text-[#bea55a] hover:texte-[#d4bf7a]">
                                                            {{ user.name }}
                                                        </Link>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-base text-gray-900">{{ user.matricula || 'N/A' }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-base text-gray-900">
                                                            {{ user.team ? user.team.name : 'Sem unidade' }}
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-amber-100 text-black">
                                                            {{ formatRole(user.role) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <Link :href="route('admin.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 mr-4 flex items-center">
                                                            <PencilIcon class="h-5 w-5 mr-1" />
                                                            Editar
                                                        </Link>
                                                        <!-- <button @click="confirmUserDeletion(user)" class="text-red-600 hover:text-red-900 flex items-center">
                                                            <TrashIcon class="h-5 w-5 mr-1" />
                                                            Excluir
                                                        </button> -->
                                                    </td>
                                                </tr>
                                                <tr v-if="users.data.length === 0" class="transition-opacity duration-300" :class="{ 'opacity-50': isLoading }">
                                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                                        Nenhum usuário encontrado. Tente ajustar os filtros.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paginação -->
                        <div class="mt-6" v-if="users.data.length > 0">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Mostrando {{ users.from }}-{{ users.to }} de {{ users.total }} usuários
                                </div>
                                <Pagination :links="users.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmação de exclusão -->
        <DialogModal :show="confirmingUserDeletion" @close="closeModal">
            <template #title>
                Excluir Usuário
            </template>

            <template #content>
                <div v-if="userBeingDeleted">
                    Tem certeza que deseja excluir o usuário <strong>{{ userBeingDeleted.name }}</strong>?
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
                    @click="deleteUser"
                >
                    Excluir Usuário
                </DangerButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>