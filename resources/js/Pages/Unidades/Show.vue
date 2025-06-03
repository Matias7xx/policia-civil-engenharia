<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamMemberManager from '@/Pages/Teams/Partials/TeamMemberManager.vue';
import { ref, watch, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    team: Object,
    availableRoles: Array,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    orgaos: Array,
    permissions: Object,
    userPermissions: Object,
});

const orgaosCompartilhados = computed(() => props.unidade?.orgaosCompartilhados || []);
const page = usePage();
const activeTab = ref('dados-gerais');
const flashMessage = ref(null);
const mobileMenuOpen = ref(false);
const isModalOpen = ref(false);
const selectedMedia = ref(null);

// Log para depuração
/* onMounted(() => {
    console.log('Unidade recebida no frontend:', props.unidade);
    console.log('Órgãos compartilhados (props):', props.unidade.orgaosCompartilhados);
    console.log('Órgãos compartilhados (computed):', orgaosCompartilhados.value);
}); */

watch(() => page.props.flash, (flash) => {
    flashMessage.value = flash?.success || flash?.error || flash?.banner || null;
    if (flashMessage.value) {
        setTimeout(() => (flashMessage.value = null), 5000);
    }
}, { immediate: true, deep: true });

const tabs = [
    { id: 'dados-gerais', label: 'Dados Gerais', icon: 'fa-info-circle' },
    { id: 'acessibilidade', label: 'Acessibilidade', icon: 'fa-wheelchair' },
    { id: 'informacoes', label: 'Informações Estruturais', icon: 'fa-building' },
    { id: 'midias', label: 'Mídias', icon: 'fa-image' },
];

const changeTab = (tabId) => {
    activeTab.value = tabId;
    mobileMenuOpen.value = false;
};

const isImage = (midia) => midia.url && /\.(jpg|jpeg|png|gif|webp)$/i.test(midia.url);

const openModal = (midia) => {
    selectedMedia.value = midia;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedMedia.value = null;
};

const getStatusLabel = computed(() => {
    if (!props.unidade?.status) return { text: 'Desconhecido', class: 'bg-gray-100 text-gray-800' };
    switch(props.unidade.status) {
        case 'pendente_avaliacao': return { text: 'Pendente de Avaliação', class: 'bg-yellow-100 text-yellow-800' };
        case 'aprovada': return { text: 'Aprovado', class: 'bg-green-100 text-green-800' };
        case 'reprovada': return { text: 'Reprovado', class: 'bg-red-100 text-red-800' };
        case 'em_revisao': return { text: 'Em Revisão', class: 'bg-blue-100 text-blue-800' };
        default: return { text: 'Desconhecido', class: 'bg-gray-100 text-gray-800' };
    }
});

const formatarEndereco = computed(() => {
    const { rua, numero, bairro, cidade, cep } = props.unidade || {};
    let endereco = [];
    if (rua) endereco.push(`${rua}${numero ? `, ${numero}` : ', S/N'}`);
    if (bairro) endereco.push(bairro);
    if (cidade) endereco.push(cidade);
    if (cep) endereco.push(`CEP: ${cep}`);
    return endereco.length > 0 ? endereco.join(' - ') : 'Não informado';
});

const formatarTelefones = computed(() => {
    const { telefone_1, telefone_2 } = props.unidade || {};
    const tels = [telefone_1, telefone_2].filter(t => t);
    return tels.length > 0 ? tels.join(' / ') : 'Não informado';
});
</script>

