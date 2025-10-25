<script setup>
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    team: Object,
    acessibilidade: Object,
    permissions: Object,
    isNew: Boolean,
});

const form = useForm({
    team_id: props.team.id,
    unidade_id: props.acessibilidade?.unidade_id || props.team.id,
    rampa_acesso: props.acessibilidade?.rampa_acesso || false,
    corrimao: props.acessibilidade?.corrimao || false,
    piso_tatil: props.acessibilidade?.piso_tatil || false,
    banheiro_adaptado: props.acessibilidade?.banheiro_adaptado || false,
    elevador: props.acessibilidade?.elevador || false,
    sinalizacao_braile: props.acessibilidade?.sinalizacao_braile || false,
    observacoes: props.acessibilidade?.observacoes || "",
});

const updateAcessibilidade = () => {
    if (props.isNew) {
        form.post(route("acessibilidade.store"), {
            errorBag: "updateAcessibilidade",
            preserveScroll: true,
        });
    } else {
        form.put(route("acessibilidade.update", props.acessibilidade.id), {
            errorBag: "updateAcessibilidade",
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <FormSection @submitted="updateAcessibilidade">
        <template #title> Acessibilidade </template>

        <template #description>
            Atualize as informações de acessibilidade desta unidade policial.
        </template>

        <template #form>
            <div class="col-span-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Recursos de Acessibilidade
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Selecione todos os recursos de acessibilidade disponíveis
                    nesta unidade policial.
                </p>
            </div>

            <!-- Rampa de Acesso -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="rampa_acesso"
                        v-model:checked="form.rampa_acesso"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="rampa_acesso"
                        value="Rampa de Acesso"
                        class="ml-2"
                    />
                </div>
            </div>

            <!-- Corrimão -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="corrimao"
                        v-model:checked="form.corrimao"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel for="corrimao" value="Corrimão" class="ml-2" />
                </div>
            </div>

            <!-- Piso Tátil -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="piso_tatil"
                        v-model:checked="form.piso_tatil"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="piso_tatil"
                        value="Piso Tátil"
                        class="ml-2"
                    />
                </div>
            </div>

            <!-- Banheiro Adaptado -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="banheiro_adaptado"
                        v-model:checked="form.banheiro_adaptado"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="banheiro_adaptado"
                        value="Banheiro Adaptado"
                        class="ml-2"
                    />
                </div>
            </div>

            <!-- Elevador -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="elevador"
                        v-model:checked="form.elevador"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel for="elevador" value="Elevador" class="ml-2" />
                </div>
            </div>

            <!-- Sinalização em Braile -->
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center">
                    <Checkbox
                        id="sinalizacao_braile"
                        v-model:checked="form.sinalizacao_braile"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel
                        for="sinalizacao_braile"
                        value="Sinalização em Braile"
                        class="ml-2"
                    />
                </div>
            </div>

            <!-- Observações -->
            <div class="col-span-6 mt-6">
                <InputLabel
                    for="observacoes"
                    value="Observações sobre Acessibilidade"
                />
                <textarea
                    id="observacoes"
                    v-model="form.observacoes"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="3"
                    :disabled="!permissions.canUpdateTeam"
                    placeholder="Informe outras características de acessibilidade ou detalhes adicionais"
                ></textarea>
                <InputError :message="form.errors.observacoes" class="mt-2" />
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
