<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ArrowPathIcon, ClipboardDocumentListIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const toast = useToast();

const props = defineProps({
    unidade: {
        type: Object,
        required: true,
    },
    avaliacoes: {
        type: Array,
        default: () => [],
    },
    avaliacao: {
        type: Object,
        default: null,
    },
    isNew: {
        type: Boolean,
        default: true
    }
});

// Estado para controlar se uma avaliação foi salva recentemente
const recentlySaved = ref(false);
const showNewEvaluationPrompt = ref(false);

// Valores reativos para os controles deslizantes
const notaGeral = ref(props.avaliacao?.nota_geral || 5.0);
const notaEstrutura = ref(props.avaliacao?.nota_estrutura || 5.0);
const notaAcessibilidade = ref(props.avaliacao?.nota_acessibilidade || 5.0);

const form = useForm({
    status: props.avaliacao?.status || 'pendente_avaliacao',
    nota_geral: notaGeral.value,
    nota_estrutura: notaEstrutura.value,
    nota_acessibilidade: notaAcessibilidade.value,
    observacoes: props.avaliacao?.observacoes || '',
});

// Observar alterações nos controles deslizantes para atualizar os valores do formulário
watch(notaGeral, (value) => form.nota_geral = parseFloat(value));
watch(notaEstrutura, (value) => form.nota_estrutura = parseFloat(value));
watch(notaAcessibilidade, (value) => form.nota_acessibilidade = parseFloat(value));

const submitAvaliacao = () => {
    // Verificar se unidade.id existe
    const unidadeId = props.unidade?.id;
    if (!unidadeId) {
        console.error('ID da unidade não encontrado. Verifique os props.');
        return;
    }

    if (props.isNew) {
        form.post(route('admin.unidades.avaliar', unidadeId), {
            errorBag: 'avaliacaoUnidade',
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Avaliação salva com sucesso.');
                handleSuccessfulSave();
            },
            onError: () => {
                toast.error('Erro ao salvar avaliação.');
            },
        });
    } else {
        // Verificar se avaliacao.id existe
        const avaliacaoId = props.avaliacao?.id;
        if (!avaliacaoId) {
            console.error('ID da avaliação não encontrado. Verifique os props.');
            return;
        }
        form.put(route('admin.avaliacoes.update', avaliacaoId), {
            errorBag: 'avaliacaoUnidade',
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Avaliação atualizada com sucesso.');
                handleSuccessfulSave();
            },
            onError: () => {
                toast.error('Erro ao atualizar avaliação.');
            },
        });
    }
};

// Função para tratar o sucesso no salvamento
const handleSuccessfulSave = () => {
    recentlySaved.value = true;
    showNewEvaluationPrompt.value = true;
    
    // Esconder a mensagem após 5 segundos
    setTimeout(() => {
        showNewEvaluationPrompt.value = false;
    }, 5000);
};

// Redefinir formulário para valores iniciais
const resetForm = () => {
    notaGeral.value = props.avaliacao?.nota_geral || 5.0;
    notaEstrutura.value = props.avaliacao?.nota_estrutura || 5.0;
    notaAcessibilidade.value = props.avaliacao?.nota_acessibilidade || 5.0;
    form.observacoes = props.avaliacao?.observacoes || '';
    form.status = props.avaliacao?.status || 'pendente_avaliacao';
    
    // Resetar estados de controle
    recentlySaved.value = false;
    showNewEvaluationPrompt.value = false;
};

// iniciar uma nova avaliação
const startNewEvaluation = () => {
    // Limpar valores para uma nova avaliação
    notaGeral.value = 5.0;
    notaEstrutura.value = 5.0;
    notaAcessibilidade.value = 5.0;
    form.observacoes = '';
    form.status = 'pendente_avaliacao';
    
    // Resetar estados
    recentlySaved.value = false;
    showNewEvaluationPrompt.value = false;
    
    toast.info('Formulário preparado para nova avaliação.');
};

// Funções auxiliares para notas
const getNotaLetra = (nota) => {
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return '';
    if (notaNum >= 9.5) return 'A+';
    if (notaNum >= 9.0) return 'A';
    if (notaNum >= 8.0) return 'B';
    if (notaNum >= 7.0) return 'C';
    if (notaNum >= 6.0) return 'D';
    if (notaNum >= 5.0) return 'E';
    if (notaNum >= 4.0) return 'F';
    if (notaNum >= 3.0) return 'G';
    if (notaNum >= 2.0) return 'H';
    if (notaNum >= 1.0) return 'I';
    return 'J';
};

const getNotaClass = (nota) => {
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return '';
    if (notaNum >= 9.0) return 'bg-green-600 text-white';
    if (notaNum >= 7.0) return 'bg-green-500 text-white';
    if (notaNum >= 5.0) return 'bg-yellow-500 text-white';
    if (notaNum >= 3.0) return 'bg-orange-500 text-white';
    return 'bg-red-500 text-white';
};

