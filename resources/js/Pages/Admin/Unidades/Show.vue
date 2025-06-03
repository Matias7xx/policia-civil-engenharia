<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvaliacaoForm from '@/Pages/Admin/Unidades/Partials/AvaliacaoForm.vue';
import AvaliacaoHistorico from '@/Pages/Admin/Unidades/Partials/AvaliacaoHistorico.vue';
import { ref, watch, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import { ChartBarIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    informacoes: Object,
    midias: Array,
    orgaos: Array,
    permissions: Object,
    avaliacoes: Array,
    isSuperAdmin: Boolean,
});

const page = usePage();
const activeTab = ref('dados-gerais');
const activeAvaliacaoTab = ref('historico');
const flashMessage = ref(null);
const mobileMenuOpen = ref(false);

// Estado do modal de mídia
const isModalOpen = ref(false);
const selectedMedia = ref(null);

// Estado do modal de reprovação
const isRejectionModalOpen = ref(false);
const rejectionReason = ref('');

// Formulário para edição de contrato de locação
const contratoForm = useForm({
    nome_proprietario: props.unidade?.contrato_locacao?.nome_proprietario || '',
    cpf_cnpj: props.unidade?.contrato_locacao?.cpf_cnpj || '',
    telefone: props.unidade?.contrato_locacao?.telefone || '',
    valor_locacao: props.unidade?.contrato_locacao?.valor_locacao || '',
    data_inicio: props.unidade?.contrato_locacao?.data_inicio || '',
    data_fim: props.unidade?.contrato_locacao?.data_fim || '',
    anexo: null,
});

// Formulário para edição de cessão
const cessaoForm = useForm({
    orgao_cedente: props.unidade?.orgao_cedente || '',
    termo_cessao: null,
    prazo_cessao: props.unidade?.prazo_cessao || '',
});

// Formulário para atualização de status
const statusForm = useForm({
    status: '',
    rejection_reason: '',
});

// Máscara para o campo telefone
const telefoneMask = { mask: '(00) 00000-0000', maxLength: 15, lazy: false, overwrite: true };

// Máscara para o campo CPF/CNPJ
const cpfCnpjMask = {
    mask: [
        { mask: '000.000.000-00', maxLength: 14 }, // CPF com formatação
        { mask: '00.000.000/0000-00', maxLength: 18 } // CNPJ com formatação
    ],
    dispatch: function(appended, dynamicMasked) {
        const numericValue = (dynamicMasked.value + appended).replace(/\D/g, '');
        return numericValue.length <= 11 
            ? dynamicMasked.compiledMasks[0] 
            : dynamicMasked.compiledMasks[1];
    },
};

// Exibe mensagens de flash
watch(() => page.props.flash, (flash) => {
    flashMessage.value = flash?.success || flash?.error || flash?.banner || null;
    if (flashMessage.value) {
        setTimeout(() => (flashMessage.value = null), 5000);
    }
}, { immediate: true, deep: true });

// Aba ativa
const tabs = [
    { id: 'dados-gerais', label: 'Dados Gerais', icon: 'fa-info-circle' },
    { id: 'acessibilidade', label: 'Acessibilidade', icon: 'fa-wheelchair' },
    { id: 'informacoes', label: 'Informações Estruturais', icon: 'fa-building' },
    { id: 'midias', label: 'Mídias', icon: 'fa-image' },
    { id: 'avaliacoes', label: 'Avaliações', icon: 'fa-star' },
];

const changeTab = (tabId) => {
    activeTab.value = tabId;
    mobileMenuOpen.value = false;
};

// Verificar tipos de mídia
const isImage = (midia) => midia.url && /\.(jpg|jpeg|png|gif|webp)$/i.test(midia.url);

// Função para abrir o modal com a imagem selecionada
const openModal = (midia) => {
    selectedMedia.value = midia;
    isModalOpen.value = true;
};

// Função para fechar o modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedMedia.value = null;
};

// Função para abrir o modal de reprovação
const openRejectionModal = () => {
    rejectionReason.value = '';
    statusForm.status = 'reprovada';
    isRejectionModalOpen.value = true;
};

// Função para fechar o modal de reprovação
const closeRejectionModal = () => {
    isRejectionModalOpen.value = false;
    statusForm.reset();
    rejectionReason.value = '';
};

