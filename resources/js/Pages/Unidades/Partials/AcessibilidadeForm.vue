<script setup>
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { ref, watch } from "vue";
import { useToast } from "@/Composables/useToast";

const toast = useToast();
const emit = defineEmits(["saved"]);
const isLoading = ref(false);

const props = defineProps({
    team: Object,
    unidade: Object,
    acessibilidade: Object,
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

const unidadeId = Number(props.unidade?.id) || null;
const temRegistro = !!props.acessibilidade?.id;

// null = "Não Possui" declarado; true = Sim; false = Não
// Para o form, boolean (true/false) é o valor dos checkboxes "Possui"
// naoPosuiItem controla se o checkbox "Não Possui" está marcado
const naoPosuiItem = ref({
    rampa_acesso:       temRegistro && props.acessibilidade?.rampa_acesso       === null,
    corrimao:           temRegistro && props.acessibilidade?.corrimao           === null,
    piso_tatil:         temRegistro && props.acessibilidade?.piso_tatil         === null,
    banheiro_adaptado:  temRegistro && props.acessibilidade?.banheiro_adaptado  === null,
    elevador:           temRegistro && props.acessibilidade?.elevador           === null,
    sinalizacao_braile: temRegistro && props.acessibilidade?.sinalizacao_braile === null,
});

const form = useForm({
    unidade_id:         unidadeId,
    rampa_acesso:       props.acessibilidade?.rampa_acesso       ?? false,
    corrimao:           props.acessibilidade?.corrimao           ?? false,
    piso_tatil:         props.acessibilidade?.piso_tatil         ?? false,
    banheiro_adaptado:  props.acessibilidade?.banheiro_adaptado  ?? false,
    elevador:           props.acessibilidade?.elevador           ?? false,
    sinalizacao_braile: props.acessibilidade?.sinalizacao_braile ?? false,
    observacoes:        props.acessibilidade?.observacoes        || "",
});

// Quando "Não Possui" marcado → desmarca o checkbox "Possui"
watch(naoPosuiItem, (newVal) => {
    Object.keys(newVal).forEach((field) => {
        if (newVal[field]) form[field] = false;
    });
}, { deep: true });

const checkOptions = [
    { id: "rampa_acesso",       label: "Rampa de Acesso",        description: "Rampas para acesso de cadeirantes e pessoas com mobilidade reduzida" },
    { id: "corrimao",           label: "Corrimão",               description: "Corrimãos em escadas e rampas" },
    { id: "piso_tatil",         label: "Piso Tátil",             description: "Piso com sinalização tátil para deficientes visuais" },
    { id: "banheiro_adaptado",  label: "Banheiro Adaptado",      description: "Banheiros adaptados para cadeirantes" },
    { id: "elevador",           label: "Elevador",               description: "Elevador para acesso a andares superiores" },
    { id: "sinalizacao_braile", label: "Sinalização em Braille", description: "Sinalizações em Braille para deficientes visuais" },
];

const canEdit = () => props.permissions?.canUpdateTeam && (props.isEditable || !props.unidade?.id);

// Se já existe registro salvo, todos os campos foram preenchidos anteriormente.
const touched = ref(
    Object.fromEntries(checkOptions.map(({ id }) => [id, temRegistro]))
);

// Quando "Não Possui" é marcado, o item fica como tocado automaticamente
watch(naoPosuiItem, (newVal) => {
    Object.keys(newVal).forEach((field) => {
        if (newVal[field]) touched.value[field] = true;
    });
}, { deep: true });

// Chamado quando o checkbox "Possui" é clicado
const markTouched = (field) => { touched.value[field] = true; };

const saveAcessibilidade = () => {
    if (!unidadeId) {
        toast.error("ID da unidade inválido.");
        emit("saved", "ID da unidade inválido.");
        return;
    }

    // Validação: cada item deve ter "Não Possui" OU ter sido explicitamente tocado
    // Válido apenas se há uma escolha ativa: "Possui" marcado OU "Não Possui" marcado
    const naoPreenchidos = checkOptions.filter(
        (opt) => !naoPosuiItem.value[opt.id] && !form[opt.id]
    );
    if (naoPreenchidos.length > 0) {
        naoPreenchidos.forEach((opt) => {
            toast.error(`"${opt.label}": marque se possui ou selecione "Não Possui".`);
        });
        return;
    }

    // Antes de enviar, setar null nos campos com "Não Possui"
    Object.keys(naoPosuiItem.value).forEach((field) => {
        if (naoPosuiItem.value[field]) form[field] = null;
    });

    isLoading.value = true;

    form.post(route("unidades.saveAcessibilidade"), {
        errorBag: "saveAcessibilidade",
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
            // Restaurar false nos campos que eram null (para o form não ficar com null)
            checkOptions.forEach(({ id }) => {
                if (naoPosuiItem.value[id]) form[id] = false;
            });
            toast.success("Dados de acessibilidade salvos com sucesso!");
            emit("saved");
        },
        onError: () => {
            isLoading.value = false;
            // Restaurar false nos campos que eram null
            checkOptions.forEach(({ id }) => {
                if (naoPosuiItem.value[id]) form[id] = false;
            });
            toast.error("Erro ao salvar os dados de acessibilidade.");
            emit("saved", "Erro ao salvar os dados de acessibilidade.");
        },
    });
};
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
                    <h3 class="text-xl font-semibold text-gray-900">Acessibilidade da Unidade *</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Para cada item, informe se a unidade <strong>possui</strong> ou marque <strong>"Não Possui"</strong>.
                    </p>
                </div>

                <!-- Grid de itens -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="option in checkOptions"
                        :key="option.id"
                        class="rounded-lg border transition-colors duration-200"
                        :class="naoPosuiItem[option.id]
                            ? 'border-red-200 bg-red-50'
                            : (form[option.id] ? 'border-green-200 bg-green-50' : 'border-amber-300 bg-amber-50')"
                    >
                        <!-- Checkbox "Não Possui" no topo do card -->
                        <div
                            class="flex items-center gap-2 px-4 pt-3 pb-2 border-b"
                            :class="naoPosuiItem[option.id] ? 'border-red-200' : 'border-gray-200'"
                        >
                            <Checkbox
                                :id="`nao_possui_${option.id}`"
                                v-model:checked="naoPosuiItem[option.id]"
                                :disabled="!canEdit()"
                                class="h-3.5 w-3.5"
                                @change="markTouched(option.id)"
                            />
                            <label
                                :for="`nao_possui_${option.id}`"
                                class="text-xs font-semibold cursor-pointer select-none"
                                :class="naoPosuiItem[option.id] ? 'text-red-600' : 'text-gray-400'"
                            >
                                Não Possui
                            </label>
                        </div>

                        <!-- Checkbox "Possui" (desabilitado quando "Não Possui" marcado) -->
                        <div
                            class="flex items-center gap-3 px-4 py-3 transition-opacity duration-200"
                            :class="{ 'opacity-40 pointer-events-none': naoPosuiItem[option.id] }"
                        >
                            <Checkbox
                                :id="option.id"
                                v-model:checked="form[option.id]"
                                :disabled="!canEdit() || naoPosuiItem[option.id]"
                                class="h-5 w-5 text-[#bea55a] focus:ring-[#bea55a] flex-shrink-0"
                                @change="markTouched(option.id)"
                            />
                            <div>
                                <InputLabel :for="option.id" class="font-medium text-gray-900 cursor-pointer">
                                    {{ option.label }}
                                </InputLabel>
                                <p class="text-xs text-gray-500 mt-0.5">{{ option.description }}</p>
                            </div>
                        </div>

                        <InputError :message="form.errors[option.id]" class="px-4 pb-2 text-xs" />
                    </div>
                </div>

                <!-- Observações -->
                <div class="mt-6">
                    <InputLabel for="observacoes" value="Observações Adicionais" class="font-medium text-gray-700 mb-2" />
                    <textarea
                        id="observacoes"
                        v-model="form.observacoes"
                        class="mt-1 block w-full border-gray-300 focus:border-[#bea55a] focus:ring-[#bea55a] rounded-md shadow-sm"
                        :disabled="!canEdit()"
                        rows="4"
                        placeholder="Descreva outros recursos de acessibilidade ou forneça mais detalhes..."
                    ></textarea>
                    <InputError :message="form.errors.observacoes" class="mt-2" />
                </div>
            </div>

            <!-- Barra de ações -->
            <div
                v-if="permissions?.canUpdateTeam"
                class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex items-center justify-between sticky bottom-0"
            >
                <div class="text-sm text-yellow-700 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Todos os itens são obrigatórios — marque "Possui" ou "Não Possui"
                </div>
                <div class="flex items-center">
                    <ActionMessage :on="form.recentlySuccessful" class="mr-4">
                        <span class="text-green-600 font-medium">Salvo com sucesso</span>
                    </ActionMessage>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing, 'bg-[#bea55a] hover:bg-[#d4bf7a]': !form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Salvando..." : props.unidade?.is_draft === true ? "Salvar e Continuar" : "Atualizar Acessibilidade" }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
input[type="checkbox"] { width: 1.1rem; height: 1.1rem; }
</style>