const getNotaBgClass = (nota) => {
    const notaNum = parseFloat(nota);
    if (isNaN(notaNum)) return 'bg-gray-200';
    if (notaNum >= 9.0) return 'bg-green-100';
    if (notaNum >= 7.0) return 'bg-green-50';
    if (notaNum >= 5.0) return 'bg-yellow-50';
    if (notaNum >= 3.0) return 'bg-orange-50';
    return 'bg-red-50';
};

// Texto de dica para cada grau de avaliação
const getNoteTooltip = (nota) => {
    const grade = getNotaLetra(nota);
    switch (grade) {
        case 'A+':
        case 'A': return 'Excelente: Condições ideais sem necessidade de melhorias';
        case 'B': return 'Ótimo: Condições muito boas com pequenas melhorias necessárias';
        case 'C': return 'Bom: Condições adequadas com algumas melhorias recomendadas';
        case 'D': return 'Satisfatório: Condições básicas atendidas, melhorias necessárias';
        case 'E': return 'Regular: Atende minimamente, melhorias significativas necessárias';
        case 'F': return 'Insuficiente: Não atende adequadamente, melhorias urgentes';
        case 'G': return 'Ruim: Condições precárias, necessidade de intervenção';
        case 'H': return 'Muito Ruim: Condições críticas, intervenção imediata';
        case 'I': return 'Péssimo: Condições extremamente precárias, risco à segurança';
        case 'J': return 'Crítico: Inapto para uso, riscos severos à segurança';
        default: return '';
    }
};
</script>

<template>
    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
      <h3 class="text-xl font-semibold text-gray-900 flex items-center">
         <ClipboardDocumentListIcon class="w-5 h-5 mr-2 text-gray-600" />
        {{ recentlySaved && props.isNew ? 'Avaliação Realizada' : 'Nova Avaliação' }}
      </h3>
    </div>

    <div class="w-full mx-auto">

        <!-- Alerta de sucesso após salvamento -->
        <div v-if="showNewEvaluationPrompt" class="bg-green-50 border border-green-200 p-4 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-700 font-medium">
                        {{ props.isNew ? 'Avaliação salva com sucesso!' : 'Avaliação atualizada com sucesso!' }}
                    </p>
                </div>
                <button 
                    v-if="props.isNew"
                    @click="startNewEvaluation"
                    class="inline-flex items-center px-3 py-1 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    Nova Avaliação
                </button>
            </div>
        </div>

        <div v-if="unidade.status !== 'aprovada'" class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg space-y-6">
            <p class="text-yellow-700 text-center">
                Somente unidades com o formulário "Aprovado" podem ser avaliadas.
            </p>
        </div>

        <form v-else @submit.prevent="submitAvaliacao" class="space-y-6">
            <div class="p-6 rounded-lg shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nota Geral -->
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg transition-all duration-300">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel for="nota_geral" value="Nota Geral" class="text-base" />
                            <div class="flex items-center">
                                <span class="text-xl font-bold mr-2">{{ notaGeral }}</span>
                                <span :class="getNotaClass(notaGeral)" class="text-sm font-medium p-1 px-2 rounded-md" :title="getNoteTooltip(notaGeral)">
                                    {{ getNotaLetra(notaGeral) }}
                                </span>
                            </div>
                        </div>
                        <div class="relative">
                            <input 
                                type="range"
                                id="nota_geral"
                                v-model="notaGeral"
                                min="1"
                                max="10"
                                step="0.1"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                :disabled="recentlySaved && props.isNew"
                            />
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ getNoteTooltip(notaGeral) }}</p>
                        <InputError :message="form.errors.nota_geral" class="mt-2" />
                    </div>

                    <!-- Nota Estrutura -->
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg transition-all duration-300">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel for="nota_estrutura" value="Nota Estrutura" class="text-base" />
                            <div class="flex items-center">
                                <span class="text-xl font-bold mr-2">{{ notaEstrutura }}</span>
                                <span :class="getNotaClass(notaEstrutura)" class="text-sm font-medium p-1 px-2 rounded-md" :title="getNoteTooltip(notaEstrutura)">
                                    {{ getNotaLetra(notaEstrutura) }}
                                </span>
                            </div>
                        </div>
                        <div class="relative">
                            <input 
                                type="range"
                                id="nota_estrutura"
                                v-model="notaEstrutura"
                                min="1"
                                max="10"
                                step="0.1"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                :disabled="recentlySaved && props.isNew"
                            />
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Avalie a qualidade da estrutura física do imóvel</p>
                        <InputError :message="form.errors.nota_estrutura" class="mt-2" />
                    </div>

                    <!-- Nota Acessibilidade -->
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg transition-all duration-300">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel for="nota_acessibilidade" value="Nota Acessibilidade" class="text-base" />
                            <div class="flex items-center">
                                <span class="text-xl font-bold mr-2">{{ notaAcessibilidade }}</span>
                                <span :class="getNotaClass(notaAcessibilidade)" class="text-sm font-medium p-1 px-2 rounded-md" :title="getNoteTooltip(notaAcessibilidade)">
                                    {{ getNotaLetra(notaAcessibilidade) }}
                                </span>
                            </div>
                        </div>
                        <div class="relative">
                            <input 
                                type="range"
                                id="nota_acessibilidade"
                                v-model="notaAcessibilidade"
                                min="1"
                                max="10"
                                step="0.1"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                :disabled="recentlySaved && props.isNew"
                            />
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Avalie os recursos de acessibilidade disponíveis</p>
                        <InputError :message="form.errors.nota_acessibilidade" class="mt-2" />
                    </div>
                </div>

                <!-- Legenda das classificações -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Legenda de Classificação</h4>
                    <div class="grid grid-cols-5 gap-2 text-xs">
                        <div class="bg-green-600 text-white p-1 rounded text-center">A (9.0-10.0)<br/>Excelente</div>
                        <div class="bg-green-500 text-white p-1 rounded text-center">B-C (7.0-8.9)<br/>Bom</div>
                        <div class="bg-yellow-500 text-white p-1 rounded text-center">D-E (5.0-6.9)<br/>Regular</div>
                        <div class="bg-orange-500 text-white p-1 rounded text-center">F-G (3.0-4.9)<br/>Ruim</div>
                        <div class="bg-red-500 text-white p-1 rounded text-center">H-J (1.0-2.9)<br/>Crítico</div>
                    </div>
                </div>

                <!-- Observações -->
                <div class="mt-6">
                    <InputLabel for="observacoes" value="Observações e Recomendações" />
                    <textarea
                        id="observacoes"
                        v-model="form.observacoes"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="4"
                        placeholder="Insira suas observações, recomendações e justificativas para a avaliação"
                        :disabled="recentlySaved && props.isNew"
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Descreva os pontos fortes e fracos, bem como recomendações específicas para melhoria.
                    </p>
                    <InputError :message="form.errors.observacoes" class="mt-2" />
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="flex items-center justify-end bg-gray-50 p-4 rounded-lg">
                <button 
                    type="button" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition me-3"
                    @click="resetForm"
                    :disabled="form.processing"
                >
                    <ArrowPathIcon class="h-4 w-4 mr-1" />
                    Redefinir
                </button>

                <!-- Botão Nova Avaliação (aparece após salvar) -->
                <button 
                    v-if="recentlySaved && props.isNew"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-green-300 rounded-md font-semibold text-xs text-green-700 uppercase tracking-widest shadow-sm bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition me-3"
                    @click="startNewEvaluation"
                >
                    <PlusIcon class="h-4 w-4 mr-1" />
                    Nova Avaliação
                </button>

                <PrimaryButton 
                    :class="{ 'opacity-25': form.processing || (recentlySaved && props.isNew) }" 
                    :disabled="form.processing || (recentlySaved && props.isNew)" 
                    color="gold"
                >
                    {{ props.isNew ? 'Salvar Avaliação' : 'Atualizar Avaliação' }}
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>

