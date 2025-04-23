<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    team: Object,
    unidade: Object,
    permissions: Object,
    isNew: Boolean,
});

const form = useForm({
    team_id: props.team.id,
    nome: props.unidade?.nome || props.team.name,
    codigo: props.unidade?.codigo || '',
    tipo_estrutural: props.unidade?.tipo_estrutural || '',
    srpc: props.unidade?.srpc || '',
    dspc: props.unidade?.dspc || '',
    nivel: props.unidade?.nivel || '',
    sede: props.unidade?.sede || false,
    cidade_id: props.unidade?.cidade_id || '',
    cep: props.unidade?.cep || '',
    rua: props.unidade?.rua || '',
    numero: props.unidade?.numero || '',
    bairro: props.unidade?.bairro || '',
    complemento: props.unidade?.complemento || '',
    email: props.unidade?.email || '',
    telefone_1: props.unidade?.telefone_1 || '',
    telefone_2: props.unidade?.telefone_2 || '',
    latitude: props.unidade?.latitude || '',
    longitude: props.unidade?.longitude || '',
    tipo_judicial: props.unidade?.tipo_judicial || '',
    status: props.unidade?.status || 'pendente_avaliacao',
    observacoes: props.unidade?.observacoes || '',
    numero_medidor_agua: props.unidade?.numero_medidor_agua || '',
    numero_medidor_energia: props.unidade?.numero_medidor_energia || '',
});

const updateUnidadeInformation = () => {
    if (props.isNew) {
        form.post(route('unidades.store'), {
            errorBag: 'updateUnidadeInformation',
            preserveScroll: true,
        });
    } else {
        form.put(route('unidades.update', props.unidade.id), {
            errorBag: 'updateUnidadeInformation',
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <FormSection @submitted="updateUnidadeInformation">
        <template #title>
            Informações da Unidade
        </template>

        <template #description>
            Atualize as informações gerais desta unidade policial.
        </template>

        <template #form>
            <!-- Nome da Unidade -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="nome" value="Nome da Unidade" />
                <TextInput
                    id="nome"
                    v-model="form.nome"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.nome" class="mt-2" />
            </div>

            <!-- Código -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="codigo" value="Código da Unidade" />
                <TextInput
                    id="codigo"
                    v-model="form.codigo"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.codigo" class="mt-2" />
            </div>

            <!-- Tipo Estrutural -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="tipo_estrutural" value="Tipo Estrutural" />
                <TextInput
                    id="tipo_estrutural"
                    v-model="form.tipo_estrutural"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.tipo_estrutural" class="mt-2" />
            </div>

            <!-- SRPC -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="srpc" value="Superintendência Regional de Polícia Civil (SRPC)" />
                <TextInput
                    id="srpc"
                    v-model="form.srpc"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.srpc" class="mt-2" />
            </div>

            <!-- DSPC -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="dspc" value="Delegacia Seccional de Polícia Civil (DSPC)" />
                <TextInput
                    id="dspc"
                    v-model="form.dspc"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.dspc" class="mt-2" />
            </div>

            <!-- Nível -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="nivel" value="Nível" />
                <TextInput
                    id="nivel"
                    v-model="form.nivel"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.nivel" class="mt-2" />
            </div>

            <!-- Sede -->
            <div class="col-span-6 sm:col-span-4">
                <div class="flex items-center">
                    <Checkbox
                        id="sede"
                        v-model:checked="form.sede"
                        :disabled="!permissions.canUpdateTeam"
                    />
                    <InputLabel for="sede" value="Unidade Sede" class="ml-2" />
                </div>
                <InputError :message="form.errors.sede" class="mt-2" />
            </div>

            <div class="col-span-6 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Endereço</h3>
            </div>

            <!-- CEP -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="cep" value="CEP" />
                <TextInput
                    id="cep"
                    v-model="form.cep"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.cep" class="mt-2" />
            </div>

            <!-- Rua -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="rua" value="Rua" />
                <TextInput
                    id="rua"
                    v-model="form.rua"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.rua" class="mt-2" />
            </div>

            <!-- Número -->
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="numero" value="Número" />
                <TextInput
                    id="numero"
                    v-model="form.numero"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.numero" class="mt-2" />
            </div>

            <!-- Bairro -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="bairro" value="Bairro" />
                <TextInput
                    id="bairro"
                    v-model="form.bairro"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.bairro" class="mt-2" />
            </div>

            <!-- Complemento -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="complemento" value="Complemento" />
                <TextInput
                    id="complemento"
                    v-model="form.complemento"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.complemento" class="mt-2" />
            </div>

            <div class="col-span-6 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Contato</h3>
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>

            <!-- Telefone 1 -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="telefone_1" value="Telefone Principal" />
                <TextInput
                    id="telefone_1"
                    v-model="form.telefone_1"
                    type="tel"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.telefone_1" class="mt-2" />
            </div>

            <!-- Telefone 2 -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="telefone_2" value="Telefone Secundário" />
                <TextInput
                    id="telefone_2"
                    v-model="form.telefone_2"
                    type="tel"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.telefone_2" class="mt-2" />
            </div>

            <div class="col-span-6 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Informações Complementares</h3>
            </div>

            <!-- Tipo Judicial -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="tipo_judicial" value="Tipo Judicial" />
                <TextInput
                    id="tipo_judicial"
                    v-model="form.tipo_judicial"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <p class="mt-1 text-sm text-gray-500">Próprio, locado, cedido, etc.</p>
                <InputError :message="form.errors.tipo_judicial" class="mt-2" />
            </div>

            <!-- Número Medidor Água -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="numero_medidor_agua" value="Número do Medidor de Água" />
                <TextInput
                    id="numero_medidor_agua"
                    v-model="form.numero_medidor_agua"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.numero_medidor_agua" class="mt-2" />
            </div>

            <!-- Número Medidor Energia -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="numero_medidor_energia" value="Número do Medidor de Energia" />
                <TextInput
                    id="numero_medidor_energia"
                    v-model="form.numero_medidor_energia"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam"
                />
                <InputError :message="form.errors.numero_medidor_energia" class="mt-2" />
            </div>

            <!-- Observações -->
            <div class="col-span-6">
                <InputLabel for="observacoes" value="Observações" />
                <textarea
                    id="observacoes"
                    v-model="form.observacoes"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="3"
                    :disabled="!permissions.canUpdateTeam"
                ></textarea>
                <InputError :message="form.errors.observacoes" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing || !permissions.canUpdateTeam">
                Salvar
            </PrimaryButton>
        </template>
    </FormSection>
</template>