<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

// Ícones do Heroicons
import {
    UserIcon,
    EnvelopeIcon,
    IdentificationIcon,
    BriefcaseIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    ShieldCheckIcon,
    CalendarIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    user: Object,
});

const formatRole = (role) => {
    const rolesMap = {
        superadmin: "Super Administrador",
        admin: "Administrador",
        servidor: "Servidor",
    };
    return rolesMap[role] || role;
};
</script>

<template>
    <AppLayout title="Detalhes do Usuário">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img
                        src="/images/logo-pc-branca.png"
                        alt="Logo da Polícia Civil"
                        class="h-14 w-auto"
                    />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2"
                    >ENGENHARIA - Detalhes do Usuário</span
                >
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Card Principal -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Cabeçalho do Card -->
                    <div class="bg-amber-50 border-b border-gray-200 px-6 py-4">
                        <h3
                            class="text-lg font-semibold text-gray-900 flex items-center"
                        >
                            <UserIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                            {{ user.name }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Detalhes do usuário e informações associadas
                        </p>
                    </div>

                    <!-- Conteúdo do Card -->
                    <div class="p-6">
                        <!-- Informações do Usuário -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Campo: Nome -->
                            <div class="flex items-start">
                                <UserIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Nome</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.name }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Email -->
                            <div class="flex items-start">
                                <EnvelopeIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Email</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.email }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Matrícula -->
                            <div class="flex items-start">
                                <IdentificationIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Matrícula</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.matricula || "N/A" }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Cargo -->
                            <div class="flex items-start">
                                <BriefcaseIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Cargo</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.cargo || "N/A" }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Telefone -->
                            <div class="flex items-start">
                                <PhoneIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Telefone</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.telefone || "N/A" }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Unidade -->
                            <div class="flex items-start">
                                <BuildingOfficeIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Unidade</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{
                                            user.team
                                                ? user.team.name
                                                : "Sem unidade"
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Função -->
                            <div class="flex items-start">
                                <ShieldCheckIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Função</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ formatRole(user.role) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Campo: Criado em -->
                            <div class="flex items-start">
                                <CalendarIcon
                                    class="h-5 w-5 text-gray-400 mr-3 mt-1"
                                />
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700"
                                        >Criado em</span
                                    >
                                    <span
                                        class="block text-base text-gray-900"
                                        >{{ user.created_at }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div
                            class="mt-8 flex flex-col sm:flex-row sm:space-x-4 space-y-3 sm:space-y-0"
                        >
                            <Link
                                :href="route('admin.users.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="h-5 w-5 mr-2 -ml-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    ></path>
                                </svg>
                                Voltar
                            </Link>
                            <Link
                                :href="route('admin.users.edit', user.id)"
                                class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] focus:outline-none focus:ring-2 focus:ring-indigo-300 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="h-5 w-5 mr-2 -ml-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    ></path>
                                </svg>
                                Editar Usuário
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
