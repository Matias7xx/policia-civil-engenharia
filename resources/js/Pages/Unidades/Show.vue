<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    availableRoles: Array,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    orgaos: Array,
    permissions: Object,
    userPermissions: Object,
});

const midiasReais = computed(() => {
    if (!props.midias || !Array.isArray(props.midias)) {
        return [];
    }
    
    // Filtrar apenas mídias reais (não os registros de "não possui")
    return props.midias.filter(midia => {
        // Excluir mídias que têm path = 'nao_possui_ambiente'
        if (midia.path === 'nao_possui_ambiente') {
            return false;
        }
        
        // Excluir mídias que têm o flag nao_possui_ambiente = true no pivot
        if (midia.pivot && midia.pivot.nao_possui_ambiente === true) {
            return false;
        }
        
        // Incluir apenas mídias com URL válida e que são imagens
        return midia.url && midia.url !== null;
    });
});

const ambientesNaoPossui = computed(() => {
    if (!props.midias || !Array.isArray(props.midias)) {
        return [];
    }
    
    // Filtrar apenas registros de "não possui ambiente"
    return props.midias.filter(midia => {
        // Incluir mídias que têm path = 'nao_possui_ambiente'
        if (midia.path === 'nao_possui_ambiente') {
            return true;
        }
        
        // Incluir mídias que têm o flag nao_possui_ambiente = true no pivot
        if (midia.pivot && midia.pivot.nao_possui_ambiente === true) {
            return true;
        }
        
        return false;
    }).map(midia => {
        // Formatação dos nomes para exibição
        const nome = midia.midia_tipo?.nome || midia.midiaTipo?.nome || 'Ambiente desconhecido';
        return {
            ...midia,
            nomeFormatado: formatarNomeTipoMidia(nome)
        };
    });
});

// Função para formatar nomes dos tipos de mídia
const formatarNomeTipoMidia = (nome) => {
    // Casos específicos - Área Interna
    if (nome === 'foto_frente') return 'Frente';
    if (nome === 'foto_lateral_1') return 'Lateral esquerda';
    if (nome === 'foto_lateral_2') return 'Lateral direita';
    if (nome === 'foto_fundos') return 'Fundos';
    if (nome === 'foto_medidor_agua') return 'Nº medidor de água';
    if (nome === 'foto_medidor_energia') return 'Nº medidor de energia';
    
    if (nome === 'recepção') return 'Recepção';
    if (nome === 'sala_oitiva') return 'Sala de oitiva';
    if (nome === 'sala_boletim_de_ocorrência') return 'Sala de BO';
    if (nome === 'gabinete_01') return 'Gabinete 01';
    if (nome === 'gabinete_02') return 'Gabinete 02';
    if (nome === 'cartório_01') return 'Cartório 01';
    if (nome === 'cartório_02') return 'Cartório 02';
    if (nome === 'sala_de_agentes') return 'Sala de agentes';
    if (nome === 'wc_público_masculino') return 'WC público masculino';
    if (nome === 'wc_público_feminino') return 'WC público feminino';
    if (nome === 'wc_servidores_masculino') return 'WC servidores masculino';
    if (nome === 'wc_servidores_feminino') return 'WC servidores feminino';
    if (nome === 'alojamento_masculino') return 'Alojamento masculino';
    if (nome === 'alojamento_feminino') return 'Alojamento feminino';
    if (nome === 'xadrez_masculino_01') return 'Xadrez masculino 01';
    if (nome === 'xadrez_masculino_02') return 'Xadrez masculino 02';
    if (nome === 'xadrez_masculino_03') return 'Xadrez masculino 03';
    if (nome === 'xadrez_feminino_01') return 'Xadrez feminino 01';
    if (nome === 'xadrez_feminino_02') return 'Xadrez feminino 02';
    if (nome === 'xadrez_feminino_03') return 'Xadrez feminino 03';
    if (nome === 'parlatório') return 'Parlatório';
    if (nome === 'sala_identificação') return 'Sala de identificação';
    if (nome === 'área_de_serviço') return 'Área de serviço';
    if (nome === 'cozinha') return 'Copa/Cozinha';
    if (nome === 'garagem') return 'Garagem';
    if (nome === 'dispensa') return 'Dispensa';
    if (nome === 'depósito_apreensão') return 'Depósito de apreensão';
    if (nome === 'porta_principal') return 'Porta principal';
    if (nome === 'luminarias_emergencia') return 'Luminárias emergência';
    if (nome === 'escada_acesso') return 'Escada de acesso';
    if (nome === 'demarcacao_extintor') return 'Demarcação do extintor';
    if (nome === 'rampa_acesso') return 'Rampa de acesso';
    if (nome === 'corrimao') return 'Corrimão';
    if (nome === 'piso_tatil') return 'Piso tátil';
    if (nome === 'banheiro_adaptado') return 'Banheiro adaptado';
    if (nome === 'elevador') return 'Elevador';
    if (nome === 'sinalizacao_braile') return 'Sinalização em Braile';
    
    // Formato padrão para outros tipos
    return nome.replace('foto_', '').replace(/_/g, ' ');
};

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

