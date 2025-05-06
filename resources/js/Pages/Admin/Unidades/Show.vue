<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvaliacaoForm from '@/Pages/Admin/Unidades/Partials/AvaliacaoForm.vue';
import { ref, watch, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

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
const flashMessage = ref(null);
const mobileMenuOpen = ref(false);

// Estado do modal
const isModalOpen = ref(false);
const selectedMedia = ref(null);

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

// Máscara para o campo telefone
const telefoneMask = { mask: '(00) 00000-0000', lazy: false, overwrite: true };

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

// Formatando dados para exibição
const getStatusLabel = computed(() => {
    if (!props.unidade?.status) return { text: 'Desconhecido', class: 'bg-gray-100 text-gray-800' };
    
    switch(props.unidade.status) {
        case 'pendente_avaliacao':
            return { text: 'Pendente de Avaliação', class: 'bg-yellow-100 text-yellow-800' };
        case 'aprovada':
            return { text: 'Aprovada', class: 'bg-green-100 text-green-800' };
        case 'reprovada':
            return { text: 'Reprovada', class: 'bg-red-100 text-red-800' };
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

// Formatar a data das avaliações
const formatarData = (data) => {
    if (!data) return 'Não informado';
    
    if (typeof data === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(data)) {
        const [year, month, day] = data.split('-').map(Number);
        // Criar a data como local
        const date = new Date(year, month - 1, day);
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    // Para outros formatos, usamos o comportamento padrão
    const date = new Date(data);
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// Função para salvar contrato de locação
const salvarContrato = () => {
    // Remover qualquer formatação do cpf_cnpj antes de enviar
    contratoForm.cpf_cnpj = contratoForm.cpf_cnpj.replace(/\D/g, '');
    contratoForm.post(route('admin.unidades.updateContrato', props.unidade.id), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            flashMessage.value = 'Contrato salvo com sucesso!';
            // Atualizar o formulário com os dados retornados
            const updatedContrato = page.props.unidade.contrato_locacao || {};
            contratoForm.nome_proprietario = updatedContrato.nome_proprietario || '';
            contratoForm.cpf_cnpj = updatedContrato.cpf_cnpj || '';
            contratoForm.telefone = updatedContrato.telefone || '';
            contratoForm.valor_locacao = updatedContrato.valor_locacao || '';
            // Garantir que as datas estejam no formato YYYY-MM-DD
            contratoForm.data_inicio = updatedContrato.data_inicio ? new Date(updatedContrato.data_inicio).toISOString().split('T')[0] : '';
            contratoForm.data_fim = updatedContrato.data_fim ? new Date(updatedContrato.data_fim).toISOString().split('T')[0] : '';
            contratoForm.anexo = null; // Resetar o campo de arquivo
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
                                :class="[activeTab === tab.id ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs md:text-sm flex items-center transition-all']"
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
                        
                        <!-- Menu móvel dropdown -->
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
                                <span class="font-medium text-gray-600">Status:</span>
                                <span 
                                    class="px-3 py-1 rounded-full text-xs sm:text-sm font-medium shadow-sm"
                                    :class="getStatusLabel.class"
                                >
                                    {{ getStatusLabel.text }}
                                </span>
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
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Código:</dt>
                                        <dd class="mt-1">{{ unidade?.codigo || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Tipo Estrutural:</dt>
                                        <dd class="mt-1">{{ unidade?.tipo_estrutural || 'Não informado' }}</dd>
                                    </div>
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nível:</dt>
                                        <dd class="mt-1">{{ unidade?.nivel || 'Não informado' }}</dd>
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
                                            placeholder="12345678901 ou 12345678000199"
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
                                        <input type="file" @change="contratoForm.anexo = $event.target.files[0]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="contratoForm.errors.anexo" class="text-red-500 text-xs mt-1">{{ contratoForm.errors.anexo }}</p>
                                    </div>
                                    <div class="col-span-full">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Salvar Contrato
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
                                        <input type="file" @change="cessaoForm.termo_cessao = $event.target.files[0]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <p v-if="cessaoForm.errors.termo_cessao" class="text-red-500 text-xs mt-1">{{ cessaoForm.errors.termo_cessao }}</p>
                                    </div>
                                    <div class="col-span-full">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Salvar Cessão
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Informações para imóvel compartilhado -->
                            <div v-if="unidade?.imovel_compartilhado_orgao" class="bg-gray-50 p-4 rounded-lg shadow-sm md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Compartilhamento de Imóvel</h3>
                                <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                    <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Órgão Compartilhado:</dt>
                                    <dd class="mt-1">{{ unidade.orgao_compartilhado?.nome || 'Não informado' }}</dd>
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
                            <!-- Primeira seção: Via e Serviços -->
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
                            </div>

                            <!-- Segunda seção: Internet e Telefonia -->
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

                            <!-- Terceira seção: Características do Imóvel -->
                            <div v-if="informacoes" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Características do Imóvel</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-3 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Tipo do Imóvel:</dt>
                                        <dd class="mt-1">{{ informacoes.tipo_imovel || 'Não informado' }}</dd>
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
                                        <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Celas:</dt>
                                        <dd class="mt-1">{{ informacoes.qtd_celas_carceragem || '0' }}</dd>
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

                            <!-- Se não houver informações estruturais -->
                            <div v-if="!informacoes" class="bg-white p-6 rounded-md shadow-sm text-center">
                                <i class="fas fa-info-circle text-blue-500 text-4xl mb-4"></i>
                                <p class="text-gray-600">Nenhuma informação estrutural cadastrada para esta unidade.</p>
                            </div>
                        </div>

                        <!-- Mídias -->
                        <div v-if="activeTab === 'midias'" class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Galeria de Mídias</h3>
                                
                                <!-- Exibição de mídias em formato grid -->
                                <div v-if="midias && midias.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div 
                                        v-for="(midia, index) in midias" 
                                        :key="index" 
                                        class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all"
                                    >
                                        <!-- Imagem -->
                                        <div v-if="isImage(midia)" class="relative h-48 bg-gray-200">
                                            <img 
                                                :src="midia.url" 
                                                alt="Mídia" 
                                                class="w-full h-full object-cover cursor-pointer"
                                                @click="openModal(midia)"
                                            />
                                        </div>
                                        
                                        <!-- Informações da mídia -->
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
                                
                                <!-- Mensagem quando não há mídias -->
                                <div v-else class="bg-white p-6 rounded-md shadow-sm text-center">
                                    <i class="fas fa-photo-video text-blue-500 text-4xl mb-4"></i>
                                    <p class="text-gray-600">Nenhuma mídia disponível para esta unidade.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Avaliações -->
                        <div v-if="activeTab === 'avaliacoes'" class="space-y-6">
                            <!-- Histórico de Avaliações -->
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Histórico de Avaliações</h3>
                                
                                <div v-if="avaliacoes && avaliacoes.length" class="space-y-4">
                                    <div v-for="(avaliacao, index) in avaliacoes" :key="avaliacao.id" class="bg-white p-4 rounded-md shadow-sm hover:shadow-md transition-shadow">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Status:</dt>
                                                <dd class="mt-1">
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                                                          :class="{
                                                              'bg-green-100 text-green-800': avaliacao.status === 'aprovada',
                                                              'bg-red-100 text-red-800': avaliacao.status === 'reprovada',
                                                              'bg-blue-100 text-blue-800': avaliacao.status === 'em_revisao',
                                                          }">
                                                        {{ avaliacao.status === 'aprovada' ? 'Aprovada' : avaliacao.status === 'reprovada' ? 'Reprovada' : 'Em Revisão' }}
                                                    </span>
                                                </dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nota Geral:</dt>
                                                <dd class="mt-1">{{ avaliacao.nota_geral || 'Não informado' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nota Estrutura:</dt>
                                                <dd class="mt-1">{{ avaliacao.nota_estrutura || 'Não informado' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nota Acessibilidade:</dt>
                                                <dd class="mt-1">{{ avaliacao.nota_acessibilidade || 'Não informado' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Nota Conservação:</dt>
                                                <dd class="mt-1">{{ avaliacao.nota_conservacao || 'Não informado' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Avaliador:</dt>
                                                <dd class="mt-1">{{ avaliacao.avaliador?.name || 'Não informado' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Data:</dt>
                                                <dd class="mt-1">{{ formatarData(avaliacao.created_at) }}</dd>
                                            </div>
                                            <div v-if="avaliacao.observacoes" class="col-span-1 sm:col-span-2 md:col-span-3">
                                                <dt class="font-medium text-gray-600 text-xs uppercase tracking-wider">Observações:</dt>
                                                <dd class="mt-1 whitespace-pre-line">{{ avaliacao.observacoes }}</dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="bg-white p-6 rounded-md shadow-sm text-center">
                                    <i class="fas fa-star text-blue-500 text-4xl mb-4"></i>
                                    <p class="text-gray-600">Nenhuma avaliação registrada para esta unidade.</p>
                                </div>
                            </div>

                            <!-- Formulário de Avaliação (apenas para SuperAdmin) -->
                            <div v-if="isSuperAdmin" class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Registrar Nova Avaliação</h3>
                                <AvaliacaoForm :unidade="unidade" :is-new="true" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para exibir a imagem -->
        <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[1000]" @click.self="closeModal">
            <div class="bg-white rounded-lg p-4 max-w-3xl w-full mx-4 relative">
                <!-- Botão de fechar -->
                <button @click="closeModal" class="absolute top-2 right-2 text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                
                <!-- Imagem no modal -->
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