<style scoped>
/* Estilização personalizada do controle deslizante */
input[type=range] {
  height: 38px;
  -webkit-appearance: none;
  margin: 10px 0;
  width: 100%;
  background: transparent;
}
input[type=range]:focus {
  outline: none;
}
input[type=range]::-webkit-slider-runnable-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  animate: 0.2s;
  box-shadow: 0px 0px 0px #000000;
  background: #E5E7EB;
  border-radius: 5px;
  border: 0px solid #000000;
}
input[type=range]::-webkit-slider-thumb {
  box-shadow: 0px 0px 1px #000000;
  border: 1px solid #BEA55A;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #ffffff;
  cursor: pointer;
  -webkit-appearance: none;
  margin-top: -6px;
}
input[type=range]:focus::-webkit-slider-runnable-track {
  background: #E5E7EB;
}
input[type=range]::-moz-range-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  animate: 0.2s;
  box-shadow: 0px 0px 0px #000000;
  background: #E5E7EB;
  border-radius: 5px;
  border: 0px solid #000000;
}
input[type=range]::-moz-range-thumb {
  box-shadow: 0px 0px 1px #000000;
  border: 1px solid #BEA55A;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #BEA55A;
  cursor: pointer;
}
input[type=range]::-ms-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  animate: 0.2s;
  background: transparent;
  border-color: transparent;
  color: transparent;
}
input[type=range]::-ms-fill-lower {
  background: #E5E7EB;
  border: 0px solid #000000;
  border-radius: 10px;
  box-shadow: 0px 0px 0px #000000;
}
input[type=range]::-ms-fill-upper {
  background: #E5E7EB;
  border: 0px solid #000000;
  border-radius: 10px;
  box-shadow: 0px 0px 0px #000000;
}
input[type=range]::-ms-thumb {
  margin-top: 1px;
  box-shadow: 0px 0px 1px #000000;
  border: 1px solid #BEA55A;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #BEA55A;
  cursor: pointer;
}
input[type=range]:focus::-ms-fill-lower {
  background: #E5E7EB;
}
input[type=range]:focus::-ms-fill-upper {
  background: #E5E7EB;
}

/* Estilo para campos desabilitados */
input[type=range]:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #f9fafb;
}
</style>