<template>
    <AppLayout :title="`Visualizar Unidade: ${unidade?.nome || 'Carregando...'}`">
        <template #header>
            <div class="flex flex-wrap items-center justify-between w-full">
                <div class="flex items-center space-x-4 mb-2 sm:mb-0">
                    <Link href="/dashboard">
                        <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-10 sm:h-14 w-auto" />
                    </Link>
                    <div class="border-l border-white h-8 hidden sm:block"></div>
                    <span class="text-white text-sm sm:text-xl font-semibold pl-2 truncate">
                        {{ unidade?.nome || 'Carregando...' }}
                    </span>
                </div>
            </div>
        </template>

        <div v-if="flashMessage" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 my-4">
            <div class="p-4 rounded-md shadow-sm transition-all" 
                :class="flashMessage.includes('sucesso') || flashMessage.includes('salva') ? 
                    'bg-green-100 text-green-700 border-l-4 border-green-500' : 
                    'bg-amber-100 text-amber-700 border-l-4 border-amber-500'">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i :class="flashMessage.includes('sucesso') || flashMessage.includes('salva') ? 
                            'fa-check-circle text-green-600' : 'fa-exclamation-circle text-amber-600'"></i>
                    </div>
                    <div class="ml-3">{{ flashMessage }}</div>
                    <button @click="flashMessage = null" class="ml-auto text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-t-lg overflow-hidden shadow-md">
                    <div class="hidden sm:block border-b border-gray-200">
                        <nav class="flex space-x-2 md:space-x-8 overflow-x-auto scrollbar-thin" aria-label="Abas">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="changeTab(tab.id)"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-amber-500 text-amber-600 bg-amber-50'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs md:text-sm flex items-center transition-all'
                                ]"
                            >
                                <i :class="`fas ${tab.icon} mr-2`"></i>
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>
                    
                    <div class="sm:hidden bg-white p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-indigo-600 font-medium">
                                <i :class="`fas ${tabs.find(t => t.id === activeTab)?.icon || 'fa-info-circle'} mr-2`"></i>
                                {{ tabs.find(t => t.id === activeTab)?.label || 'Dados Gerais' }}
                            </span>
                            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
                                <i :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                            </button>
                        </div>
                        
                        <div v-if="mobileMenuOpen" class="mt-2 space-y-1 bg-gray-50 rounded-md p-2 transition-all">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="changeTab(tab.id)"
                                class="block w-full text-left px-3 py-2 rounded-md text-sm"
                                :class="activeTab === tab.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100'"
                            >
                                <i :class="`fas ${tab.icon} mr-2`"></i>
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 bg-white">
                        <div v-if="unidade?.status" class="mb-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-gray-600">Status:</span>
                                <span 
                                    class="px-3 py-1 rounded-full text-xs sm:text-sm font-medium shadow-sm"
                                    :class="getStatusLabel.class"
                                >
                                    {{ getStatusLabel.text }}
                                </span>
                            </div>
                            <div v-if="unidade?.rejection_reason && unidade.status === 'reprovada'" class="mt-4">
                                <div class="bg-red-50 p-3 rounded-md shadow-sm">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Motivo da Reprovação:</dt>
                                    <dd class="mt-1 whitespace-pre-line text-red-700">{{ unidade.rejection_reason }}</dd>

                                    <div class="flex justify-end">
                                        <Link 
                                            v-if="unidade?.status && unidade.status !== 'aprovada'" 
                                            :href="route('unidades.edit', { team: unidade.team_id, unidade: unidade.id })" 
                                            class="inline-flex items-center px-4 py-2 bg-black border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <i class="fas fa-edit mr-2"></i> Editar Informações
                                        </Link>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'dados-gerais'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Informações Básicas</h3>
                                <dl class="grid grid-cols-1 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nome:</dt>
                                        <dd class="mt-1">{{ unidade?.nome || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Unidade Gestora:</dt>
                                        <dd class="mt-1">{{ unidade?.srpc || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Unidade Sub-Gestora:</dt>
                                        <dd class="mt-1">{{ unidade?.dspc || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Tipo Estrutural:</dt>
                                        <dd class="mt-1">{{ unidade?.tipo_estrutural || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Sede:</dt>
                                        <dd class="mt-1">{{ unidade?.sede ? 'Sim' : 'Não' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Localização e Contatos</h3>
                                <dl class="grid grid-cols-1 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Endereço:</dt>
                                        <dd class="mt-1">{{ formatarEndereco }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Coordenadas:</dt>
                                        <dd class="mt-1">
                                            <span v-if="unidade?.latitude && unidade?.longitude" class="inline-flex items-center">
                                                <span>Lat: {{ unidade.latitude }}, Lng: {{ unidade.longitude }}</span>
                                                <a 
                                                    :href="`https://www.google.com/maps?q=${unidade.latitude},${unidade.longitude}`" 
                                                    target="_blank"
                                                    class="ml-2 text-blue-500 hover:text-blue-700"
                                                    title="Ver no Google Maps"
                                                >
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </a>
                                            </span>
                                            <span v-else>Não informado</span>
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Email:</dt>
                                        <dd class="mt-1">
                                            <a v-if="unidade?.email" :href="`mailto:${unidade.email}`" class="text-blue-500 hover:underline">
                                                {{ unidade.email }}
                                            </a>
                                            <span v-else>Não informado</span>
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Telefones:</dt>
                                        <dd class="mt-1">{{ formatarTelefones }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Tipo Judicial:</dt>
                                        <dd class="mt-1">{{ unidade?.tipo_judicial || 'Não informado' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div v-if="unidade?.imovel_compartilhado_orgao" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Compartilhamento de Imóvel</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Compartilhado com:</dt>
                                    <dd class="mt-1 flex flex-wrap gap-2">
                                        <span v-if="!unidade.orgaosCompartilhados || unidade.orgaosCompartilhados.length === 0" class="text-gray-600">
                                            Não informado
                                        </span>
                                        <span 
                                            v-else
                                            v-for="orgao in unidade.orgaosCompartilhados" 
                                            :key="orgao.id"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
                                        >
                                            {{ orgao.nome || 'Sem nome' }}
                                        </span>
                                    </dd>
                                </div>
                            </div>

                            <div v-if="unidade?.observacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Observações</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow whitespace-pre-line">
                                    {{ unidade.observacoes }}
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'acessibilidade'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm col-span-full">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Condições de Acessibilidade</h3>
                                <div v-if="acessibilidade" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Rampa de Acesso:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.rampa_acesso ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.rampa_acesso ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Corrimão:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.corrimao ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.corrimao ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Piso Tátil:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.piso_tatil ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.piso_tatil ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Banheiro Adaptado:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.banheiro_adaptado ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.banheiro_adaptado ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Elevador:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.elevador ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.elevador ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Sinalização em Braille:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${acessibilidade.sinalizacao_braile ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ acessibilidade.sinalizacao_braile ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                </div>
                                <div v-if="!acessibilidade" class="bg-white p-6 rounded-md shadow-sm text-center">
                                    <i class="fas fa-info-circle text-blue-500 text-4xl mb-4"></i>
                                    <p class="text-gray-600">Nenhuma informação de acessibilidade cadastrada para esta unidade.</p>
                                </div>
                                <div v-if="acessibilidade?.observacoes" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow mt-4">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Observações:</dt>
                                    <dd class="mt-1 whitespace-pre-line">{{ acessibilidade.observacoes }}</dd>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'informacoes'" class="space-y-6">
                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Características da Via e Serviços</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pavimentação da Rua:</dt>
                                        <dd class="mt-1">{{ informacoes.pavimentacao_rua || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Padrão de Energia:</dt>
                                        <dd class="mt-1">{{ informacoes.padrao_energia || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Subestação:</dt>
                                        <dd class="mt-1">{{ informacoes.subestacao || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Gerador de Energia:</dt>
                                        <dd class="mt-1">{{ informacoes.gerador_energia || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Para-Raio:</dt>
                                        <dd class="mt-1">{{ informacoes.para_raio || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Caixa d'Água:</dt>
                                        <dd class="mt-1">{{ informacoes.caixa_dagua || 'Não informado' }}</dd>
                                    </div>
                                </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow mt-4 w-fit">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Ponto de energia próximo a algum ponto de água para a instalação de um purificador de água:</dt>
                                        <dd class="mt-1">{{ informacoes.ponto_energia_agua || 'Não informado' }}</dd>
                                    </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Internet e Telefonia</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Internet Cabeada:</dt>
                                        <dd class="mt-1">{{ informacoes.internet_cabeada || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Provedor de Internet:</dt>
                                        <dd class="mt-1">{{ informacoes.internet_provedor || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Telefone Fixo:</dt>
                                        <dd class="mt-1">{{ informacoes.telefone_fixo || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Telefone Móvel:</dt>
                                        <dd class="mt-1">{{ informacoes.telefone_movel || 'Não informado' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Características do Imóvel</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Área Aproximada da Unidade (Área Construída) m²:</dt>
                                        <dd class="mt-1">{{ informacoes.area_aproximada_unidade ? `${informacoes.area_aproximada_unidade} m²` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Área Aproximada do Terreno (m²):</dt>
                                        <dd class="mt-1">{{ informacoes.area_aproximada_terreno ? `${informacoes.area_aproximada_terreno} m²` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Quantidade de Pavimentos:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_pavimentos || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Cercado por Muros:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.cercado_muros ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.cercado_muros ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Estacionamento Interno:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.estacionamento_interno ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.estacionamento_interno ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Estacionamento Externo:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.estacionamento_externo ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.estacionamento_externo ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Recuos</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Recuo Frontal:</dt>
                                        <dd class="mt-1">{{ informacoes.recuo_frontal ? `${informacoes.recuo_frontal} m` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Recuo Lateral:</dt>
                                        <dd class="mt-1">{{ informacoes.recuo_lateral ? `${informacoes.recuo_lateral} m` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Recuo Fundos:</dt>
                                        <dd class="mt-1">{{ informacoes.recuo_fundos ? `${informacoes.recuo_fundos} m` : 'Não informado' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Quantitativos de Espaços</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Recepções:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_recepcao || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">WCs Públicos:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_wc_publico || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Gabinetes:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_gabinetes || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Salas de Oitiva:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_sala_oitiva || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">WCs Servidores:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_wc_servidores || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Alojamentos M:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_alojamento_masculino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">WCs Aloj. M:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_wc_alojamento_masculino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Alojamentos F:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_alojamento_feminino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">WCs Aloj. F:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_wc_alojamento_feminino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Xadrez masculino:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_xadrez_masculino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Área Xadrez masculino:</dt>
                                        <dd class="mt-1">{{ informacoes.area_xadrez_masculino ? `${informacoes.area_xadrez_masculino} m²` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Xadrez feminino:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_xadrez_feminino || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Área Xadrez feminino:</dt>
                                        <dd class="mt-1">{{ informacoes.area_xadrez_feminino ? `${informacoes.area_xadrez_feminino} m²` : 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Salas Identificação:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_sala_identificacao || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Cozinhas:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_cozinha || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Áreas de Serviço:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_area_servico || '0' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Depósitos Apreensão:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_deposito_apreensao || '0' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Suficiência de Instalações</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Tomadas:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.tomadas_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.tomadas_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Luminárias:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.luminarias_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.luminarias_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pontos de Rede:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.pontos_rede_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.pontos_rede_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pontos de Telefone:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.pontos_telefone_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.pontos_telefone_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pontos de A/C:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.pontos_ar_condicionado_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.pontos_ar_condicionado_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pontos Hidráulicos:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.pontos_hidraulicos_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.pontos_hidraulicos_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pontos Sanitários:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.pontos_sanitarios_suficientes ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                            {{ informacoes.pontos_sanitarios_suficientes ? 'Suficientes' : 'Insuficientes' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Acabamentos</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Piso:</dt>
                                        <dd class="mt-1">{{ informacoes.piso || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Parede:</dt>
                                        <dd class="mt-1">{{ informacoes.parede || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Esquadrias:</dt>
                                        <dd class="mt-1">{{ informacoes.esquadrias || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Louças e Metais:</dt>
                                        <dd class="mt-1">{{ informacoes.loucas_metais || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Forro/Laje:</dt>
                                        <dd class="mt-1">{{ informacoes.forro_lage || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Cobertura:</dt>
                                        <dd class="mt-1">{{ informacoes.cobertura || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Pintura:</dt>
                                        <dd class="mt-1">{{ informacoes.pintura || 'Não informado' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Equipamentos de Segurança</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Extintor Pó Químico:</dt>
                                        <dd class="mt-1">{{ informacoes.extintor_po_quimico || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Extintor CO2:</dt>
                                        <dd class="mt-1">{{ informacoes.extintor_co2 || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Extintor Água:</dt>
                                        <dd class="mt-1">{{ informacoes.extintor_agua || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Placas de Emergência Para Incêndio:</dt>
                                        <dd class="mt-1">{{ informacoes.placa_incendio || 'Não informado' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!informacoes" class="bg-white p-6 rounded-md shadow-sm text-center">
                                <i class="fas fa-info-circle text-blue-500 text-4xl mb-4"></i>
                                <p class="text-gray-600">Nenhuma informação estrutural cadastrada para esta unidade.</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'midias'" class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Galeria de Mídias</h3>
                                <div v-if="midias && midias.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div 
                                        v-for="(midia, index) in midias" 
                                        :key="index" 
                                        class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all"
                                    >
                                        <div v-if="isImage(midia)" class="relative h-48 bg-gray-200">
                                            <img 
                                                :src="midia.url" 
                                                alt="Mídia" 
                                                class="w-full h-full object-cover cursor-pointer"
                                                @click="openModal(midia)"
                                            />
                                        </div>
                                        <div class="p-4">
                                            <h4 class="font-medium text-gray-900 truncate">
                                                {{ midia.midia_tipo?.nome || 'Arquivo' }}
                                            </h4>
                                            <p v-if="midia.tamanho" class="text-xs text-gray-500 mt-1">
                                                {{ (midia.tamanho / 1024).toFixed(2) }} KB
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="bg-white p-6 rounded-md shadow-sm text-center">
                                    <i class="fas fa-photo-video text-blue-500 text-4xl mb-4"></i>
                                    <p class="text-gray-600">Nenhuma mídia disponível para esta unidade.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[1000]" @click.self="closeModal">
            <div class="bg-white rounded-lg p-4 max-w-3xl w-full mx-4 relative">
                <button @click="closeModal" class="absolute top-2 right-2 text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <div class="flex flex-col items-center">
                    <img 
                        :src="selectedMedia?.url" 
                        alt="Mídia Ampliada" 
                        class="max-h-[70vh] max-w-full object-contain rounded-lg"
                    />
                    <div class="mt-4 flex items-center justify-between w-full">
                        <h4 class="font-medium text-gray-900 truncate">
                            {{ selectedMedia?.midia_tipo?.nome || 'Arquivo' }}
                        </h4>
                        <a :href="selectedMedia?.url" download class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-all">
                            <i class="fas fa-download mr-2"></i> Baixar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.transition-all {
    transition: all 0.3s ease;
}

.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.scrollbar-thin {
    scrollbar-width: thin;
}

@media (max-width: 640px) {
    .grid {
        grid-gap: 0.75rem;
    }
    .p-4 {
        padding: 0.75rem;
    }
}

.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>