const isImage = (midia) => {
    // Usar o atributo is_imagem (Model)
    return midia.is_imagem === true || midia.is_imagem === 1;
};

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
                            
                            <!-- Seção de Compartilhamento com Unidades -->
                            <div v-if="unidade?.imovel_compartilhado_unidades" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Compartilhamento com Unidades Policiais</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Unidades que compartilham o imóvel:</dt>
                                    <dd class="mt-1">
                                        <span v-if="!unidade.imovel_compartilhado_unidades_texto?.trim()" class="text-gray-600">
                                            Não informado
                                        </span>
                                        <span v-else class="text-gray-900">
                                            {{ unidade.imovel_compartilhado_unidades_texto }}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                            
                            <!-- Seção de Compartilhamento com Órgãos -->
                            <div v-if="unidade?.imovel_compartilhado_orgao" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Compartilhamento com Órgãos</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Órgãos que compartilham o imóvel:</dt>
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
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Demarcação no piso abaixo do extintor:</dt>
                                        <dd class="mt-1">{{ informacoes.demarcacao_piso_extintor || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Placas de Emergência Para Incêndio:</dt>
                                        <dd class="mt-1">{{ informacoes.placa_incendio || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Possui luminárias de emergência:</dt>
                                        <dd class="mt-1">{{ informacoes.possui_luminarias_emergencia || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">A porta principal abre para fora:</dt>
                                        <dd class="mt-1">{{ informacoes.porta_principal_abre_fora || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Escada de acesso possui corrimão:</dt>
                                        <dd class="mt-1">{{ informacoes.escada_possui_corrimao || 'Não informado' }}</dd>
                                    </div>
                                </div>
                            </div>

                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Informações sobre Veículos Apreendidos</h3>
                                
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow mb-4">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Na unidade policial tem espaço para guardar veículos apreendidos:</dt>
                                    <dd class="mt-1 flex items-center">
                                        <i :class="`fas ${informacoes.tem_espaco_veiculos_apreendidos ? 'fa-check text-green-500' : 'fa-times text-red-500'} mr-2`"></i>
                                        {{ informacoes.tem_espaco_veiculos_apreendidos ? 'Sim' : 'Não' }}
                                    </dd>
                                </div>

                                <div v-if="informacoes.tem_espaco_veiculos_apreendidos" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Quantos veículos do tipo automóvel podem ser guardados:</dt>
                                        <dd class="mt-1 font-semibold text-lg">
                                            {{ informacoes.qtd_max_veiculos_automovel ? `${informacoes.qtd_max_veiculos_automovel} veículo(s)` : 'Não informado' }}
                                        </dd>
                                    </div>

                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">O local apresenta segurança:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <span 
                                                class="inline-flex items-center px-2.5 py-2.5 rounded-full text-xs font-semibold"
                                                :class="{
                                                    'bg-green-100 text-green-800': informacoes.seguranca_local_veiculos === 'sim',
                                                    'bg-red-100 text-red-800': informacoes.seguranca_local_veiculos === 'nao',
                                                    'bg-yellow-100 text-yellow-800': informacoes.seguranca_local_veiculos === 'parcial',
                                                    'bg-gray-100 text-gray-800': !informacoes.seguranca_local_veiculos
                                                }"
                                            >
                                                <i 
                                                    :class="{
                                                        'fas fa-shield-alt mr-1': informacoes.seguranca_local_veiculos === 'sim',
                                                        'fas fa-exclamation-triangle mr-1': informacoes.seguranca_local_veiculos === 'nao',
                                                        'fas fa-shield-halved mr-1': informacoes.seguranca_local_veiculos === 'parcial',
                                                        'fas fa-question mr-1': !informacoes.seguranca_local_veiculos
                                                    }"
                                                ></i>
                                                {{ 
                                                    informacoes.seguranca_local_veiculos === 'sim' ? 'Sim' :
                                                    informacoes.seguranca_local_veiculos === 'nao' ? 'Não' :
                                                    informacoes.seguranca_local_veiculos === 'parcial' ? 'Parcial' :
                                                    'Não informado'
                                                }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Histórico de invasão/subtração:</dt>
                                        <dd class="mt-1 flex items-center">
                                            <i :class="`fas ${informacoes.historico_invasao_veiculo ? 'fa-exclamation-triangle text-red-500' : 'fa-check text-green-500'} mr-2`"></i>
                                            <span :class="informacoes.historico_invasao_veiculo ? 'text-red-600 font-semibold' : 'text-green-600'">
                                                {{ informacoes.historico_invasao_veiculo ? 'Sim, há histórico' : 'Não há histórico' }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div v-if="informacoes.observacoes_veiculos_apreendidos" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow sm:col-span-2 md:col-span-3">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Observações sobre veículos apreendidos:</dt>
                                        <dd class="mt-1 whitespace-pre-line text-gray-700">{{ informacoes.observacoes_veiculos_apreendidos }}</dd>
                                    </div>
                                </div>

                                <div v-else class="bg-yellow-50 p-3 rounded-md border border-yellow-200">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                                        <p class="text-yellow-800 text-sm">
                                            Esta unidade não possui espaço disponível para guardar veículos apreendidos.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!informacoes" class="bg-white p-6 rounded-md shadow-sm text-center">
                                <i class="fas fa-info-circle text-blue-500 text-4xl mb-4"></i>
                                <p class="text-gray-600">Nenhuma informação estrutural cadastrada para esta unidade.</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'midias'" class="grid grid-cols-1 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6 flex items-center">
                                    <i class="fas fa-images text-blue-500 mr-2"></i>
                                    Galeria de Fotos da Unidade
                                    <span v-if="midiasReais?.length" class="ml-2 text-sm font-normal text-gray-600">
                                        ({{ midiasReais.length }} {{ midiasReais.length === 1 ? 'foto' : 'fotos' }})
                                    </span>
                                </h3>
                                
                                <div v-if="midiasReais && midiasReais.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    <div 
                                        v-for="midia in midiasReais" 
                                        :key="midia.id" 
                                        class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group"
                                    >
                                        <!-- Container da imagem -->
                                        <div class="relative aspect-square bg-gray-100">
                                            <!-- Se for imagem -->
                                            <div v-if="isImage(midia)" class="relative h-full">
                                                <img 
                                                    :src="midia.url" 
                                                    @click="openModal(midia)" 
                                                    class="w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-300"
                                                    :alt="`Foto - ${formatarNomeTipoMidia(midia.midia_tipo?.nome || midia.midiaTipo?.nome)}`"
                                                    @error="$event.target.src = '/images/placeholder-image.jpg'"
                                                    loading="lazy"
                                                />
                                                
                                                <!-- Overlay com ações - aparece no hover -->
                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                    <div class="flex space-x-3">
                                                        <button 
                                                            @click="openModal(midia)"
                                                            class="flex items-center px-4 py-2 bg-white text-gray-800 rounded-lg shadow-lg hover:bg-gray-100 transition-colors"
                                                        >
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            Ampliar
                                                        </button>
                                                        <a 
                                                            :href="midia.download_url || midia.url" 
                                                            class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow-lg hover:bg-blue-700 transition-colors"
                                                            download
                                                        >
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            Baixar
                                                        </a>
                                                    </div>
                                                </div>
                                                
                                                <!-- Badge do tipo (canto superior) -->
                                                <div class="absolute top-3 left-3 bg-black bg-opacity-70 text-white text-xs px-3 py-1 rounded-full font-medium">
                                                    {{ formatarNomeTipoMidia(midia.midia_tipo?.nome || midia.midiaTipo?.nome) }}
                                                </div>

                                                <!-- Badge do tamanho (canto superior direito) -->
                                                <div v-if="midia.tamanho_formatado" class="absolute top-3 right-3 bg-blue-600 bg-opacity-90 text-white text-xs px-2 py-1 rounded-full">
                                                    {{ midia.tamanho_formatado }}
                                                </div>
                                            </div>
                                            
                                            <!-- Se não for imagem -->
                                            <div v-else class="flex flex-col items-center justify-center h-full p-6 text-center">
                                                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-3">
                                                    <i class="fas fa-file text-gray-500 text-2xl"></i>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-4 font-medium">Arquivo</p>
                                                <div class="flex space-x-2">
                                                    <a 
                                                        :href="midia.url" 
                                                        target="_blank" 
                                                        class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition-colors"
                                                    >
                                                        <i class="fas fa-eye mr-1"></i>
                                                        Ver
                                                    </a>
                                                    <a 
                                                        :href="midia.download_url || midia.url" 
                                                        download
                                                        class="px-3 py-2 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition-colors"
                                                    >
                                                        <i class="fas fa-download mr-1"></i>
                                                        Baixar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Informações do arquivo -->
                                        <div class="p-4">
                                            <h4 class="font-semibold text-gray-900 text-sm mb-1 truncate">
                                                {{ formatarNomeTipoMidia(midia.midia_tipo?.nome || midia.midiaTipo?.nome || 'Arquivo') }}
                                            </h4>
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span v-if="midia.tamanho_formatado">{{ midia.tamanho_formatado }}</span>
                                                <span v-if="midia.created_at" class="text-xs">
                                                    {{ new Date(midia.created_at).toLocaleDateString('pt-BR') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Estado vazio -->
                                <div v-else class="bg-white p-12 rounded-xl shadow-sm text-center">
                                    <div class="max-w-sm mx-auto">
                                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-photo-video text-gray-400 text-3xl"></i>
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma mídia encontrada</h4>
                                        <p class="text-gray-600 text-sm leading-relaxed">
                                            Não há fotos ou arquivos cadastrados para esta unidade ainda.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ambientes que a Unidade Não Possui -->
                            <div v-if="ambientesNaoPossui && ambientesNaoPossui.length > 0" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4 flex items-center">
                                    <i class="fas fa-ban text-orange-500 mr-2"></i>
                                    Ambientes que a Unidade Não Possui
                                    <span class="ml-2 text-sm font-normal text-gray-600">({{ ambientesNaoPossui.length }} ambiente{{ ambientesNaoPossui.length === 1 ? '' : 's' }})</span>
                                </h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div 
                                        v-for="ambiente in ambientesNaoPossui" 
                                        :key="ambiente.id" 
                                        class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all border-l-4 border-gray-400"
                                    >
                                        <div class="flex items-center justify-center mb-3">
                                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-home-slash text-orange-600 text-xl"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <h4 class="font-medium text-gray-900 text-sm mb-1">
                                                {{ ambiente.nomeFormatado }}
                                            </h4>
                                            <p class="text-xs text-gray-500 mb-2">Ambiente não disponível</p>
                                            
                                            <div class="flex items-center justify-center">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <i class="fas fa-times mr-1"></i>
                                                    Não possui
                                                </span>
                                            </div>
                                            
                                            <!-- Observações se existirem -->
                                            <div v-if="ambiente.pivot && ambiente.pivot.observacoes" class="mt-2 text-xs text-gray-600 italic p-2 bg-gray-50 rounded">
                                                "{{ ambiente.pivot.observacoes }}"
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Informação adicional -->
                                <div class="mt-4 bg-orange-50 border border-orange-200 rounded-md p-3">
                                    <div class="flex items-start">
                                        <i class="fas fa-info-circle text-orange-500 mr-2 mt-0.5 flex-shrink-0"></i>
                                        <div class="text-sm">
                                            <p class="font-medium text-orange-800 mb-1">Informação:</p>
                                            <p class="text-orange-700">
                                                Foi marcado que "A unidade não possui este ambiente" durante o cadastro da unidade. 
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Caso não haja nenhuma mídia nem ambientes "não possui" -->
                            <div v-if="(!midiasReais || midiasReais.length === 0) && (!ambientesNaoPossui || ambientesNaoPossui.length === 0)" class="bg-white p-12 rounded-xl shadow-sm text-center">
                                <div class="max-w-md mx-auto">
                                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-photo-video text-gray-400 text-4xl"></i>
                                    </div>
                                    <h4 class="text-xl font-semibold text-gray-900 mb-3">Nenhuma informação de mídia disponível</h4>
                                    <p class="text-gray-600 leading-relaxed">
                                        Não há fotos cadastradas nem ambientes marcados como "não possui" para esta unidade.
                                    </p>
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

/* imagens mantem proporção */
.aspect-square {
    aspect-ratio: 1 / 1;
}

/* transição de hover das imagens */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

/* Animações para os elementos */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.group-hover\:opacity-100 {
    transition: opacity 0.3s ease;
}

/* Estilo personalizado para os badges */
.bg-opacity-70 {
    background-color: rgba(0, 0, 0, 0.7);
}

.bg-opacity-90 {
    background-color: rgba(37, 99, 235, 0.9);
}
</style>