<script setup>
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    team: Object,
    informacoes: Object,
    permissions: Object,
    isNew: Boolean,
});

const form = useForm({
    team_id: props.team.id,
    unidade_id: props.informacoes?.unidade_id || props.team.id,

    // Características da via e serviços
    pavimentacao_rua: props.informacoes?.pavimentacao_rua || "",
    padrao_energia: props.informacoes?.padrao_energia || "",
    subestacao: props.informacoes?.subestacao || "",
    gerador_energia: props.informacoes?.gerador_energia || "",
    para_raio: props.informacoes?.para_raio || "",
    caixa_dagua: props.informacoes?.caixa_dagua || "",
    internet_cabeada: props.informacoes?.internet_cabeada || "",
    internet_provedor: props.informacoes?.internet_provedor || "",
    telefone_fixo: props.informacoes?.telefone_fixo || "",
    telefone_movel: props.informacoes?.telefone_movel || "",

    // Características do imóvel
    tipo_imovel: props.informacoes?.tipo_imovel || "",
    contrato_locacao_id: props.informacoes?.contrato_locacao_id || "",
    responsavel_locacao_cessao:
        props.informacoes?.responsavel_locacao_cessao || "",
    escritura_publica: props.informacoes?.escritura_publica || "",

    // Características estruturais
    qtd_pavimentos: props.informacoes?.qtd_pavimentos || "",
    cercado_muros: props.informacoes?.cercado_muros || false,
    estacionamento_interno: props.informacoes?.estacionamento_interno || false,
    estacionamento_externo: props.informacoes?.estacionamento_externo || false,
    recuo_frontal: props.informacoes?.recuo_frontal || "",
    recuo_lateral: props.informacoes?.recuo_lateral || "",
    recuo_fundos: props.informacoes?.recuo_fundos || "",

    // Quantitativos de espaços e instalações
    qtd_recepcao: props.informacoes?.qtd_recepcao || 0,
    qtd_wc_publico: props.informacoes?.qtd_wc_publico || 0,
    qtd_gabinetes: props.informacoes?.qtd_gabinetes || 0,
    qtd_sala_oitiva: props.informacoes?.qtd_sala_oitiva || 0,
    qtd_wc_servidores: props.informacoes?.qtd_wc_servidores || 0,
    qtd_alojamento_masculino: props.informacoes?.qtd_alojamento_masculino || 0,
    qtd_wc_alojamento_masculino:
        props.informacoes?.qtd_wc_alojamento_masculino || 0,
    qtd_alojamento_feminino: props.informacoes?.qtd_alojamento_feminino || 0,
    qtd_wc_alojamento_feminino:
        props.informacoes?.qtd_wc_alojamento_feminino || 0,
    qtd_celas_carceragem: props.informacoes?.qtd_celas_carceragem || 0,
    qtd_sala_identificacao: props.informacoes?.qtd_sala_identificacao || 0,
    qtd_cozinha: props.informacoes?.qtd_cozinha || 0,
    qtd_area_servico: props.informacoes?.qtd_area_servico || 0,
    qtd_deposito_apreensao: props.informacoes?.qtd_deposito_apreensao || 0,

    // Suficiência de instalações
    tomadas_suficientes: props.informacoes?.tomadas_suficientes || false,
    luminarias_suficientes: props.informacoes?.luminarias_suficientes || false,
    pontos_rede_suficientes:
        props.informacoes?.pontos_rede_suficientes || false,
    pontos_telefone_suficientes:
        props.informacoes?.pontos_telefone_suficientes || false,
    pontos_ar_condicionado_suficientes:
        props.informacoes?.pontos_ar_condicionado_suficientes || false,
    pontos_hidraulicos_suficientes:
        props.informacoes?.pontos_hidraulicos_suficientes || false,
    pontos_sanitarios_suficientes:
        props.informacoes?.pontos_sanitarios_suficientes || false,

    // Acabamentos
    piso: props.informacoes?.piso || "",
    parede: props.informacoes?.parede || "",
    esquadrias: props.informacoes?.esquadrias || "",
    loucas_metais: props.informacoes?.loucas_metais || "",
    forro_lage: props.informacoes?.forro_lage || "",
    cobertura: props.informacoes?.cobertura || "",
    pintura: props.informacoes?.pintura || "",

    // Equipamentos de segurança
    extintor_po_quimico: props.informacoes?.extintor_po_quimico || "",
    extintor_co2: props.informacoes?.extintor_co2 || "",
    extintor_agua: props.informacoes?.extintor_agua || "",
});

