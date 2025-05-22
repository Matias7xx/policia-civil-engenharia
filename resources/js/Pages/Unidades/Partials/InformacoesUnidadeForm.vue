<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SelectInput from '@/Components/SelectInput.vue';

const emit = defineEmits(['saved']);

const props = defineProps({
    team: Object,
    unidade: Object,
    informacoes: Object,
    permissions: Object,
    isNew: Boolean,
});

// Seções colapsáveis
const expandedSections = ref({
    via: true,
    imovel: true,
    estruturais: true,
    espacos: true,
    instalacoes: true,
    acabamentos: true,
    seguranca: true
});

// Formulário com valores padrão
const form = useForm({
    unidade_id: props.unidade?.id || '',
    pavimentacao_rua: props.informacoes?.pavimentacao_rua || '',
    padrao_energia: props.informacoes?.padrao_energia || '',
    subestacao: props.informacoes?.subestacao || '',
    gerador_energia: props.informacoes?.gerador_energia || '',
    para_raio: props.informacoes?.para_raio || '',
    caixa_dagua: props.informacoes?.caixa_dagua || '',
    internet_cabeada: props.informacoes?.internet_cabeada || '',
    internet_provedor: props.informacoes?.internet_provedor || '',
    telefone_fixo: props.informacoes?.telefone_fixo || '',
    telefone_movel: props.informacoes?.telefone_movel || '',
    tipo_imovel: props.informacoes?.tipo_imovel || '',
    contrato_locacao_id: props.informacoes?.contrato_locacao_id || '',
    responsavel_locacao_cessao: props.informacoes?.responsavel_locacao_cessao || '',
    escritura_publica: props.informacoes?.escritura_publica || '',
    area_aproximada_unidade: props.informacoes?.area_aproximada_unidade ? String(props.informacoes.area_aproximada_unidade) : '',
    qtd_pavimentos: props.informacoes?.qtd_pavimentos ? String(props.informacoes.qtd_pavimentos) : '',
    cercado_muros: props.informacoes?.cercado_muros || false,
    estacionamento_interno: props.informacoes?.estacionamento_interno || false,
    estacionamento_externo: props.informacoes?.estacionamento_externo || false,
    recuo_frontal: props.informacoes?.recuo_frontal ? String(props.informacoes.recuo_frontal) : '',
    recuo_lateral: props.informacoes?.recuo_lateral ? String(props.informacoes.recuo_lateral) : '',
    recuo_fundos: props.informacoes?.recuo_fundos ? String(props.informacoes.recuo_fundos) : '',
    qtd_recepcao: props.informacoes?.qtd_recepcao ? String(props.informacoes.qtd_recepcao) : '',
    qtd_wc_publico: props.informacoes?.qtd_wc_publico ? String(props.informacoes.qtd_wc_publico) : '',
    qtd_gabinetes: props.informacoes?.qtd_gabinetes ? String(props.informacoes.qtd_gabinetes) : '',
    qtd_sala_oitiva: props.informacoes?.qtd_sala_oitiva ? String(props.informacoes.qtd_sala_oitiva) : '',
    qtd_wc_servidores: props.informacoes?.qtd_wc_servidores ? String(props.informacoes.qtd_wc_servidores) : '',
    qtd_alojamento_masculino: props.informacoes?.qtd_alojamento_masculino ? String(props.informacoes.qtd_alojamento_masculino) : '',
    qtd_wc_alojamento_masculino: props.informacoes?.qtd_wc_alojamento_masculino ? String(props.informacoes.qtd_wc_alojamento_masculino) : '',
    qtd_alojamento_feminino: props.informacoes?.qtd_alojamento_feminino ? String(props.informacoes.qtd_alojamento_feminino) : '',
    qtd_wc_alojamento_feminino: props.informacoes?.qtd_wc_alojamento_feminino ? String(props.informacoes.qtd_wc_alojamento_feminino) : '',
    qtd_xadrez_masculino: props.informacoes?.qtd_xadrez_masculino ? String(props.informacoes.qtd_xadrez_masculino) : '',
    area_xadrez_masculino: props.informacoes?.area_xadrez_masculino ? String(props.informacoes.area_xadrez_masculino) : '',
    qtd_xadrez_feminino: props.informacoes?.qtd_xadrez_feminino ? String(props.informacoes.qtd_xadrez_feminino) : '',
    area_xadrez_feminino: props.informacoes?.area_xadrez_feminino ? String(props.informacoes.area_xadrez_feminino) : '',
    qtd_sala_identificacao: props.informacoes?.qtd_sala_identificacao ? String(props.informacoes.qtd_sala_identificacao) : '',
    qtd_cozinha: props.informacoes?.qtd_cozinha ? String(props.informacoes.qtd_cozinha) : '',
    qtd_area_servico: props.informacoes?.qtd_area_servico ? String(props.informacoes.qtd_area_servico) : '',
    qtd_deposito_apreensao: props.informacoes?.qtd_deposito_apreensao ? String(props.informacoes.qtd_deposito_apreensao) : '',
    ponto_energia_agua: props.informacoes?.ponto_energia_agua || '',
    tomadas_suficientes: props.informacoes?.tomadas_suficientes || false,
    luminarias_suficientes: props.informacoes?.luminarias_suficientes || false,
    pontos_rede_suficientes: props.informacoes?.pontos_rede_suficientes || false,
    pontos_telefone_suficientes: props.informacoes?.pontos_telefone_suficientes || false,
    pontos_ar_condicionado_suficientes: props.informacoes?.pontos_ar_condicionado_suficientes || false,
    pontos_hidraulicos_suficientes: props.informacoes?.pontos_hidraulicos_suficientes || false,
    pontos_sanitarios_suficientes: props.informacoes?.pontos_sanitarios_suficientes || false,
    piso: props.informacoes?.piso || '',
    parede: props.informacoes?.parede || '',
    esquadrias: props.informacoes?.esquadrias || '',
    loucas_metais: props.informacoes?.loucas_metais || '',
    forro_lage: props.informacoes?.forro_lage || '',
    cobertura: props.informacoes?.cobertura || '',
    pintura: props.informacoes?.pintura || '',
    extintor_po_quimico: props.informacoes?.extintor_po_quimico || '',
    extintor_co2: props.informacoes?.extintor_co2 || '',
    extintor_agua: props.informacoes?.extintor_agua || '',
    placa_incendio: props.informacoes?.placa_incendio || '',
});

