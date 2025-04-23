<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    unidade: Object,
    avaliacao: Object,
    isNew: {
        type: Boolean,
        default: true,
    }
});

const statusOptions = [
    { value: 'aprovada', label: 'Aprovada' },
    { value: 'reprovada', label: 'Reprovada' },
    { value: 'em_revisao', label: 'Em Revisão' },
];

const formTitle = computed(() => {
    return props.isNew ? 'Avaliar Unidade Policial' : 'Atualizar Avaliação';
});

const formDescription = computed(() => {
    return props.isNew 
        ? 'Preencha o formulário para avaliar esta unidade policial.' 
        : 'Atualize a avaliação desta unidade policial.';
});

const form = useForm({
    status: props.avaliacao?.status || 'aprovada',
    nota_geral: props.avaliacao?.nota_geral || 5.0,
    nota_estrutura: props.avaliacao?.nota_estrutura || 5.0,
    nota_acessibilidade: props.avaliacao?.nota_acessibilidade || 5.0,
    nota_conservacao: props.avaliacao?.nota_conservacao || 5.0,
    observacoes: props.avaliacao?.observacoes || '',
});

const submitAvaliacao = () => {
    if (props.isNew) {
        form.post(route('admin.unidades.avaliar', props.unidade.id), {
            errorBag: 'avaliacaoUnidade',
            preserveScroll: true,
        });
    } else {
        form.put(route('admin.avaliacoes.update', props.avaliacao.id), {
            errorBag: 'avaliacaoUnidade',
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <FormSection @submitted="submitAvaliacao">
        <template #title>
            {{ formTitle }}
        </template>

        <template #description>
            {{ formDescription }}
        </template>

        <template #form>
            <!-- Status -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="status" value="Status da Avaliação" />
                <div class="mt-1">
                    <SelectInput id="status" v-model="form.status" class="block w-full">
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </SelectInput>
                </div>
                <InputError :message="form.errors.status" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">
                    O status determina se a unidade foi aprovada, reprovada ou precisa de revisão.
                </p>
            </div>

            <div class="col-span-6 border-b pb-4 mb-4 mt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Notas de Avaliação</h3>
                <p class="text-sm text-gray-600">Atribua notas de 0 a 10 para cada aspecto da unidade.</p>
            </div>

            <!-- Nota Geral -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="nota_geral" value="Nota Geral" />
                <TextInput
                    id="nota_geral"
                    v-model="form.nota_geral"
                    type="number"
                    min="0"
                    max="10"
                    step="0.1"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.nota_geral" class="mt-2" />
            </div>

            <!-- Nota Estrutura -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="nota_estrutura" value="Nota de Estrutura" />
                <TextInput
                    id="nota_estrutura"
                    v-model="form.nota_estrutura"
                    type="number"
                    min="0"
                    max="10"
                    step="0.1"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.nota_estrutura" class="mt-2" />
            </div>

            <!-- Nota Acessibilidade -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="nota_acessibilidade" value="Nota de Acessibilidade" />
                <TextInput
                    id="nota_acessibilidade"
                    v-model="form.nota_acessibilidade"
                    type="number"
                    min="0"
                    max="10"
                    step="0.1"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.nota_acessibilidade" class="mt-2" />
            </div>

            <!-- Nota Conservação -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="nota_conservacao" value="Nota de Conservação" />
                <TextInput
                    id="nota_conservacao"
                    v-model="form.nota_conservacao"
                    type="number"
                    min="0"
                    max="10"
                    step="0.1"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.nota_conservacao" class="mt-2" />
            </div>

            <!-- Observações -->
            <div class="col-span-6 mt-4">
                <InputLabel for="observacoes" value="Observações e Recomendações" />
                <textarea
                    id="observacoes"
                    v-model="form.observacoes"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="4"
                    placeholder="Insira suas observações, recomendações e justificativas para a avaliação"
                ></textarea>
                <InputError :message="form.errors.observacoes" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Avaliação salva com sucesso.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ props.isNew ? 'Salvar Avaliação' : 'Atualizar Avaliação' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>