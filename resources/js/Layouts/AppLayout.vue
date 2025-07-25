<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { 
    UserIcon, 
    Bars3Icon, 
    XMarkIcon, 
    EnvelopeIcon,
    DocumentMagnifyingGlassIcon 
} from '@heroicons/vue/24/outline';
import Footer from '@/Components/Footer.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    title: String,
});

const page = usePage();
const showingNavigationDropdown = ref(false);
const scrolled = ref(false);
const toastRef = ref(null);

// Monitor scroll position for navbar styling
const handleScroll = () => {
    scrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    if (toastRef.value) {
        window.__toastInstance = toastRef.value;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Função para rolar ao topo da página
const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
};

const isSuperAdmin = computed(() => {
    const user = page.props.auth.user;
    return user && user.isSuperAdmin === true;
});

// Verificar se é o super admin de (matrícula 0000001)
const isSuperAdminPrincipal = computed(() => {
    const user = page.props.auth.user;
    return user && user.isSuperAdmin === true && user.matricula === '0000001';
});

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};

// Determine a rota de "Minha Unidade Policial"
const unidadeRoute = computed(() => {
    const user = page.props.auth.user;
    
    if (!user || !user.current_team) {
        return route('unidades.create');
    }

    // Verificar se a unidade está cadastrada (baseado em is_draft)
    const unidade = page.props.auth.unidades?.find(u => u.team_id === user.current_team_id);
    if (unidade && !unidade.is_draft) {
        return route('unidades.show', { team: user.current_team_id, unidade: unidade.id });
    }

    return route('unidades.create');
});

// Verificar se a rota atual é unidades.show
const isUnidadeRouteActive = computed(() => {
    return route().current('unidades.show') || route().current('unidades.create');
});

// Verificar se a rota atual é de auditoria
const isAuditoriaRouteActive = computed(() => {
    return route().current('admin.auditoria.index') || 
           route().current('admin.auditoria.show');
});

// Verificar se o usuário está autenticado e tem all_teams
const hasTeams = computed(() => {
    return page.props.auth.user && 
           Array.isArray(page.props.auth.user.all_teams) && 
           page.props.auth.user.all_teams.length > 1;
});