const methods = {
    updateField: (field, value) => {
        debouncedUpdate(field, value);
    },
    toggleSection: (section) => {
        expandedSections.value[section] = !expandedSections.value[section];
    }
};

const saveInformacoesEstruturais = () => {
    // Validação client-side
    form.errors = {}; // Limpar erros anteriores
    
    // Campos obrigatórios
    const requiredFields = {
        pavimentacao_rua: 'A pavimentação da rua é obrigatória.',
    };
    
    Object.entries(requiredFields).forEach(([field, message]) => {
        if (!form[field]) {
            form.errors[field] = message;
        }
    });
    
    if (Object.keys(form.errors).length > 0) {
        // Rola até o primeiro erro
        const firstErrorField = document.querySelector('.text-red-600');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        emit('saved', 'Verifique os campos obrigatórios.');
        return;
    }

    form.post(route('unidades.saveInformacoesEstruturais'), {
        errorBag: 'saveInformacoesEstruturais',
        preserveScroll: true,
        onSuccess: () => {
            emit('saved'); // Emite apenas 'saved' para sucesso, sem forçar transição de aba
        },
        onError: (errors) => {
            emit('saved', 'Erro ao salvar as informações estruturais. Verifique os campos.');
            
            // Rola até o primeiro erro
            setTimeout(() => {
                const firstErrorField = document.querySelector('.text-red-600');
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        },
    });
};

// Lista de opções para pavimentação
const tiposPavimentacao = [
    { value: 'asfalto', label: 'Asfalto' },
    { value: 'paralelepipedo', label: 'Paralelepípedo' },
    { value: 'terra', label: 'Terra' },
    { value: 'cascalho', label: 'Cascalho' },
    { value: 'outro', label: 'Outro' }
];

const buttonText = computed(() => {
    if (props.isNew) {
        return 'Salvar Alterações';
    }
    return 'Salvar e Continuar';
});
</script>

<template>
    <FormSection @submitted="saveInformacoesEstruturais">
        <template #title>
            <h2 class="text-lg font-medium text-gray-900">Informações Estruturais</h2>
        </template>

        <template #description>
            <div class="space-y-3">
                <p class="text-sm text-gray-600">
                    Preencha as informações sobre a estrutura física da unidade.
                </p>
                
                <!-- Instruções -->
                <div class="bg-blue-50 p-2 rounded text-sm text-blue-800 mt-2">
                    <p class="font-medium">Dica:</p>
                    <p class="text-xs">Clique nos títulos das seções para expandir ou recolher os campos.</p>
                </div>
            </div>
        </template>

        <template #form>
            <!-- 1. Características da Via e Serviços -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('via')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="1. Características da Via e Serviços" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.via ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.via" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <InputLabel for="pavimentacao_rua" value="Pavimentação da Rua *" class="text-sm" />
                            <SelectInput
                                id="pavimentacao_rua"
                                v-model="form.pavimentacao_rua"
                                :options="tiposPavimentacao"
                                class="mt-1 block w-full"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            >
                                <option value="">Selecione o tipo</option>
                            </SelectInput>
                            <InputError :message="form.errors.pavimentacao_rua" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="padrao_energia" value="Padrão de Energia" class="text-sm" />
                            <TextInput
                                id="padrao_energia"
                                v-model="form.padrao_energia"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ex: Monofásico/Bifásico/Trifásico"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.padrao_energia" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="subestacao" value="Subestação" class="text-sm" />
                            <TextInput
                                id="subestacao"
                                v-model="form.subestacao"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Descreva se houver"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.subestacao" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="gerador_energia" value="Gerador de Energia" class="text-sm" />
                            <TextInput
                                id="gerador_energia"
                                v-model="form.gerador_energia"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Descreva se houver"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.gerador_energia" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="para_raio" value="Para-Raio" class="text-sm" />
                            <TextInput
                                id="para_raio"
                                v-model="form.para_raio"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Sim/Não/Especificações"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.para_raio" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="caixa_dagua" value="Caixa d'Água" class="text-sm" />
                            <TextInput
                                id="caixa_dagua"
                                v-model="form.caixa_dagua"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Sim/Não/Especificações"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.caixa_dagua" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="internet_cabeada" value="Internet Cabeada" class="text-sm" />
                            <TextInput
                                id="internet_cabeada"
                                v-model="form.internet_cabeada"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Possui/Não Possui"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.internet_cabeada" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="internet_provedor" value="Provedor de Internet" class="text-sm" />
                            <TextInput
                                id="internet_provedor"
                                v-model="form.internet_provedor"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Nome do provedor"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.internet_provedor" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="telefone_fixo" value="Telefone Fixo" class="text-sm" />
                            <TextInput
                                id="telefone_fixo"
                                v-model="form.telefone_fixo"
                                type="text"
                                placeholder="Possui/Não Possui"
                                class="mt-1 block w-full"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.telefone_fixo" class="mt-1 text-xs" />
                        </div>

                        <div>
                            <InputLabel for="telefone_movel" value="Telefone Móvel" class="text-sm" />
                            <TextInput
                                id="telefone_movel"
                                v-model="form.telefone_movel"
                                type="text"
                                placeholder="Possui/Não Possui"
                                class="mt-1 block w-full"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.telefone_movel" class="mt-1 text-xs" />
                        </div>
                    </div>
                            <div class="mt-4">
                            <InputLabel for="ponto_energia_agua" value="Há ponto de energia próximo a algum ponto de água para a instalação de um purificador de água?" class="text-sm" />
                            <TextInput
                                id="ponto_energia_agua"
                                v-model="form.ponto_energia_agua"
                                type="text"
                                placeholder="Sim/Não"
                                class="mt-1"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.ponto_energia_agua" class="mt-1 text-xs" />
                        </div>
                </div>
            </div>

            <!-- 2. Características Estruturais -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('estruturais')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="2. Características Estruturais" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.estruturais ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.estruturais" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="area_aproximada_unidade" value="Área Aproximada da Unidade (m²)" class="text-sm" />
                                <TextInput
                                    id="area_aproximada_unidade"
                                    v-model="form.area_aproximada_unidade"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    placeholder="Ex: 3.5"
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                />
                                <InputError :message="form.errors.area_aproximada_unidade" class="mt-1 text-xs" />
                            </div>

                        <div>
                            <InputLabel for="qtd_pavimentos" value="Quantidade de Pavimentos" class="text-sm" />
                            <TextInput
                                id="qtd_pavimentos"
                                v-model="form.qtd_pavimentos"
                                type="number"
                                min="1"
                                step="1"
                                class="mt-1 block w-full"
                                placeholder="Ex: 1, 2, 3..."
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                            <InputError :message="form.errors.qtd_pavimentos" class="mt-1 text-xs" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-1 gap-3 items-center">
                            <div class="flex items-center">
                                <Checkbox 
                                    id="cercado_muros" 
                                    v-model:checked="form.cercado_muros" 
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                                />
                                <InputLabel for="cercado_muros" value="Cercado por Muros" class="ml-2 text-sm" />
                            </div>
                            
                            <div class="flex items-center">
                                <Checkbox 
                                    id="estacionamento_interno" 
                                    v-model:checked="form.estacionamento_interno" 
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                                />
                                <InputLabel for="estacionamento_interno" value="Estacionamento Interno" class="ml-2 text-sm" />
                            </div>
                            
                            <div class="flex items-center">
                                <Checkbox 
                                    id="estacionamento_externo" 
                                    v-model:checked="form.estacionamento_externo" 
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                                />
                                <InputLabel for="estacionamento_externo" value="Estacionamento Externo" class="ml-2 text-sm" />
                            </div>
                        </div>

                        <div class="col-span-1 sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
                            <div>
                                <InputLabel for="recuo_frontal" value="Recuo Frontal (m)" class="text-sm" />
                                <TextInput
                                    id="recuo_frontal"
                                    v-model="form.recuo_frontal"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    placeholder="Ex: 3.5"
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                />
                                <InputError :message="form.errors.recuo_frontal" class="mt-1 text-xs" />
                            </div>

                            <div>
                                <InputLabel for="recuo_lateral" value="Recuo Lateral (m)" class="text-sm" />
                                <TextInput
                                vue                                    id="recuo_lateral"
                                    v-model="form.recuo_lateral"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    placeholder="Ex: 2.0"
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                />
                                <InputError :message="form.errors.recuo_lateral" class="mt-1 text-xs" />
                            </div>

                            <div>
                                <InputLabel for="recuo_fundos" value="Recuo Fundos (m)" class="text-sm" />
                                <TextInput
                                    id="recuo_fundos"
                                    v-model="form.recuo_fundos"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    placeholder="Ex: 4.0"
                                    :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                />
                                <InputError :message="form.errors.recuo_fundos" class="mt-1 text-xs" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Quantitativos de Espaços e Instalações -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('espacos')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="3. Quantitativos de Espaços e Instalações" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.espacos ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.espacos" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <!-- Áreas de uso público -->
                        <div class="p-3 rounded-lg col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4">
                            <h3 class="text-sm font-medium mb-2">Áreas de Uso Público</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel for="qtd_recepcao" value="Recepções" class="text-sm" />
                                    <TextInput
                                        id="qtd_recepcao"
                                        v-model="form.qtd_recepcao"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_wc_publico" value="WCs Públicos" class="text-sm" />
                                    <TextInput
                                        id="qtd_wc_publico"
                                        v-model="form.qtd_wc_publico"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_sala_oitiva" value="Salas de Oitiva" class="text-sm" />
                                    <TextInput
                                        id="qtd_sala_oitiva"
                                        v-model="form.qtd_sala_oitiva"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Áreas administrativas -->
                        <div class="-3 rounded-lg col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4">
                            <h3 class="text-sm font-medium mb-2">Áreas Administrativas</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel for="qtd_gabinetes" value="Gabinetes" class="text-sm" />
                                    <TextInput
                                        id="qtd_gabinetes"
                                        v-model="form.qtd_gabinetes"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_wc_servidores" value="WCs Servidores" class="text-sm" />
                                    <TextInput
                                        id="qtd_wc_servidores"
                                        v-model="form.qtd_wc_servidores"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_sala_identificacao" value="Salas de Identificação" class="text-sm" />
                                    <TextInput
                                        id="qtd_sala_identificacao"
                                        v-model="form.qtd_sala_identificacao"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alojamentos -->
                        <div class="p-3 rounded-lg col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4">
                            <h3 class="text-sm font-medium mb-2">Alojamentos</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <InputLabel for="qtd_alojamento_masculino" value="Alojamento Masculino" class="text-sm" />
                                    <TextInput
                                        id="qtd_alojamento_masculino"
                                        v-model="form.qtd_alojamento_masculino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_wc_alojamento_masculino" value="WCs Aloj. Masculino" class="text-sm" />
                                    <TextInput
                                        id="qtd_wc_alojamento_masculino"
                                        v-model="form.qtd_wc_alojamento_masculino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_alojamento_feminino" value="Alojamento Feminino" class="text-sm" />
                                    <TextInput
                                        id="qtd_alojamento_feminino"
                                        v-model="form.qtd_alojamento_feminino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_wc_alojamento_feminino" value="WCs Aloj. Feminino" class="text-sm" />
                                    <TextInput
                                        id="qtd_wc_alojamento_feminino"
                                        v-model="form.qtd_wc_alojamento_feminino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Outras instalações -->
                        <div class="p-3 rounded-lg col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4">
                            <h3 class="text-sm font-medium mb-2">Outras Instalações</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <InputLabel for="qtd_xadrez_masculino" value="Xadrez Masculino" class="text-sm" />
                                    <TextInput
                                        id="qtd_xadrez_masculino"
                                        v-model="form.qtd_xadrez_masculino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="area_xadrez_masculino" value="Área Xadrez Masc. (m²)" class="text-sm" />
                                    <TextInput
                                        id="area_xadrez_masculino"
                                        v-model="form.area_xadrez_masculino"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Ex: 3.5"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                    <InputError :message="form.errors.area_xadrez_masculino" class="mt-1 text-xs" />
                                </div>

                                <div>
                                    <InputLabel for="qtd_xadrez_feminino" value="Xadrez Feminino" class="text-sm" />
                                    <TextInput
                                        id="qtd_xadrez_feminino"
                                        v-model="form.qtd_xadrez_feminino"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="area_xadrez_feminino" value="Área Xadrez Fem. (m²)" class="text-sm" />
                                    <TextInput
                                        id="area_xadrez_feminino"
                                        v-model="form.area_xadrez_feminino"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Ex: 3.5"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                    <InputError :message="form.errors.area_xadrez_feminino" class="mt-1 text-xs" />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_cozinha" value="Cozinha" class="text-sm" />
                                    <TextInput
                                        id="qtd_cozinha"
                                        v-model="form.qtd_cozinha"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_area_servico" value="Áreas de Serviço" class="text-sm" />
                                    <TextInput
                                        id="qtd_area_servico"
                                        v-model="form.qtd_area_servico"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel for="qtd_deposito_apreensao" value="Depósito Apreensão" class="text-sm" />
                                    <TextInput
                                        id="qtd_deposito_apreensao"
                                        v-model="form.qtd_deposito_apreensao"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="Quantidade"
                                        :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Suficiência de Instalações -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('instalacoes')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="4. Suficiência de Instalações" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.instalacoes ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.instalacoes" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 gap-4">
                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="tomadas_suficientes" 
                                v-model:checked="form.tomadas_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="tomadas_suficientes" value="Tomadas Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="luminarias_suficientes" 
                                v-model:checked="form.luminarias_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="luminarias_suficientes" value="Luminárias Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="pontos_rede_suficientes" 
                                v-model:checked="form.pontos_rede_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="pontos_rede_suficientes" value="Pontos de Rede Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="pontos_telefone_suficientes" 
                                v-model:checked="form.pontos_telefone_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="pontos_telefone_suficientes" value="Pontos de Telefone Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="pontos_ar_condicionado_suficientes" 
                                v-model:checked="form.pontos_ar_condicionado_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="pontos_ar_condicionado_suficientes" value="Ares-Condicionados Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="pontos_hidraulicos_suficientes" 
                                v-model:checked="form.pontos_hidraulicos_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="pontos_hidraulicos_suficientes" value="Pontos Hidráulicos Suficientes" class="ml-2 text-sm" />
                        </div>

                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            <Checkbox 
                                id="pontos_sanitarios_suficientes" 
                                v-model:checked="form.pontos_sanitarios_suficientes" 
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)" 
                            />
                            <InputLabel for="pontos_sanitarios_suficientes" value="Pontos Sanitários Suficientes" class="ml-2 text-sm" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Acabamentos -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('acabamentos')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="5. Acabamentos" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.acabamentos ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.acabamentos" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <InputLabel for="piso" value="Piso" class="text-sm" />
                            <TextInput
                                id="piso"
                                v-model="form.piso"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Cerâmica, porcelanato, etc"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="parede" value="Parede" class="text-sm" />
                            <TextInput
                                id="parede"
                                v-model="form.parede"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Revestimento das paredes"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="esquadrias" value="Esquadrias" class="text-sm" />
                            <TextInput
                                id="esquadrias"
                                v-model="form.esquadrias"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Alumínio, madeira, etc"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="loucas_metais" value="Louças e Metais" class="text-sm" />
                            <TextInput
                                id="loucas_metais"
                                v-model="form.loucas_metais"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Descrição das louças"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="forro_lage" value="Forro/Laje" class="text-sm" />
                            <TextInput
                                id="forro_lage"
                                v-model="form.forro_lage"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Tipo de forro/laje"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="cobertura" value="Cobertura" class="text-sm" />
                            <TextInput
                                id="cobertura"
                                v-model="form.cobertura"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="telha, etc"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="pintura" value="Pintura" class="text-sm" />
                            <TextInput
                                id="pintura"
                                v-model="form.pintura"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Tipo de pintura"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Equipamentos de Segurança -->
            <div class="col-span-6">
                <div 
                    @click="methods.toggleSection('seguranca')" 
                    class="flex justify-between items-center bg-gray-100 p-3 rounded-t-lg cursor-pointer hover:bg-gray-200 transition-colors duration-200"
                >
                    <InputLabel value="7. Equipamentos de Segurança" class="text-base font-semibold" />
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-transform duration-200" 
                        :class="expandedSections.seguranca ? 'transform rotate-180' : ''"
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div 
                    v-show="expandedSections.seguranca" 
                    class="p-4 border border-gray-200 rounded-b-lg mb-4 bg-white shadow-sm transition-all duration-300"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="extintor_po_quimico" value="Extintor de Pó Químico" class="text-sm" />
                            <TextInput
                                id="extintor_po_quimico"
                                v-model="form.extintor_po_quimico"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Quantidade/Capacidade"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="extintor_co2" value="Extintor de CO2" class="text-sm" />
                            <TextInput
                                id="extintor_co2"
                                v-model="form.extintor_co2"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Quantidade/Capacidade"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="extintor_agua" value="Extintor de Água" class="text-sm" />
                            <TextInput
                                id="extintor_agua"
                                v-model="form.extintor_agua"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Quantidade/Capacidade"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>

                        <div>
                            <InputLabel for="placa_incendio" value="Placas de Sinalização de Emergência Para Incêndio" class="text-sm" />
                            <TextInput
                                id="placa_incendio"
                                v-model="form.placa_incendio"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Sim/Não"
                                :disabled="!permissions?.canUpdateTeam || (isNew && unidade?.is_draft === false)"
                            />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campos obrigatórios -->
            <div class="col-span-6 text-sm text-gray-500 mt-2">
                <p>* Campos obrigatórios</p>
            </div>
        </template>

        <template v-if="permissions?.canUpdateTeam && (!isNew || unidade?.is_draft === false)" #actions>
            <div class="flex items-center justify-between w-full">
                <div>
                    <ActionMessage :on="form.recentlySuccessful" class="me-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800">
                            <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Dados salvos com sucesso!
                        </span>
                    </ActionMessage>
                </div>
                
                <div class="flex space-x-2">
                    <PrimaryButton 
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing"
                        color="gold"
                    >
                        <span class="flex items-center">
                            <span>{{ buttonText }}</span>
                            <svg v-if="unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </FormSection>
</template>

<style scoped>
/* Animações para abrir/fechar seções */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Destaque para campos obrigatórios */
.required-field::after {
    content: '*';
    color: #e53e3e;
    margin-left: 0.25rem;
}

/* Estilo para inputs numéricos */
input[type="number"] {
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Estilo para áreas colapsáveis */
.section-header {
    position: relative;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.section-header:hover {
    background-color: #f3f4f6;
}

/* Estilo para campos editáveis/desabilitados */
.disabled-field {
    background-color: #f9fafb;
    cursor: not-allowed;
}

/* Efeito hover para checkboxes */
.checkbox-hover:hover {
    background-color: #f3f4f6;
}

/* Destaque para itens com erros */
.has-error input,
.has-error select,
.has-error textarea {
    border-color: #f56565;
}

/* Efeito de pulsação para mensagens de sucesso */
@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
    100% {
        opacity: 1;
    }
}

.success-message {
    animation: pulse 2s infinite;
}
</style>