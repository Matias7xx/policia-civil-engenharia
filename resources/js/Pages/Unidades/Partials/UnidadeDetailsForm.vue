<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, onMounted, watch } from 'vue';
import { debounce } from 'lodash';

const emit = defineEmits(['saved']);

const props = defineProps({
    team: Object,
    unidade: Object,
    orgaos: Array,
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

// Inicializar os IDs dos órgãos compartilhados, garantindo que sejam números
const orgaoIds = props.unidade?.orgaosCompartilhados?.length
    ? props.unidade.orgaosCompartilhados.map(orgao => Number(orgao.id))
    : [];

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
    imovel_compartilhado_orgao: props.unidade?.imovel_compartilhado_orgao || false,
    imovel_compartilhado_orgao_ids: orgaoIds,
    observacoes: props.unidade?.observacoes || '',
    numero_medidor_agua: props.unidade?.numero_medidor_agua || '',
    numero_medidor_energia: props.unidade?.numero_medidor_energia || '',
});

const orgaoSelect = ref(null); // Referência ao select

const debouncedUpdate = debounce((field, value) => {
    form[field] = value;
}, 300);

const methods = {
    updateField: (field, value) => {
        debouncedUpdate(field, value);
    },
};

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

watch(() => form.imovel_compartilhado_orgao, (newValue) => {
    if (!newValue) {
        form.imovel_compartilhado_orgao_ids = [];
    }
});

onMounted(() => {
    // Forçar atualização do select
    if (orgaoSelect.value && form.imovel_compartilhado_orgao_ids.length) {
        form.imovel_compartilhado_orgao_ids.forEach(id => {
            const option = orgaoSelect.value.querySelector(`option[value="${id}"]`);
            if (option) option.selected = true;
        });
    }
});

const saveDadosGerais = () => {
    if (!props.isEditable) {
        emit('saved', 'O cadastro está finalizado e não pode ser editado.');
        return;
    }

    const requiredFields = ['nome', 'tipo_estrutural', 'tipo_judicial'];
    const errors = [];
    requiredFields.forEach((field) => {
        if (!form[field]) {
            errors.push(`O campo ${field} é obrigatório.`);
            form.errors[field] = `O campo ${field} é obrigatório.`;
        }
    });

    if (form.imovel_compartilhado_orgao && form.imovel_compartilhado_orgao_ids.length === 0) {
        errors.push('Selecione pelo menos um órgão.');
        form.errors.imovel_compartilhado_orgao_ids = 'Selecione pelo menos um órgão.';
    }

    if (errors.length > 0) {
        emit('saved', errors.join(' '));
        return;
    }

    form.telefone_1 = form.telefone_1 ? form.telefone_1.replace(/[^0-9]/g, '') : '';
    form.telefone_2 = form.telefone_2 ? form.telefone_2.replace(/[^0-9]/g, '') : '';

    form.post(route('unidades.saveDadosGerais'), {
        errorBag: 'saveDadosGerais',
        preserveScroll: true,
        onSuccess: () => {
            emit('saved'); // Apenas emite o evento sem forçar transição de aba
        },
        onError: (errors) => {
            emit('saved', 'Erro ao salvar os dados. Verifique os campos.');
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-md">
        <form @submit.prevent="saveDadosGerais">
            <div class="p-6">
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
                            <option value="unidade_especializada">Unidade Especializada</option>
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
                    <div class="md:col-span-3">
                        <div class="flex items-center mt-2">
                            <Checkbox 
                                id="imovel_compartilhado_orgao" 
                                v-model:checked="form.imovel_compartilhado_orgao" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="imovel_compartilhado_orgao" value="Imóvel Compartilhado com Outro Órgão" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.imovel_compartilhado_orgao" class="mt-1" />
                    </div>
                    <div v-if="form.imovel_compartilhado_orgao" class="md:col-span-3">
                        <InputLabel for="imovel_compartilhado_orgao_ids" value="Órgãos Compartilhados *" class="text-sm font-medium text-gray-700" />
                        <select
                            id="imovel_compartilhado_orgao_ids"
                            ref="orgaoSelect"
                            v-model="form.imovel_compartilhado_orgao_ids"
                            multiple
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option v-for="orgao in orgaos" :key="orgao.id" :value="Number(orgao.id)">
                                {{ orgao.nome }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Segure Ctrl (ou Cmd) para selecionar múltiplos órgãos.</p>
                        <InputError :message="form.errors.imovel_compartilhado_orgao_ids" class="mt-1" />
                    </div>
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
                        Salvar e Continuar
                        <svg v-if="unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>