// Close mobile menu when route changes
router.on('navigate', () => {
    showingNavigationDropdown.value = false;
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gray-100">
        <Head :title="title" />

        <Banner />

        <!-- Sticky Header - changes on scroll -->
        <header :class="[
            'sticky top-0 z-[1000] transition-all duration-300',
            scrolled ? 'shadow-md' : ''
        ]">
            <!-- Barra de Contato -->
            <div class="bg-black text-white py-1 text-xs">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-end">
                    <a href="mailto:dieri.dg@pc.pb.gov.br" class="inline-flex items-center text-[#bea55a] hover:text-[#d4bf7a] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contato: dieri.dg@pc.pb.gov.br
                    </a>
                </div>
            </div>
            
            <!-- Page header section -->
            <div v-if="$slots.header" class="bg-black shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </div>
            
            <!-- Main navigation -->
            <nav :class="[
                'bg-[#bea55a] border-b-2 border-[#816d33] transition-all duration-300',
                scrolled ? 'py-2' : 'py-3'
            ]">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center">
                        <div class="flex">
                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink 
                                    :href="route('dashboard')" 
                                    :active="route().current('dashboard')"
                                    class="text-black hover:text-white font-sans transition-colors duration-150 font-medium"
                                >
                                    Home
                                </NavLink>
                                
                                <!-- Links apenas para SuperAdmin -->
                                <template v-if="isSuperAdmin">
                                    <NavLink 
                                        :href="route('admin.unidades.index')" 
                                        :active="route().current('admin.unidades.index')"
                                        class="text-black hover:text-white font-sans transition-colors duration-150 font-medium"
                                    >
                                        Unidades
                                    </NavLink>
                                    
                                    <NavLink 
                                        :href="route('admin.users.index')" 
                                        :active="route().current('admin.users.index')"
                                        class="text-black hover:text-white font-sans transition-colors duration-150 font-medium"
                                    >
                                        Usuários
                                    </NavLink>

                                    <NavLink 
                                        :href="route('admin.orgaos.index')" 
                                        :active="route().current('admin.orgaos.index')"
                                        class="text-black hover:text-white font-sans transition-colors duration-150 font-medium"
                                    >
                                        Órgãos
                                    </NavLink>

                                    <!-- Link de Auditoria apenas para super admin -->
                                    <NavLink 
                                        v-if="isSuperAdminPrincipal"
                                        :href="route('admin.auditoria.index')" 
                                        :active="isAuditoriaRouteActive"
                                        class="text-black hover:text-white font-sans transition-colors duration-150 font-medium flex items-center"
                                        title="Sistema de Auditoria"
                                    >
                                        <DocumentMagnifyingGlassIcon class="h-5 w-5 mr-1" />
                                        Auditoria
                                    </NavLink>
                                </template>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-2">
                            
                            <!-- Teams Dropdown -->
                            <Dropdown v-if="$page.props.jetstream.hasTeamFeatures && $page.props.auth.user" align="right" width="60">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <span 
                                            type="button" 
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black bg-[#bea55a] focus:outline-none transition-colors duration-150"
                                        >
                                            {{ $page.props.auth.user.current_team.name }}
                                        </span>
                                    </span>
                                </template>

                                <template #content>
                                    <div class="w-60">
                                        <!-- Team Switcher -->
                                        <template v-if="hasTeams">
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Alternar Unidades
                                            </div>

                                            <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                <form @submit.prevent="switchToTeam(team)">
                                                    <DropdownLink as="button">
                                                        <div class="flex items-center">
                                                            <svg v-if="team.id == $page.props.auth.user.current_team_id" class="mr-2 h-5 w-5 text-[#bea55a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>

                                                            <span class="truncate max-w-[180px] text-gray-700 hover:text-[#bea55a]">
                                                                {{ team.name }}
                                                            </span>
                                                        </div>
                                                    </DropdownLink>
                                                </form>
                                            </template>
                                        </template>
                                    </div>
                                </template>
                            </Dropdown>

                            <!-- Settings Dropdown -->
                            <div v-if="$page.props.auth.user" class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button 
                                            v-if="$page.props.jetstream.managesProfilePhotos" 
                                            class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-[#816d33] transition overflow-hidden"
                                        >
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button 
                                                type="button" 
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black hover:bg-[#a89043] focus:outline-none transition-colors duration-150"
                                            >
                                                <UserIcon class="h-5 w-5 mr-2" />
                                                <span class="max-w-[100px] truncate">{{ $page.props.auth.user.name.split(' ')[0] }}</span>

                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Gerenciar Conta
                                        </div>

                                        <DropdownLink 
                                            :href="route('profile.show')"
                                            class="text-gray-700 hover:text-[#bea55a] hover:bg-gray-50 transition-colors duration-150 px-4 py-2"
                                        >
                                            Perfil
                                        </DropdownLink>

                                        <DropdownLink 
                                            v-if="$page.props.jetstream.hasApiFeatures" 
                                            :href="route('api-tokens.index')"
                                            class="text-gray-700 hover:text-[#bea55a] hover:bg-gray-50 transition-colors duration-150 px-4 py-2"
                                        >
                                            API Tokens
                                        </DropdownLink>

                                        <!-- Auditoria no dropdown do usuário (apenas para super admin) -->
                                        <template v-if="isSuperAdminPrincipal">
                                            <div class="border-t border-gray-200 my-1"></div>
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Sistema
                                            </div>
                                            <DropdownLink 
                                                :href="route('admin.auditoria.index')"
                                                class="text-gray-700 hover:text-[#bea55a] hover:bg-gray-50 transition-colors duration-150 px-4 py-2 flex items-center"
                                            >
                                                <DocumentMagnifyingGlassIcon class="h-4 w-4 mr-2" />
                                                Auditoria do Sistema
                                            </DropdownLink>
                                        </template>

                                        <div class="border-t border-gray-200 my-1"></div>

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink 
                                                as="button"
                                                class="text-gray-700 hover:text-[#bea55a] hover:bg-gray-50 transition-colors duration-150 px-4 py-2 w-full text-left"
                                            >
                                                Sair
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button 
                                class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-[#d4bf7a] hover:bg-[#816d33] focus:outline-none transition-colors duration-150" 
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                aria-expanded="showingNavigationDropdown"
                                aria-label="Menu de navegação"
                            >
                                <Bars3Icon v-if="!showingNavigationDropdown" class="h-6 w-6" />
                                <XMarkIcon v-else class="h-6 w-6" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-[#bea55a] absolute w-full left-0 shadow-lg border-t border-[#816d33] z-50 transition-all duration-300">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink 
                            :href="route('dashboard')" 
                            :active="route().current('dashboard')"
                            class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2 rounded-md text-base font-medium"
                        >
                            Home
                        </ResponsiveNavLink>

                        <!-- Links apenas para super administradores -->
                        <template v-if="isSuperAdmin">
                            <ResponsiveNavLink 
                                :href="route('admin.unidades.index')" 
                                :active="route().current('admin.unidades.index')"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2 rounded-md text-base font-medium"
                            >
                                Unidades
                            </ResponsiveNavLink>

                            <ResponsiveNavLink 
                                :href="route('admin.users.index')" 
                                :active="route().current('admin.users.index')"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2 rounded-md text-base font-medium"
                            >
                                Usuários
                            </ResponsiveNavLink>

                            <ResponsiveNavLink 
                                :href="route('admin.orgaos.index')" 
                                :active="route().current('admin.orgaos.index')"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2 rounded-md text-base font-medium"
                            >
                                Órgãos
                            </ResponsiveNavLink>

                            <!-- Auditoria no menu mobile -->
                            <ResponsiveNavLink 
                                v-if="isSuperAdminPrincipal"
                                :href="route('admin.auditoria.index')" 
                                :active="isAuditoriaRouteActive"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2 rounded-md text-base font-medium flex items-center"
                            >
                                <DocumentMagnifyingGlassIcon class="h-5 w-5 mr-2" />
                                Auditoria
                            </ResponsiveNavLink>
                        </template>
                        
                        <!-- Exibir contato na versão móvel -->
                        <div class="mt-2 pt-2 border-t border-[#816d33]">
                            <a 
                                href="mailto:dieri.dg@pc.pb.gov.br" 
                                class="flex items-center px-3 py-2 text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 rounded-md text-sm font-medium"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Contato: dieri.dg@pc.pb.gov.br
                            </a>
                        </div>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div v-if="$page.props.auth.user" class="pt-4 pb-1 border-t border-[#816d33]">
                        <div class="flex items-center px-4 py-2">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-10 w-10 rounded-full object-cover border-2 border-white shadow" 
                                     :src="$page.props.auth.user.profile_photo_url" 
                                     :alt="$page.props.auth.user.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-black">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-800">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1 border-t border-[#816d33] pt-2">
                            <ResponsiveNavLink 
                                :href="route('profile.show')" 
                                :active="route().current('profile.show')"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2"
                            >
                                Perfil
                            </ResponsiveNavLink>

                            <ResponsiveNavLink 
                                v-if="$page.props.jetstream.hasApiFeatures" 
                                :href="route('api-tokens.index')" 
                                :active="route().current('api-tokens.index')"
                                class="text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2"
                            >
                                API Tokens
                            </ResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink 
                                    as="button"
                                    class="w-full text-left text-black hover:text-white hover:bg-[#816d33] transition-colors duration-150 block px-3 py-2"
                                >
                                    Sair
                                </ResponsiveNavLink>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main content -->
        <main class="flex-grow">
            <slot />
        </main>
            
        <!-- Footer -->
        <Footer />
        
        <!-- Back to top button - appears when scrolled -->
        <button 
            v-show="scrolled" 
            @click="scrollToTop"
            class="fixed bottom-6 right-6 p-2 rounded-full bg-[#bea55a] text-black hover:bg-[#d4bf7a] shadow-lg transition-all duration-300 transform hover:scale-110 focus:outline-none"
            aria-label="Voltar ao topo"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>
        <!-- Toast Component -->
    <Toast ref="toastRef" />
    </div>
</template>

<style scoped>
/* Responsive adjustments */
@media (max-width: 640px) {
    .responsive-padding {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

/* Animation for page transitions */
.page-enter-active,
.page-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>