const updateInformacoes = () => {
    if (props.isNew) {
        form.post(route("informacoes-unidade.store"), {
            errorBag: "updateInformacoesUnidade",
            preserveScroll: true,
        });
    } else {
        form.put(route("informacoes-unidade.update", props.informacoes.id), {
            errorBag: "updateInformacoesUnidade",
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <FormSection @submitted="updateInformacoes">
        <template #title> Informações Estruturais </template>

        <template #description>
            Atualize as informações estruturais desta unidade policial.
        </template>

        <template #form>
            <!-- Seção: Características da via e serviços -->
            <div class="col-span-6 border-b pb-4 mb-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Características da Via e Serviços
                </h3>
            </div>

            <!-- Pavimentação da Rua -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel
                    for="pavimentacao_rua"
                    value="Pavimentação da Rua"
                />
                <TextInput
                    id="pavimentacao_rua"
                    v-model="form.pavimentacao_rua"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.pavimentacao_rua"
                    class="mt-2"
                />
            </div>

            <!-- Padrão de Energia -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="padrao_energia" value="Padrão de Energia" />
                <TextInput
                    id="padrao_energia"
                    v-model="form.padrao_energia"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.padrao_energia"
                    class="mt-2"
                />
            </div>

            <!-- Subestação -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="subestacao" value="Subestação" />
                <TextInput
                    id="subestacao"
                    v-model="form.subestacao"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.subestacao" class="mt-2" />
            </div>

            <!-- Gerador de Energia -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="gerador_energia" value="Gerador de Energia" />
                <TextInput
                    id="gerador_energia"
                    v-model="form.gerador_energia"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.gerador_energia"
                    class="mt-2"
                />
            </div>

            <!-- Para-raio -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="para_raio" value="Para-raio" />
                <TextInput
                    id="para_raio"
                    v-model="form.para_raio"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.para_raio" class="mt-2" />
            </div>

            <!-- Caixa D'água -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="caixa_dagua" value="Caixa D'água" />
                <TextInput
                    id="caixa_dagua"
                    v-model="form.caixa_dagua"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.caixa_dagua" class="mt-2" />
            </div>

            <!-- Internet Cabeada -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="internet_cabeada" value="Internet Cabeada" />
                <TextInput
                    id="internet_cabeada"
                    v-model="form.internet_cabeada"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.internet_cabeada"
                    class="mt-2"
                />
            </div>

            <!-- Internet Provedor -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel
                    for="internet_provedor"
                    value="Provedor de Internet"
                />
                <TextInput
                    id="internet_provedor"
                    v-model="form.internet_provedor"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.internet_provedor"
                    class="mt-2"
                />
            </div>

            <!-- Seção: Características do imóvel -->
            <div class="col-span-6 border-b pb-4 mb-4 mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Características do Imóvel
                </h3>
            </div>

            <!-- Tipo do Imóvel -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="tipo_imovel" value="Tipo do Imóvel" />
                <TextInput
                    id="tipo_imovel"
                    v-model="form.tipo_imovel"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.tipo_imovel" class="mt-2" />
            </div>

            <!-- Responsável pela Locação/Cessão -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel
                    for="responsavel_locacao_cessao"
                    value="Responsável pela Locação/Cessão"
                />
                <TextInput
                    id="responsavel_locacao_cessao"
                    v-model="form.responsavel_locacao_cessao"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.responsavel_locacao_cessao"
                    class="mt-2"
                />
            </div>

            <!-- Escritura Pública -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="escritura_publica" value="Escritura Pública" />
                <TextInput
                    id="escritura_publica"
                    v-model="form.escritura_publica"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.escritura_publica"
                    class="mt-2"
                />
            </div>

            <!-- Seção: Características estruturais -->
            <div class="col-span-6 border-b pb-4 mb-4 mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Características Estruturais
                </h3>
            </div>

            <!-- Quantidade de Pavimentos -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel
                    for="qtd_pavimentos"
                    value="Quantidade de Pavimentos"
                />
                <TextInput
                    id="qtd_pavimentos"
                    v-model="form.qtd_pavimentos"
                    type="number"
                    step="0.5"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.qtd_pavimentos"
                    class="mt-2"
                />
            </div>

            <!-- Cercado/Muros -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center mt-6">
                    <Checkbox
                        id="cercado_muros"
                        v-model:checked="form.cercado_muros"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="cercado_muros"
                        value="Cercado/Muros"
                        class="ml-2"
                    />
                </div>
                <InputError :message="form.errors.cercado_muros" class="mt-2" />
            </div>

            <!-- Estacionamento Interno -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="estacionamento_interno"
                        v-model:checked="form.estacionamento_interno"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="estacionamento_interno"
                        value="Estacionamento Interno"
                        class="ml-2"
                    />
                </div>
                <InputError
                    :message="form.errors.estacionamento_interno"
                    class="mt-2"
                />
            </div>

            <!-- Estacionamento Externo -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="estacionamento_externo"
                        v-model:checked="form.estacionamento_externo"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="estacionamento_externo"
                        value="Estacionamento Externo"
                        class="ml-2"
                    />
                </div>
                <InputError
                    :message="form.errors.estacionamento_externo"
                    class="mt-2"
                />
            </div>

            <!-- Recuo Frontal -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="recuo_frontal" value="Recuo Frontal (m)" />
                <TextInput
                    id="recuo_frontal"
                    v-model="form.recuo_frontal"
                    type="number"
                    step="0.01"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.recuo_frontal" class="mt-2" />
            </div>

            <!-- Recuo Lateral -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="recuo_lateral" value="Recuo Lateral (m)" />
                <TextInput
                    id="recuo_lateral"
                    v-model="form.recuo_lateral"
                    type="number"
                    step="0.01"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.recuo_lateral" class="mt-2" />
            </div>

            <!-- Recuo Fundos -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="recuo_fundos" value="Recuo Fundos (m)" />
                <TextInput
                    id="recuo_fundos"
                    v-model="form.recuo_fundos"
                    type="number"
                    step="0.01"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.recuo_fundos" class="mt-2" />
            </div>

            <!-- Seção: Quantitativos de espaços e instalações -->
            <div class="col-span-6 border-b pb-4 mb-4 mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Quantitativos de Espaços e Instalações
                </h3>
            </div>

            <!-- Qtd Recepção -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="qtd_recepcao" value="Recepção (Qtd)" />
                <TextInput
                    id="qtd_recepcao"
                    v-model="form.qtd_recepcao"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.qtd_recepcao" class="mt-2" />
            </div>

            <!-- Qtd WC Público -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="qtd_wc_publico" value="WC Público (Qtd)" />
                <TextInput
                    id="qtd_wc_publico"
                    v-model="form.qtd_wc_publico"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.qtd_wc_publico"
                    class="mt-2"
                />
            </div>

            <!-- Qtd Gabinetes -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="qtd_gabinetes" value="Gabinetes (Qtd)" />
                <TextInput
                    id="qtd_gabinetes"
                    v-model="form.qtd_gabinetes"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.qtd_gabinetes" class="mt-2" />
            </div>

            <!-- Qtd Sala de Oitiva -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel
                    for="qtd_sala_oitiva"
                    value="Sala de Oitiva (Qtd)"
                />
                <TextInput
                    id="qtd_sala_oitiva"
                    v-model="form.qtd_sala_oitiva"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.qtd_sala_oitiva"
                    class="mt-2"
                />
            </div>

            <!-- Qtd WC Servidores -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel
                    for="qtd_wc_servidores"
                    value="WC Servidores (Qtd)"
                />
                <TextInput
                    id="qtd_wc_servidores"
                    v-model="form.qtd_wc_servidores"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.qtd_wc_servidores"
                    class="mt-2"
                />
            </div>

            <!-- Qtd Alojamento Masculino -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel
                    for="qtd_alojamento_masculino"
                    value="Alojamento Masculino (Qtd)"
                />
                <TextInput
                    id="qtd_alojamento_masculino"
                    v-model="form.qtd_alojamento_masculino"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError
                    :message="form.errors.qtd_alojamento_masculino"
                    class="mt-2"
                />
            </div>

            <!-- Seção: Acabamentos -->
            <div class="col-span-6 border-b pb-4 mb-4 mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Acabamentos
                </h3>
            </div>

            <!-- Piso -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="piso" value="Piso" />
                <TextInput
                    id="piso"
                    v-model="form.piso"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.piso" class="mt-2" />
            </div>

            <!-- Parede -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="parede" value="Parede" />
                <TextInput
                    id="parede"
                    v-model="form.parede"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.parede" class="mt-2" />
            </div>

            <!-- Seção: Suficiência de instalações -->
            <div class="col-span-6 border-b pb-4 mb-4 mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Suficiência de Instalações
                </h3>
            </div>

            <!-- Tomadas Suficientes -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="tomadas_suficientes"
                        v-model:checked="form.tomadas_suficientes"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="tomadas_suficientes"
                        value="Tomadas Suficientes"
                        class="ml-2"
                    />
                </div>
                <InputError
                    :message="form.errors.tomadas_suficientes"
                    class="mt-2"
                />
            </div>

            <!-- Luminárias Suficientes -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="luminarias_suficientes"
                        v-model:checked="form.luminarias_suficientes"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="luminarias_suficientes"
                        value="Luminárias Suficientes"
                        class="ml-2"
                    />
                </div>
                <InputError
                    :message="form.errors.luminarias_suficientes"
                    class="mt-2"
                />
            </div>

            <!-- Pontos de Rede Suficientes -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="pontos_rede_suficientes"
                        v-model:checked="form.pontos_rede_suficientes"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="pontos_rede_suficientes"
                        value="Pontos de Rede Suficientes"
                        class="ml-2"
                    />
                </div>
                <InputError
                    :message="form.errors.pontos_rede_suficientes"
                    class="mt-2"
                />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>

            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing || !permissions.canUpdateTeam"
            >
                Salvar
            </PrimaryButton>
        </template>
    </FormSection>
</template>