// Função para atualizar o status
const updateStatus = (status) => {
    statusForm.status = status;
    if (status === 'reprovada') {
        openRejectionModal();
    } else {
        statusForm.rejection_reason = null;
        submitStatus();
    }
};

// Função para enviar o status (com motivo, se reprovada)
const submitStatus = () => {
    if (statusForm.status === 'reprovada') {
        statusForm.rejection_reason = rejectionReason.value.trim();
        if (!statusForm.rejection_reason) {
            flashMessage.value = 'Por favor, forneça um motivo para a reprovação.';
            setTimeout(() => (flashMessage.value = null), 5000);
            return;
        }
    }
    statusForm.post(route('admin.unidades.updateStatus', props.unidade.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value = 'Status atualizado com sucesso!';
            closeRejectionModal();
            setTimeout(() => (flashMessage.value = null), 5000);
        },
        onError: (errors) => {
            flashMessage.value = 'Erro ao atualizar o status: ' + (errors.rejection_reason || 'Verifique os dados.');
            setTimeout(() => (flashMessage.value = null), 5000);
        },
    });
};

// Formatando dados para exibição
const getStatusLabel = computed(() => {
    if (!props.unidade?.status) return { text: 'Desconhecido', class: 'bg-gray-100 text-gray-800' };
    
    switch(props.unidade.status) {
        case 'pendente_avaliacao':
            return { text: 'Pendente de Avaliação', class: 'bg-yellow-100 text-yellow-800' };
        case 'aprovada':
            return { text: 'Aprovado', class: 'bg-green-100 text-green-800' };
        case 'reprovada':
            return { text: 'Reprovado', class: 'bg-red-100 text-red-800' };
        case 'em_revisao':
            return { text: 'Em Revisão', class: 'bg-blue-100 text-blue-800' };
        default:
            return { text: 'Desconhecido', class: 'bg-gray-100 text-gray-800' };
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

// Formatando a data
const formatarData = (data) => {
    if (!data) return 'Não informado';
    
    // Se for uma string no formato ISO YYYY-MM-DD
    if (typeof data === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(data)) {
        // Tratar explicitamente como data UTC para evitar problemas de fuso horário
        const [year, month, day] = data.split('-').map(Number);
        // Criando um objeto Date com ano, mês (0-11) e dia
        const date = new Date(Date.UTC(year, month - 1, day));
        return date.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
    }
    
    // Para outros formatos de data
    try {
        const date = new Date(data);
        // Garantir que a data seja tratada como UTC
        return new Date(date.getTime() + date.getTimezoneOffset() * 60000)
            .toLocaleDateString('pt-BR');
    } catch (e) {
        return 'Data inválida';
    }
};

// Função para salvar contrato de locação
const salvarContrato = () => {
    // Limpar a formatação do CPF/CNPJ e telefone antes de enviar
    const cleanedCpfCnpj = contratoForm.cpf_cnpj ? String(contratoForm.cpf_cnpj).replace(/\D/g, '').trim() : '';
    const cleanedTelefone = contratoForm.telefone ? String(contratoForm.telefone).replace(/\D/g, '').trim() : null;

    // Atualizar os valores no formulário
    contratoForm.cpf_cnpj = cleanedCpfCnpj;
    contratoForm.telefone = cleanedTelefone;

    contratoForm.post(route('admin.unidades.updateContrato', props.unidade.id), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            flashMessage.value = 'Contrato salvo com sucesso!';
            const updatedContrato = page.props.unidade.contrato_locacao || {};
            contratoForm.nome_proprietario = updatedContrato.nome_proprietario || '';
            contratoForm.cpf_cnpj = updatedContrato.cpf_cnpj || '';
            contratoForm.telefone = updatedContrato.telefone || '';
            contratoForm.valor_locacao = updatedContrato.valor_locacao || '';
            contratoForm.data_inicio = updatedContrato.data_inicio ? new Date(updatedContrato.data_inicio).toISOString().split('T')[0] : '';
            contratoForm.data_fim = updatedContrato.data_fim ? new Date(updatedContrato.data_fim).toISOString().split('T')[0] : '';
            contratoForm.anexo = null;
            setTimeout(() => (flashMessage.value = null), 5000);
        },
        onError: () => {
            flashMessage.value = 'Erro ao salvar o contrato. Verifique os campos.';
            setTimeout(() => (flashMessage.value = null), 5000);
        },
    });
};

// Função para salvar cessão
const salvarCessao = () => {
    cessaoForm.post(route('admin.unidades.updateCessao', props.unidade.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value = 'Dados de cessão salvos com sucesso!';
            setTimeout(() => (flashMessage.value = null), 5000);
        },
        onError: () => {
            flashMessage.value = 'Erro ao salvar os dados de cessão. Verifique os campos.';
            setTimeout(() => (flashMessage.value = null), 5000);
        },
    });
};
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

        <!-- Mensagem de Flash -->
        <div v-if="flashMessage" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 my-4">
            <div class="p-4 rounded-md shadow-sm transition-all" 
                :class="flashMessage.includes('sucesso') || flashMessage.includes('salva') ? 
                    'bg-green-100 text-green-700 border-l-4 border-green-500' : 
                    'bg-amber-100 text-amber-700 border-l-4 border-amber-500'">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas" :class="flashMessage.includes('sucesso') || flashMessage.includes('salva') ? 
                            'fa-check-circle text-green-600' : 'fa-exclamation-circle text-amber-600'"></i>
                    </div>
                    <div class="ml-3">{{ flashMessage }}</div>
                    <button @click="flashMessage = null" class="ml-auto text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mensagem de erro para acesso não autorizado -->
        <div v-if="page.props.errors?.unauthorized" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 my-4">
            <div class="p-4 rounded-md bg-red-100 text-red-700 border-l-4 border-red-500">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                    </div>
                    <div class="ml-3">{{ page.props.errors.unauthorized }}</div>
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Navegação de abas para desktop -->
                <div class="bg-white rounded-t-lg overflow-hidden shadow-md">
                    <div class="hidden sm:block border-b border-gray-200">
                        <nav class="flex space-x-2 md:space-x-8 overflow-x-auto scrollbar-thin" aria-label="Abas">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="changeTab(tab.id)"
                                :class="[activeTab === tab.id ? 'border-amber-500 text-amber-600 bg-amber-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs md:text-sm flex items-center transition-all']"
                            >
                                <i :class="`fas ${tab.icon} mr-2`"></i>
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Navegação mobile -->
                    <div class="sm:hidden bg-white p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-indigo-600 font-medium">
                                <i :class="`fas ${tabs.find(t => t.id === activeTab)?.icon || 'fa-info-circle'} mr-2`"></i>
                                {{ tabs.find(t => t.id === activeTab)?.label || 'Dados Gerais' }}
                            </span>
                            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
                                <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
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

                    <!-- Conteúdo das abas -->
                    <div class="p-4 sm:p-6 bg-white">
                        <!-- Status da unidade -->
                        <div v-if="unidade?.status" class="mb-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-gray-600">Preenchimento do Formulário:</span>
                                <span 
                                    class="px-3 py-1 rounded-full text-xs sm:text-sm font-medium shadow-sm"
                                    :class="getStatusLabel.class"
                                >
                                    {{ getStatusLabel.text }}
                                </span>
                                <!-- Botões de ação para SuperAdmin -->
                                <div v-if="isSuperAdmin" class="flex space-x-2">
                                    <button
                                        v-if="unidade.status !== 'aprovada'"
                                        @click="updateStatus('aprovada')"
                                        class="px-3 py-1 bg-green-500 text-white rounded-md text-xs sm:text-sm hover:bg-green-600"
                                    >
                                        Aprovar
                                    </button>
                                    <button
                                        v-if="unidade.status !== 'reprovada'"
                                        @click="updateStatus('reprovada')"
                                        class="px-3 py-1 bg-red-500 text-white rounded-md text-xs sm:text-sm hover:bg-red-600"
                                    >
                                        Reprovar
                                    </button>
                                    <button
                                        v-if="unidade.status !== 'em_revisao'"
                                        @click="updateStatus('em_revisao')"
                                        class="px-3 py-1 bg-blue-500 text-white rounded-md text-xs sm:text-sm hover:bg-blue-600"
                                    >
                                        Em Revisão
                                    </button>
                                </div>
                            </div>
                            <!-- Exibir motivo da reprovação -->
                            <div v-if="unidade?.rejection_reason && unidade.status === 'reprovada'" class="mt-4">
                                <div class="bg-red-50 p-3 rounded-md shadow-sm">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Motivo da Reprovação:</dt>
                                    <dd class="mt-1 whitespace-pre-line text-red-700">{{ unidade.rejection_reason }}</dd>
                                </div>
                            </div>

                            <div v-if="unidade?.rejection_reason && unidade.status !== 'reprovada'" class="mt-4">
                                <div class="bg-red-50 p-3 rounded-md shadow-sm">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Essa Unidade já foi reprovada</dt>
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Motivo da última Reprovação:</dt>
                                    <dd class="mt-1 whitespace-pre-line text-red-700">{{ unidade.rejection_reason }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de Reprovação -->
                        <div v-if="isRejectionModalOpen" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Motivo da Reprovação</h3>
                                <form @submit.prevent="submitStatus">
                                    <div class="mb-4">
                                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Descreva o motivo</label>
                                        <textarea
                                            id="rejection_reason"
                                            v-model="rejectionReason"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            rows="4"
                                            required
                                        ></textarea>
                                        <p v-if="statusForm.errors.rejection_reason" class="text-red-500 text-xs mt-1">
                                            {{ statusForm.errors.rejection_reason }}
                                        </p>
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            type="button"
                                            @click="closeRejectionModal"
                                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                        >
                                            Cancelar
                                        </button>
                                        <button
                                            type="submit"
                                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                            :disabled="statusForm.processing"
                                        >
                                            Confirmar Reprovação
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Dados Gerais -->
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
                                                    v-if="unidade.latitude && unidade.longitude"
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

                            <!-- Contrato de Locação (visualização) -->
                            <div v-if="unidade?.tipo_judicial === 'locado' && unidade?.contrato_locacao" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Contrato de Locação</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Proprietário:</dt>
                                        <dd class="mt-1">{{ unidade.contrato_locacao?.nome_proprietario || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">CPF/CNPJ:</dt>
                                        <dd class="mt-1">{{ unidade.contrato_locacao?.cpf_cnpj || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Telefone:</dt>
                                        <dd class="mt-1">{{ unidade.contrato_locacao?.telefone || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Valor:</dt>
                                        <dd class="mt-1">{{ unidade.contrato_locacao?.valor_locacao ? 
                                            `R$ ${Number(unidade.contrato_locacao.valor_locacao).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : 
                                            'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Início:</dt>
                                        <dd class="mt-1">{{ formatarData(unidade.contrato_locacao?.data_inicio) }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Fim:</dt>
                                        <dd class="mt-1">{{ formatarData(unidade.contrato_locacao?.data_fim) }}</dd>
                                    </div>
                                    <div v-if="unidade.contrato_locacao?.anexo" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Anexo:</dt>
                                        <dd class="mt-1">
                                            <a :href="route('admin.unidades.anexo', unidade.id)" target="_blank" class="text-blue-500 hover:underline">
                                                Visualizar Anexo
                                            </a>
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário de Edição de Contrato de Locação (apenas SuperAdmin) -->
                            <div v-if="isSuperAdmin && unidade?.tipo_judicial === 'locado'" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Editar Contrato de Locação</h3>
                                <form @submit.prevent="salvarContrato" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nome do Proprietário</label>
                                        <input v-model="contratoForm.nome_proprietario" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                                        <p v-if="contratoForm.errors.nome_proprietario" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.nome_proprietario }}</p>
                                    </div>
                                    <div>
                                        <label for="cpf_cnpj" class="block text-sm font-medium text-gray-700">CPF/CNPJ *</label>
                                        <TextInput
                                            id="cpf_cnpj"
                                            v-model="contratoForm.cpf_cnpj"
                                            type="text"
                                            v-imask="cpfCnpjMask"
                                            placeholder="CPF ou CNPJ"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        />
                                        <p v-if="contratoForm.errors.cpf_cnpj" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.cpf_cnpj }}</p>
                                    </div>
                                    <div>
                                        <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                                        <TextInput
                                            id="telefone"
                                            v-model="contratoForm.telefone"
                                            type="text"
                                            v-imask="telefoneMask"
                                            placeholder="(11) 987654321"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        />
                                        <p v-if="contratoForm.errors.telefone" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.telefone }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Valor da Locação (R$)</label>
                                        <input v-model="contratoForm.valor_locacao" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="contratoForm.errors.valor_locacao" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.valor_locacao }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Data de Início</label>
                                        <input v-model="contratoForm.data_inicio" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="contratoForm.errors.data_inicio" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.data_inicio }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Data de Fim</label>
                                        <input v-model="contratoForm.data_fim" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="contratoForm.errors.data_fim" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.data_fim }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Anexo</label>
                                        <input
                                            type="file" 
                                            @change="contratoForm.anexo = $event.target.files[0]" 
                                            accept="application/pdf"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="contratoForm.errors.anexo" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.anexo }}</p>
                                    </div>
                                    <div class="col-span-full">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Informações de Cessão (visualização) -->
                            <div v-if="unidade?.tipo_judicial === 'cedido'" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Informações de Cessão</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Órgão Cedente:</dt>
                                        <dd class="mt-1">{{ unidade?.orgao_cedente || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Prazo de Cessão:</dt>
                                        <dd class="mt-1">{{ formatarData(unidade?.prazo_cessao) }}</dd>
                                    </div>
                                    <div v-if="unidade.termo_cessao" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Termo de Cessão:</dt>
                                        <dd class="mt-1">
                                            <a :href="route('admin.unidades.termoCessao', unidade.id)" target="_blank" class="text-blue-500 hover:underline">
                                                Visualizar Termo
                                            </a>
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário de Edição de Cessão (apenas SuperAdmin) -->
                            <div v-if="isSuperAdmin && unidade?.tipo_judicial === 'cedido'" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Editar Informações de Cessão</h3>
                                <form @submit.prevent="salvarCessao" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Órgão Cedente</label>
                                        <input v-model="cessaoForm.orgao_cedente" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                                        <p v-if="cessaoForm.errors.orgao_cedente" class="text-red-500 text-xs mt-1">{{ cessaoForm.errors.orgao_cedente }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Prazo de Cessão</label>
                                        <input v-model="cessaoForm.prazo_cessao" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                                        <p v-if="cessaoForm.errors.prazo_cessao" class="text-red-500 text-xs mt-1">{{ cessaoForm.errors.prazo_cessao }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Termo de Cessão</label>
                                        <input 
                                            type="file"
                                            @change="cessaoForm.termo_cessao = $event.target.files[0]" 
                                            accept="application/pdf"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            />
                                        <p v-if="cessaoForm.errors.termo_cessao" class="text-red-500 text-xs mt-1">{{ cessaoForm.errors.termo_cessao }}</p>
                                    </div>
                                    <div class="col-span-full">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Informações para imóvel compartilhado -->
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

                            <!-- Observações -->
                            <div v-if="unidade?.observacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Observações</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow whitespace-pre-line">
                                    {{ unidade.observacoes }}
                                </div>
                            </div>
                        </div>

                        <!-- Acessibilidade -->
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

                                <!-- Observações de Acessibilidade -->
                                <div v-if="acessibilidade?.observacoes" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow mt-4">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Observações:</dt>
                                    <dd class="mt-1 whitespace-pre-line">{{ acessibilidade.observacoes }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Informações Estruturais -->
                        <div v-if="activeTab === 'informacoes'" class="space-y-6">
                            <!-- Via e Serviços -->
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

                            <!-- Internet e Telefonia -->
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

                            <!-- Características do Imóvel -->
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

                            <!-- Recuos -->
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

                            <!-- Quantitativos -->
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

                            <!-- Suficiência de Instalações -->
                        <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Suficiência de Idsdsnstalações</h3>
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
                            <!-- Acabamentos -->
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
                            <!-- Equipamentos de Segurança -->
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
                        </div>

                        <!-- Mídias -->
                        <div v-if="activeTab === 'midias'" class="grid grid-cols-1 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Mídias da Unidade</h3>
                                <div v-if="midias && midias.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div v-for="midia in midias" :key="midia.id" class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">{{ midia.midia_tipo?.nome || 'Tipo Desconhecido' }}:</dt>
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">{{ (midia.tamanho / 1024).toFixed(2) }} KB</dt>
                                        <dd class="mt-1">
                                            <div v-if="isImage(midia)" class="relative">
                                                <img :src="midia.url" @click="openModal(midia)" class="w-full h-32 object-cover rounded cursor-pointer" alt="Imagem da mídia" />
                                            </div>
                                            <div v-else class="text-blue-500 hover:underline">
                                                <a :href="midia.url" target="_blank">{{ midia.url.split('/').pop() || 'Ver arquivo' }}</a>
                                            </div>
                                        </dd>
                                    </div>
                                </div>
                                <div v-else class="bg-white p-6 rounded-md shadow-sm text-center">
                                    <i class="fas fa-image text-gray-400 text-4xl mb-4"></i>
                                    <p class="text-gray-600">Nenhuma mídia cadastrada para esta unidade.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de Visualização de Imagem -->
                         <div 
                            v-if="isModalOpen" 
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-[1001]"
                            @click="closeModal"
                        >
                            <div 
                                class="bg-white rounded-lg shadow-lg p-6 max-w-3xl w-full relative"
                                @click.stop
                            >
                                <button 
                                    @click="closeModal" 
                                    class="absolute top-4 right-4 text-gray-700 hover:text-gray-900 bg-white rounded-full p-2 shadow-md flex items-center justify-center"
                                    aria-label="Fechar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <img 
                                    :src="selectedMedia.url" 
                                    class="w-full h-auto max-h-[75vh] object-contain" 
                                    :alt="selectedMedia.midia_tipo?.nome || 'Imagem'" 
                                />
                                <div class="mt-4 flex justify-end">
                                    <a 
                                        :href="selectedMedia.url" 
                                        download 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        Baixar
                                    </a>
                                </div>
                            </div>
                        </div>

                       <!-- Aba de Avaliações -->
                        <div v-if="activeTab === 'avaliacoes'" class="space-y-6">
                        <!-- Cabeçalho da seção -->
                        <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Avaliações da Unidade</h3>
                            
                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <!-- Navegação em abas -->
                            <div class="border-b border-gray-200">
                                <nav class="flex -mb-px" aria-label="Tabs">
                                <button
                                    @click="activeAvaliacaoTab = 'historico'"
                                    :class="[
                                    activeAvaliacaoTab === 'historico'
                                        ? 'border-[#bea55a] text-[#816d33]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                    ]"
                                >
                                    <span class="flex items-center justify-center">
                                    <ChartBarIcon class="w-5 h-5 mr-2" />
                                    Histórico de Avaliações
                                    </span>
                                </button>
                                <button
                                    v-if="isSuperAdmin"
                                    @click="activeAvaliacaoTab = 'avaliacao'"
                                    :class="[
                                    activeAvaliacaoTab === 'avaliacao'
                                        ? 'border-[#bea55a] text-[#816d33]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                    ]"
                                >
                                    <span class="flex items-center justify-center">
                                    <ClipboardDocumentListIcon class="w-5 h-5 mr-2" />
                                    Nova Avaliação
                                    </span>
                                </button>
                                </nav>
                            </div>

                            <!-- Conteúdo das abas -->
                            <div class="p-6">
                                <div v-show="activeAvaliacaoTab === 'historico'">
                                <AvaliacaoHistorico
                                    :avaliacoes="avaliacoes"
                                    :unidade="unidade"
                                    :is-super-admin="isSuperAdmin"
                                />
                                </div>

                                <div v-show="activeAvaliacaoTab === 'avaliacao'" v-if="isSuperAdmin">
                                <AvaliacaoForm
                                    :unidade="unidade"
                                    :avaliacoes="avaliacoes"
                                    :is-new="true"
                                />
                            </div>
                        </div>
                        </div>

                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Transições e animações */
.transition-all {
    transition: all 0.3s ease;
}

.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.scrollbar-thin {
    scrollbar-width: thin;
}

/* Responsividade para telas pequenas */
@media (max-width: 640px) {
    .grid {
        grid-gap: 0.75rem;
    }
    
    .p-4 {
        padding: 0.75rem;
    }
}

/* Estilo para o modal */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>