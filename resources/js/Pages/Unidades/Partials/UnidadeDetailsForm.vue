<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, onMounted, watch, computed, nextTick } from 'vue';
import { debounce } from 'lodash';
import { useToast } from '@/Composables/useToast';

const toast = useToast();
const emit = defineEmits(['saved']);

const props = defineProps({
    team: Object,
    unidade: Object,
    orgaos: Array,
    unidades: Array, 
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

const persistentState = ref({
    imovel_compartilhado_orgao: props.unidade?.imovel_compartilhado_orgao || false,
    imovel_compartilhado_orgao_ids: [],
    imovel_compartilhado_unidades: props.unidade?.imovel_compartilhado_unidades || false,
    imovel_compartilhado_unidades_ids: [],
    imovel_compartilhado_unidades_texto: props.unidade?.imovel_compartilhado_unidades_texto || '',
});

// Inicializar IDs dos órgãos compartilhados
const initializeOrgaoIds = () => {
    if (props.unidade?.orgaosCompartilhados?.length) {
        persistentState.value.imovel_compartilhado_orgao_ids = props.unidade.orgaosCompartilhados.map(orgao => Number(orgao.id));
    }
};

// Inicializar IDs das unidades compartilhadas
const initializeUnidadeIds = () => {
    if (props.unidade?.unidadesCompartilhadas?.length) {
        persistentState.value.imovel_compartilhado_unidades_ids = props.unidade.unidadesCompartilhadas.map(unidade => Number(unidade.id));
    }
};

// Inicializar estados
initializeOrgaoIds();
initializeUnidadeIds();

// Form do Inertia (apenas para submit)
const form = useForm({
    team_id: props.team?.id || '',
    nome: props.unidade?.nome || props.team?.name || '',
    tipo_estrutural: props.unidade?.tipo_estrutural || '',
    srpc: props.unidade?.srpc || '',
    dspc: props.unidade?.dspc || '',
    sede: props.unidade?.sede || false,
    email: props.unidade?.email || '',
    telefone_1: props.unidade?.telefone_1 || '',
    telefone_2: props.unidade?.telefone_2 || '',
    tipo_judicial: props.unidade?.tipo_judicial || '',
    observacoes: props.unidade?.observacoes || '',
    numero_medidor_agua: props.unidade?.numero_medidor_agua || '',
    numero_medidor_energia: props.unidade?.numero_medidor_energia || '',
    imovel_compartilhado_unidades: false,
    imovel_compartilhado_unidades_texto: '',
    imovel_compartilhado_orgao: false,
    imovel_compartilhado_orgao_ids: [],
    imovel_compartilhado_unidades_ids: [],
});

// Estados para dropdowns
const showOrgaoDropdown = ref(false);
const orgaoSearchTerm = ref('');
const dropdownRef = ref(null);
const showUnidadeDropdown = ref(false);
const unidadeSearchTerm = ref('');
const unidadeDropdownRef = ref(null);

const filteredOrgaos = computed(() => {
    if (!orgaoSearchTerm.value) return props.orgaos || [];
    return (props.orgaos || []).filter(orgao =>
        orgao.nome.toLowerCase().includes(orgaoSearchTerm.value.toLowerCase())
    );
});

const filteredUnidades = computed(() => {
    if (!unidadeSearchTerm.value) return props.unidades || [];
    return (props.unidades || []).filter(unidade =>
        unidade.nome.toLowerCase().includes(unidadeSearchTerm.value.toLowerCase())
    );
});

const selectedOrgaos = computed(() => {
    return (props.orgaos || []).filter(orgao => 
        persistentState.value.imovel_compartilhado_orgao_ids.includes(Number(orgao.id))
    );
});

const selectedUnidades = computed(() => {
    return (props.unidades || []).filter(unidade => 
        persistentState.value.imovel_compartilhado_unidades_ids.includes(Number(unidade.id))
    );
});

const debouncedUpdate = debounce((field, value) => {
    form[field] = value;
}, 300);

const methods = {
    updateField: (field, value) => {
        debouncedUpdate(field, value);
    },
    
    toggleOrgao: (orgaoId) => {
        const numericId = Number(orgaoId);
        const currentIds = [...persistentState.value.imovel_compartilhado_orgao_ids];
        const index = currentIds.indexOf(numericId);
        
        if (index > -1) {
            currentIds.splice(index, 1);
        } else {
            currentIds.push(numericId);
        }
        
        persistentState.value.imovel_compartilhado_orgao_ids = currentIds;
    },
    
    removeOrgao: (orgaoId) => {
        const numericId = Number(orgaoId);
        persistentState.value.imovel_compartilhado_orgao_ids = 
            persistentState.value.imovel_compartilhado_orgao_ids.filter(id => id !== numericId);
    },
    
    isOrgaoSelected: (orgaoId) => {
        return persistentState.value.imovel_compartilhado_orgao_ids.includes(Number(orgaoId));
    },

    toggleUnidade: (unidadeId) => {
        const numericId = Number(unidadeId);
        const currentIds = [...persistentState.value.imovel_compartilhado_unidades_ids];
        const index = currentIds.indexOf(numericId);
        
        if (index > -1) {
            currentIds.splice(index, 1);
        } else {
            currentIds.push(numericId);
        }
        
        persistentState.value.imovel_compartilhado_unidades_ids = currentIds;
    },
    
    removeUnidade: (unidadeId) => {
        const numericId = Number(unidadeId);
        persistentState.value.imovel_compartilhado_unidades_ids = 
            persistentState.value.imovel_compartilhado_unidades_ids.filter(id => id !== numericId);
    },
    
    isUnidadeSelected: (unidadeId) => {
        return persistentState.value.imovel_compartilhado_unidades_ids.includes(Number(unidadeId));
    }
};

// Watch para monitorar mudanças nas props
watch(() => props.unidade, (newUnidade) => {
    if (newUnidade) {
        initializeOrgaoIds();
        initializeUnidadeIds();
        persistentState.value.imovel_compartilhado_orgao = newUnidade.imovel_compartilhado_orgao || false;
        persistentState.value.imovel_compartilhado_unidades = newUnidade.imovel_compartilhado_unidades || false;
        persistentState.value.imovel_compartilhado_unidades_texto = newUnidade.imovel_compartilhado_unidades_texto || '';
    }
}, { deep: true });

// Watchers para limpar seleções quando checkboxes são desmarcados
watch(() => persistentState.value.imovel_compartilhado_unidades, (newValue) => {
    if (!newValue) {
        persistentState.value.imovel_compartilhado_unidades_texto = '';
        persistentState.value.imovel_compartilhado_unidades_ids = [];
    }
});

watch(() => persistentState.value.imovel_compartilhado_orgao, (newValue) => {
    if (!newValue) {
        persistentState.value.imovel_compartilhado_orgao_ids = [];
    }
});

// Watcher para campos tipo_judicial
watch(() => form.tipo_judicial, (newValue) => {
    if (newValue !== 'locado') {
        form.nome_proprietario = '';
        form.cpf_cnpj = '';
        form.telefone_proprietario = '';
        form.valor_locacao = '';
        form.data_inicio = '';
        form.data_fim = '';
    }
    if (newValue !== 'cedido') {
        form.orgao_cedente = '';
        form.termo_cessao = '';
        form.prazo_cessao = '';
    }
});

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showOrgaoDropdown.value = false;
    }
    if (unidadeDropdownRef.value && !unidadeDropdownRef.value.contains(event.target)) {
        showUnidadeDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});


