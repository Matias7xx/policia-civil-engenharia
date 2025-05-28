<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed } from 'vue';

const emit = defineEmits(['saved']);
const isLoading = ref(false);

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

// Verifica se unidade.id é um número válido
const unidadeId = Number(props.unidade?.id) || null;

const form = useForm({
    unidade_id: unidadeId,
    rampa_acesso: props.acessibilidade?.rampa_acesso || false,
    corrimao: props.acessibilidade?.corrimao || false,
    piso_tatil: props.acessibilidade?.piso_tatil || false,
    banheiro_adaptado: props.acessibilidade?.banheiro_adaptado || false,
    elevador: props.acessibilidade?.elevador || false,
    sinalizacao_braile: props.acessibilidade?.sinalizacao_braile || false,
    observacoes: props.acessibilidade?.observacoes || '',
});

const saveAcessibilidade = () => {
    if (!unidadeId) {
        emit('saved', 'ID da unidade inválido.');
        return;
    }

    isLoading.value = true;

    form.post(route('unidades.saveAcessibilidade'), {
        errorBag: 'saveAcessibilidade',
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
            emit('saved'); // Emite apenas 'saved' para sucesso, sem forçar transição de aba
        },
        onError: (errors) => {
            isLoading.value = false;
            emit('saved', 'Erro ao salvar os dados de acessibilidade.');
        },
    });
};

const checkOptions = [
    { id: 'rampa_acesso', label: 'Rampa de Acesso', description: 'Rampas para acesso de cadeirantes e pessoas com mobilidade reduzida' },
    { id: 'corrimao', label: 'Corrimão', description: 'Corrimãos em escadas e rampas' },
    { id: 'piso_tatil', label: 'Piso Tátil', description: 'Piso com sinalização tátil para deficientes visuais' },
    { id: 'banheiro_adaptado', label: 'Banheiro Adaptado', description: 'Banheiros adaptados para cadeirantes' },
    { id: 'elevador', label: 'Elevador', description: 'Elevador para acesso a andares superiores' },
    { id: 'sinalizacao_braile', label: 'Sinalização em Braille', description: 'Sinalizações em Braille para deficientes visuais' }
];
</script>

<template>
    <div class="bg-white rounded-lg shadow-md">
        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#bea55a]"></div>
                    <p class="text-lg font-semibold">Salvando dados de acessibilidade...</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="saveAcessibilidade">
            <div class="p-6">
                <div class="border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        Acessibilidade da Unidade
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Selecione os recursos de acessibilidade disponíveis nesta unidade.
                    </p>
                </div>

                <!-- Responsive Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="option in checkOptions" :key="option.id" class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Checkbox 
                                    :id="option.id" 
                                    v-model:checked="form[option.id]" 
                                    :disabled="!permissions?.canUpdateTeam"
                                    class="h-5 w-5 text-[#bea55a] focus:ring-[#bea55a]" 
                                />
                            </div>
                            <div class="ml-3">
                                <InputLabel :for="option.id" class="font-medium text-gray-900">{{ option.label }}</InputLabel>
                                <p class="text-xs text-gray-500 mt-1">{{ option.description }}</p>
                            </div>
                        </div>
                        <InputError :message="form.errors[option.id]" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6">
                    <InputLabel for="observacoes" value="Observações Adicionais" class="font-medium text-gray-700 mb-2" />
                    <textarea
                        id="observacoes"
                        v-model="form.observacoes"
                        class="mt-1 block w-full border-gray-300 focus:border-[#bea55a] focus:ring-[#bea55a] rounded-md shadow-sm transition"
                        :disabled="!permissions?.canUpdateTeam"
                        rows="4"
                        placeholder="Descreva outros recursos de acessibilidade disponíveis ou forneça mais detalhes sobre os itens selecionados acima..."
                    ></textarea>
                    <InputError :message="form.errors.observacoes" class="mt-2" />
                </div>
            </div>

            <!-- Barra de ações fixa -->
            <div v-if="permissions?.canUpdateTeam" class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex items-center justify-between sticky bottom-0">
                <div class="text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    O preenchimento completo é importante para a acessibilidade
                </div>
                
                <div class="flex items-center">
                    <ActionMessage :on="form.recentlySuccessful" class="mr-4">
                        <span class="text-green-600 font-medium">Salvo com sucesso</span>
                    </ActionMessage>
                    
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing, 'bg-[#bea55a] hover:bg-[#d4bf7a]': !form.processing }"
                        :disabled="form.processing"
                        color="gold"
                    >
                        {{ form.processing ? 'Salvando...' : (props.unidade?.is_draft === true ? 'Salvar e Continuar' : 'Atualizar Acessibilidade') }}
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

<style scoped>
/* Melhorias para telas pequenas */
@media (max-width: 640px) {
    .sticky {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 40;
    }
}

/* Animações */
.bg-gray-50:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}

/* Estilização dos checkboxes */
input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
}

/* Transitions */
.transition {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>