const saveDadosGerais = () => {
    if (!props.isEditable) {
        toast.info('O cadastro está finalizado e não pode ser editado.');
        emit('saved', 'O cadastro está finalizado e não pode ser editado.', null);
        return;
    }

    // SINCRONIZAR ESTADO PERSISTENTE COM FORM ANTES DA VALIDAÇÃO (PARA OS SELECTS)
    form.imovel_compartilhado_orgao = persistentState.value.imovel_compartilhado_orgao;
    form.imovel_compartilhado_orgao_ids = [...persistentState.value.imovel_compartilhado_orgao_ids];
    form.imovel_compartilhado_unidades = persistentState.value.imovel_compartilhado_unidades;
    form.imovel_compartilhado_unidades_ids = [...persistentState.value.imovel_compartilhado_unidades_ids];
    form.imovel_compartilhado_unidades_texto = persistentState.value.imovel_compartilhado_unidades_texto;

    // Validação
    const requiredFields = ['nome', 'tipo_estrutural', 'tipo_judicial'];
    const errors = [];
    
    requiredFields.forEach((field) => {
        if (!form[field]) {
            toast.error(`O campo ${field} é obrigatório.`);
            errors.push(`O campo ${field} é obrigatório.`);
        }
    });

    if (persistentState.value.imovel_compartilhado_orgao && persistentState.value.imovel_compartilhado_orgao_ids.length === 0) {
        errors.push('Selecione pelo menos um órgão.');
        toast.error('Selecione pelo menos um órgão.');
    }

    if (persistentState.value.imovel_compartilhado_unidades && 
        !persistentState.value.imovel_compartilhado_unidades_texto?.trim() && 
        persistentState.value.imovel_compartilhado_unidades_ids.length === 0) {
        errors.push('Selecione unidades ou descreva quais unidades compartilham o imóvel.');
        toast.error('Selecione unidades ou descreva quais unidades compartilham o imóvel.');
    }

    if (errors.length > 0) {
        emit('saved', errors.join(' '), null);
        return;
    }

    // Limpar telefones
    form.telefone_1 = form.telefone_1 ? form.telefone_1.replace(/[^0-9]/g, '') : '';
    form.telefone_2 = form.telefone_2 ? form.telefone_2.replace(/[^0-9]/g, '') : '';

    // Submit
    form.post(route('unidades.saveDadosGerais'), {
        errorBag: 'saveDadosGerais',
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Dados Gerais salvos com sucesso!');
            emit('saved', null, 'localizacao');
        },
        onError: (errors) => {
            toast.error('Erro ao salvar os dados. Verifique os campos.');
            emit('saved', 'Erro ao salvar os dados. Verifique os campos.', null);
        }
    });
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-md">
        <form @submit.prevent="saveDadosGerais">
            <div class="p-6">
                <!-- Informações Básicas -->
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="nome" value="Nome da Unidade *" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="nome"
                            v-model="form.nome"
                            type="text"
                            class="bg-gray-100 mt-1 block w-full"
                            disabled
                            placeholder="Ex: Delegacia Civil de Centro"
                        />
                        <InputError :message="form.errors.nome" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="tipo_estrutural" value="Tipo Estrutural *" class="text-sm font-medium text-gray-700" />
                        <SelectInput
                            id="tipo_estrutural"
                            v-model="form.tipo_estrutural"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option value="">Selecione o tipo</option>
                            <option value="delegacia">Delegacia</option>
                            <option value="especializada">Unidade Especializada</option>
                            <option value="instituto">Instituto</option>
                            <option value="academia">Academia</option>
                            <option value="superintendencia">Superintendência</option>
                            <option value="outra">Outra</option>
                        </SelectInput>
                        <InputError :message="form.errors.tipo_estrutural" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="srpc" value="Unidade Gestora" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="srpc"
                            v-model="form.srpc"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="DEGEPOL, 1ª SRPC, etc"
                        />
                        <InputError :message="form.errors.srpc" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="dspc" value="Unidade Sub-Gestora" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="dspc"
                            v-model="form.dspc"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="COORDEAM, 1ª DSPC, etc"
                        />
                        <InputError :message="form.errors.dspc" class="mt-1" />
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center h-full mt-6">
                            <Checkbox 
                                id="sede" 
                                v-model:checked="form.sede" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="sede" value="Sede" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.sede" class="mt-1" />
                    </div>
                </div>

                <!-- Informações de Contato -->
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações de Contato</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="email" value="Email" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="contato@dominio.com"
                        />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="telefone_1" value="Telefone 1" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="telefone_1"
                            :value="form.telefone_1"
                            type="text"
                            v-imask="{
                                mask: '{(00) }0000[0]-0000',
                                lazy: false,
                                overwrite: true
                            }"
                            placeholder="(11) 98765-4321"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            @update:modelValue="methods.updateField('telefone_1', $event)"
                        />
                        <InputError :message="form.errors.telefone_1" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="telefone_2" value="Telefone 2" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="telefone_2"
                            :value="form.telefone_2"
                            type="text"
                            v-imask="{
                                mask: '{(00) }0000[0]-0000',
                                lazy: false,
                                overwrite: true
                            }"
                            placeholder="(11) 98765-4321"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            @update:modelValue="methods.updateField('telefone_2', $event)"
                        />
                        <InputError :message="form.errors.telefone_2" class="mt-1" />
                    </div>
                </div>

                <!-- Informações Técnicas -->
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Técnicas</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="tipo_judicial" value="Tipo Judicial *" class="text-sm font-medium text-gray-700" />
                        <SelectInput
                            id="tipo_judicial"
                            v-model="form.tipo_judicial"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option value="">Selecione</option>
                            <option value="proprio">Próprio</option>
                            <option value="locado">Locado</option>
                            <option value="cedido">Cedido</option>
                        </SelectInput>
                        <InputError :message="form.errors.tipo_judicial" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="numero_medidor_agua" value="Medidor de Água" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="numero_medidor_agua"
                            v-model="form.numero_medidor_agua"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="Número do medidor"
                        />
                        <InputError :message="form.errors.numero_medidor_agua" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="numero_medidor_energia" value="Medidor de Energia" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="numero_medidor_energia"
                            v-model="form.numero_medidor_energia"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="Número do medidor"
                        />
                        <InputError :message="form.errors.numero_medidor_energia" class="mt-1" />
                    </div>
                    
                    <!-- Checkbox para imóvel compartilhado com outras Unidades -->
                    <div class="md:col-span-3">
                        <div class="flex items-center mt-2">
                            <Checkbox 
                                id="imovel_compartilhado_unidades" 
                                v-model:checked="persistentState.imovel_compartilhado_unidades" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="imovel_compartilhado_unidades" value="Imóvel compartilhado com outra(s) Unidades Policiais?" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.imovel_compartilhado_unidades" class="mt-1" />
                    </div>

                    <!-- Seletor de unidades -->
                    <div v-if="persistentState.imovel_compartilhado_unidades" class="md:col-span-3">
                        <InputLabel for="imovel_compartilhado_unidades_ids" value="Unidades Compartilhadas" class="text-sm font-medium text-gray-700 mb-2" />
                        
                        <!-- Unidades selecionadas (tags) -->
                        <div v-if="selectedUnidades.length > 0" class="mb-3">
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="unidade in selectedUnidades" 
                                    :key="unidade.id"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                >
                                    {{ unidade.nome }}
                                    <button 
                                        v-if="isEditable && permissions?.canUpdateTeam"
                                        type="button"
                                        @click="methods.removeUnidade(unidade.id)"
                                        class="ml-2 text-blue-600 hover:text-blue-800"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Dropdown personalizado para unidades -->
                        <div class="relative" ref="unidadeDropdownRef">
                            <button
                                type="button"
                                @click="showUnidadeDropdown = !showUnidadeDropdown"
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-left flex items-center justify-between disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <span class="text-gray-700">
                                    {{ selectedUnidades.length === 0 ? 'Selecione as unidades...' : `${selectedUnidades.length} unidade(s) selecionada(s)` }}
                                </span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown menu -->
                            <div 
                                v-show="showUnidadeDropdown"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                            >
                                <!-- Campo de busca -->
                                <div class="p-2">
                                    <input
                                        v-model="unidadeSearchTerm"
                                        type="text"
                                        placeholder="Buscar unidade..."
                                        class="w-full px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                
                                <!-- Lista de unidades -->
                                <div class="max-h-48 overflow-y-auto">
                                    <div
                                        v-for="unidade in filteredUnidades"
                                        :key="unidade.id"
                                        @click="methods.toggleUnidade(unidade.id)"
                                        class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center space-x-2"
                                        :class="{ 'bg-blue-50': methods.isUnidadeSelected(unidade.id) }"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="methods.isUnidadeSelected(unidade.id)"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            readonly
                                        />
                                        <span class="text-sm text-gray-900">{{ unidade.nome }}</span>
                                    </div>
                                    
                                    <!-- Mensagem quando não há resultados -->
                                    <div v-if="filteredUnidades.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                        Nenhuma unidade encontrada
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <InputError :message="form.errors.imovel_compartilhado_unidades_ids" class="mt-1" />
                        
                        <!-- Campo de texto para descrever as unidades -->
                        <div class="mt-4">
                            <InputLabel for="imovel_compartilhado_unidades_texto" value="Ou descreva quais unidades compartilham o imóvel" class="text-sm font-medium text-gray-700" />
                            <TextInput
                                id="imovel_compartilhado_unidades_texto"
                                v-model="persistentState.imovel_compartilhado_unidades_texto"
                                type="text"
                                class="mt-1 block w-full"
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                                placeholder="Ex: Delegacia de Homicídios, 7ª Delegacia Distrital, etc."
                            />
                            <InputError :message="form.errors.imovel_compartilhado_unidades_texto" class="mt-1" />
                            <p class="mt-1 text-xs text-gray-500">
                                Você pode selecionar unidades acima ou descrever manualmente neste campo
                            </p>
                        </div>
                    </div>
                    
                    <!-- Checkbox para imóvel compartilhado com órgãos -->
                    <div class="md:col-span-3">
                        <div class="flex items-center mt-2">
                            <Checkbox 
                                id="imovel_compartilhado_orgao" 
                                v-model:checked="persistentState.imovel_compartilhado_orgao" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="imovel_compartilhado_orgao" value="Imóvel compartilhado com outro(s) órgão(s)" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.imovel_compartilhado_orgao" class="mt-1" />
                    </div>
                    
                    <!-- Seletor de órgãos -->
                    <div v-if="persistentState.imovel_compartilhado_orgao" class="md:col-span-3">
                        <InputLabel for="imovel_compartilhado_orgao_ids" value="Órgãos Compartilhados *" class="text-sm font-medium text-gray-700 mb-2" />
                        
                        <!-- Órgãos selecionados (tags) -->
                        <div v-if="selectedOrgaos.length > 0" class="mb-3">
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="orgao in selectedOrgaos" 
                                    :key="orgao.id"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                >
                                    {{ orgao.nome }}
                                    <button 
                                        v-if="isEditable && permissions?.canUpdateTeam"
                                        type="button"
                                        @click="methods.removeOrgao(orgao.id)"
                                        class="ml-2 text-blue-600 hover:text-blue-800"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Dropdown personalizado para órgãos -->
                        <div class="relative" ref="dropdownRef">
                            <button
                                type="button"
                                @click="showOrgaoDropdown = !showOrgaoDropdown"
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-left flex items-center justify-between disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <span class="text-gray-700">
                                    {{ selectedOrgaos.length === 0 ? 'Selecione os órgãos...' : `${selectedOrgaos.length} órgão(s) selecionado(s)` }}
                                </span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown menu -->
                            <div 
                                v-show="showOrgaoDropdown"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                            >
                                <!-- Campo de busca -->
                                <div class="p-2">
                                    <input
                                        v-model="orgaoSearchTerm"
                                        type="text"
                                        placeholder="Buscar órgão..."
                                        class="w-full px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                
                                <!-- Lista de órgãos -->
                                <div class="max-h-48 overflow-y-auto">
                                    <div
                                        v-for="orgao in filteredOrgaos"
                                        :key="orgao.id"
                                        @click="methods.toggleOrgao(orgao.id)"
                                        class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center space-x-2"
                                        :class="{ 'bg-blue-50': methods.isOrgaoSelected(orgao.id) }"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="methods.isOrgaoSelected(orgao.id)"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            readonly
                                        />
                                        <span class="text-sm text-gray-900">{{ orgao.nome }}</span>
                                    </div>
                                    
                                    <!-- Mensagem quando não há resultados -->
                                    <div v-if="filteredOrgaos.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                        Nenhum órgão encontrado
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <InputError :message="form.errors.imovel_compartilhado_orgao_ids" class="mt-1" />
                    </div>
                    
                    <!-- Observações -->
                    <div class="md:col-span-3">
                        <InputLabel for="observacoes" value="Observações" class="text-sm font-medium text-gray-700" />
                        <textarea
                            id="observacoes"
                            v-model="form.observacoes"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            rows="3"
                            placeholder="Informações adicionais sobre a unidade"
                        />
                        <InputError :message="form.errors.observacoes" class="mt-1" />
                    </div>
                </div>
            </div>

            <!-- Botão de ação -->
            <div v-if="isEditable && permissions?.canUpdateTeam" class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex items-center justify-between sticky bottom-0">
                <div class="text-sm text-gray-600">
                    <span class="text-red-500">*</span> Campos obrigatórios
                </div>
                <div class="flex items-center">
                    <ActionMessage :on="form.recentlySuccessful" class="mr-4">
                        <span class="text-green-600 font-medium">Salvo com sucesso</span>
                    </ActionMessage>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        color="gold"
                    >
                        {{ form.processing ? 'Salvando...' : (props.unidade?.is_draft === true ? 'Salvar e Continuar' : 'Atualizar Dados Gerais') }}
                        <div v-if="form.processing" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                            <svg v-else-if="props.unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>