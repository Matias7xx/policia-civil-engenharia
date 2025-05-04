<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, onMounted, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    unidade: Object,
    midias: Array,
    permissions: Object,
});

const emit = defineEmits(['saved']);

// Carregar tipos de mídia ativos
const midiaTipos = ref([]);
const fileInputs = ref({});
const isLoading = ref(true);
const previewImages = ref({});
let isMounted = ref(true);
const mensagemFeedback = ref('');
const tipoFeedback = ref(''); // 'success', 'error', 'info'

onMounted(async () => {
    try {
        const response = await axios.get('/midia-tipos/ativos');
        if (isMounted.value) {
            midiaTipos.value = response.data.map(tipo => ({
                id: tipo.id,
                nome: tipo.nome,
                descricao: tipo.descricao || 'Sem descrição disponível',
                isRequired: ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                            'foto_medidor_agua', 'foto_medidor_energia'].includes(tipo.nome)
            }));
            isLoading.value = false;
        }
    } catch (error) {
        console.error('Erro ao carregar tipos de mídia:', error.response?.data || error.message);
        if (isMounted.value) {
            mensagemFeedback.value = `Erro ao carregar tipos de mídia: ${error.response?.statusText || 'Desconhecido'}`;
            tipoFeedback.value = 'error';
            isLoading.value = false;
        }
    }
});

onBeforeUnmount(() => {
    isMounted.value = false;
});

const form = useForm({
    unidade_id: props.unidade?.id || '',
    midia_files: {},
    midia_tipos: {},
});

const handleFileChange = (midiaTipoId, event) => {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;
    
    form.midia_files[midiaTipoId] = files;
    form.midia_tipos[midiaTipoId] = midiaTipoId;
    
    // Criar previews para imagens
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (!previewImages.value[midiaTipoId]) {
                    previewImages.value[midiaTipoId] = [];
                }
                previewImages.value[midiaTipoId].push(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
};

const removeFile = (midiaTipoId, index) => {
    if (previewImages.value[midiaTipoId]) {
        previewImages.value[midiaTipoId].splice(index, 1);
    }
    
    if (form.midia_files[midiaTipoId]) {
        const newFiles = Array.from(form.midia_files[midiaTipoId]);
        newFiles.splice(index, 1);
        form.midia_files[midiaTipoId] = newFiles;
        
        if (newFiles.length === 0) {
            delete form.midia_files[midiaTipoId];
            delete form.midia_tipos[midiaTipoId];
        }
    }
};

const saveMidiasAndFinalize = () => {
    if (!props.permissions?.canUpdateTeam || props.unidade?.is_draft === false) {
        mensagemFeedback.value = 'O cadastro está finalizado e não pode ser editado.';
        tipoFeedback.value = 'error';
        emit('saved', null, mensagemFeedback.value);
        return;
    }

    const requiredMidiaTipos = [
        'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos',
        'foto_medidor_agua', 'foto_medidor_energia',
    ];

    const missingRequired = requiredMidiaTipos.filter(nome => {
        const tipo = midiaTipos.value.find(t => t.nome === nome);
        if (!tipo) return true;
        
        // Verifica se já tem midias deste tipo ou se há novos arquivos
        const temMidiasExistentes = midiasPorTipo.value[tipo.id]?.length > 0;
        const temNovosArquivos = form.midia_files[tipo.id]?.length > 0;
        
        return !temMidiasExistentes && !temNovosArquivos;
    });

    if (missingRequired.length > 0) {
        const missingNomes = missingRequired.map(nome => {
            const tipo = midiaTipos.value.find(t => t.nome === nome);
            return tipo ? tipo.descricao || tipo.nome : nome;
        });
        
        mensagemFeedback.value = `Os seguintes tipos de mídia são obrigatórios: ${missingNomes.join(', ')}.`;
        tipoFeedback.value = 'error';
        emit('saved', null, mensagemFeedback.value);
        return;
    }

    const formData = new FormData();
    formData.append('unidade_id', form.unidade_id);

    let fileIndex = 0;
    Object.entries(form.midia_files).forEach(([midiaTipoId, files]) => {
        if (!files || files.length === 0) return;
        files.forEach(file => {
            formData.append(`files[${fileIndex}]`, file);
            formData.append(`midia_tipos[${fileIndex}]`, midiaTipoId);
            fileIndex++;
        });
    });

    if (fileIndex === 0) {
        mensagemFeedback.value = 'Nenhum arquivo novo foi selecionado.';
        tipoFeedback.value = 'info';
        
        // Se não há novos arquivos mas todos os obrigatórios já estão cadastrados, finalizar mesmo assim
        form.post(route('unidades.finalize', props.unidade.id), {
            errorBag: 'finalizeUnidade',
            preserveScroll: true,
            onSuccess: () => {
                if (!isMounted.value) return;
                mensagemFeedback.value = 'Cadastro finalizado com sucesso!';
                tipoFeedback.value = 'success';
                emit('saved', null, mensagemFeedback.value);
            },
            onError: (errors) => {
                if (!isMounted.value) return;
                mensagemFeedback.value = 'Erro ao finalizar o cadastro: ' + (Object.values(errors).join(', ') || 'Verifique os dados.');
                tipoFeedback.value = 'error';
                emit('saved', null, mensagemFeedback.value);
            },
        });
        return;
    }
    
    mensagemFeedback.value = 'Enviando arquivos...';
    tipoFeedback.value = 'info';

    axios.post(route('midias.store'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            mensagemFeedback.value = `Enviando arquivos... ${percentCompleted}%`;
        }
    })
    .then(response => {
        if (!isMounted.value) return;
        if (response.data.success) {
            mensagemFeedback.value = 'Arquivos salvos! Finalizando cadastro...';
            tipoFeedback.value = 'success';
            
            form.post(route('unidades.finalize', props.unidade.id), {
                errorBag: 'finalizeUnidade',
                preserveScroll: true,
                onSuccess: () => {
                    if (!isMounted.value) return;
                    mensagemFeedback.value = 'Cadastro finalizado com sucesso!';
                    tipoFeedback.value = 'success';
                    emit('saved', null, mensagemFeedback.value);
                    
                    // Limpar os previews após o envio
                    previewImages.value = {};
                },
                onError: (errors) => {
                    if (!isMounted.value) return;
                    mensagemFeedback.value = 'Erro ao finalizar o cadastro: ' + (Object.values(errors).join(', ') || 'Verifique os dados.');
                    tipoFeedback.value = 'error';
                    emit('saved', null, mensagemFeedback.value);
                },
            });
        } else {
            mensagemFeedback.value = response.data.message || 'Erro ao salvar as mídias.';
            tipoFeedback.value = 'error';
            emit('saved', null, mensagemFeedback.value);
        }
    })
    .catch(error => {
        if (!isMounted.value) return;
        mensagemFeedback.value = error.response?.data?.message || 'Erro ao salvar as mídias. Verifique os arquivos.';
        tipoFeedback.value = 'error';
        emit('saved', null, mensagemFeedback.value);
    });
};

const midiasPorTipo = computed(() => {
    const grouped = {};
    if (props.midias) {
        props.midias.forEach(midia => {
            if (!grouped[midia.tipo.id]) grouped[midia.tipo.id] = [];
            grouped[midia.tipo.id].push(midia);
        });
    }
    return grouped;
});

const getFileSize = (size) => {
    if (size < 1024) return `${size} bytes`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

const getTabelas = computed(() => {
    const tiposAgrupados = {};
    
    midiaTipos.value.forEach(tipo => {
        const categoria = tipo.nome.includes('foto_') ? 'Fotos Externas' :
                         tipo.nome.includes('recepcao') || tipo.nome.includes('wc') || tipo.nome.includes('gabinete') ? 'Áreas Internas' :
                         'Outras Mídias';
        
        if (!tiposAgrupados[categoria]) {
            tiposAgrupados[categoria] = [];
        }
        
        tiposAgrupados[categoria].push(tipo);
    });
    
    return Object.entries(tiposAgrupados);
});
</script>

<template>
    <FormSection @submitted="saveMidiasAndFinalize">
        <template #title>
            Mídias da Unidade
        </template>

        <template #description>
            <p>
                Adicione fotos da unidade para cada tipo de mídia. 
                <strong class="text-red-600">Os itens com * são obrigatórios.</strong>
            </p>
            <p class="mt-2 text-sm text-gray-600">
                Formatos aceitos: JPG, PNG (máx. 5MB)
            </p>
        </template>

        <template #form>
            <div class="col-span-6">
                <!-- Mensagem de feedback -->
                <div v-if="mensagemFeedback" 
                     class="mb-4 p-3 rounded-md text-sm"
                     :class="{
                        'bg-green-100 text-green-800': tipoFeedback === 'success',
                        'bg-red-100 text-red-800': tipoFeedback === 'error',
                        'bg-blue-100 text-blue-800': tipoFeedback === 'info'
                     }">
                    {{ mensagemFeedback }}
                </div>
                
                <!-- Estado de carregamento -->
                <div v-if="isLoading" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-gray-900"></div>
                    <span class="ml-2">Carregando tipos de mídia...</span>
                </div>
                
                <div v-else-if="midiaTipos.length === 0" class="mt-2">
                    <p class="text-red-600">Nenhum tipo de mídia disponível ou erro ao carregar.</p>
                </div>
                
                <!-- Conteúdo principal quando carregado -->
                <div v-else>

                    <!-- Seções para cada categoria -->
                    <div v-for="(grupo, index) in getTabelas" :key="index" :id="`section-${index}`" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                            {{ grupo[0] }}
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <div v-for="midiaTipo in grupo[1]" :key="midiaTipo.id" 
                                 class="border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="flex justify-between items-start mb-2">
                                    <InputLabel 
                                        :for="`file-${midiaTipo.id}`" 
                                        :value="midiaTipo.nome" 
                                        class="font-medium text-gray-800"
                                    />
                                    <span v-if="midiaTipo.isRequired" class="text-red-600 text-lg font-bold">*</span>
                                </div>
                                
                                <!-- Área de drop e upload -->
                                <div 
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-gray-50 transition-colors duration-200 cursor-pointer mb-3"
                                    :class="{ 'border-red-300': midiaTipo.isRequired && !midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length }"
                                    @click="$refs[`fileInput-${midiaTipo.id}`][0].click()"
                                >
                                    <input
                                        :id="`file-${midiaTipo.id}`"
                                        :ref="`fileInput-${midiaTipo.id}`"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        multiple
                                        :disabled="!permissions?.canUpdateTeam || props.unidade?.is_draft === false"
                                        @change="handleFileChange(midiaTipo.id, $event)"
                                    />
                                    
                                    <div class="flex flex-col items-center justify-center py-3">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="text-sm text-gray-600">Clique para selecionar ou arraste arquivos aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG até 5MB</p>
                                    </div>
                                </div>

                                <InputError :message="form.errors[`files[${midiaTipo.id}]`]" class="mt-2" />
                                
                                <!-- Previews de imagens a serem enviadas -->
                                <div v-if="previewImages[midiaTipo.id]?.length" class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Novas imagens selecionadas:</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <div 
                                            v-for="(preview, idx) in previewImages[midiaTipo.id]" 
                                            :key="`preview-${midiaTipo.id}-${idx}`"
                                            class="relative group"
                                        >
                                            <img 
                                                :src="preview" 
                                                class="h-24 w-full object-cover rounded border border-gray-200"
                                                :alt="`Preview ${idx+1}`"
                                            />
                                            <button 
                                                type="button"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                @click.prevent="removeFile(midiaTipo.id, idx)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <p class="text-xs text-gray-500 mt-1 truncate">
                                                {{ form.midia_files[midiaTipo.id][idx]?.name }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ form.midia_files[midiaTipo.id][idx] ? getFileSize(form.midia_files[midiaTipo.id][idx].size) : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mídias já cadastradas -->
                                <div v-if="midiasPorTipo[midiaTipo.id]?.length" class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Imagens já cadastradas:</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <div 
                                            v-for="midia in midiasPorTipo[midiaTipo.id]" 
                                            :key="midia.id"
                                            class="relative group"
                                        >
                                            <img
                                                v-if="midia.is_imagem"
                                                :src="midia.url"
                                                :alt="`${midiaTipo.nome} - ${midia.id}`"
                                                class="h-24 w-full object-cover rounded border border-gray-200"
                                                @click="window.open(midia.url, '_blank')"
                                            />
                                            <p class="text-xs text-gray-500 mt-1">{{ midia.tamanho_formatado }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="permissions?.canUpdateTeam && props.unidade?.is_draft" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>

            <PrimaryButton 
                :class="{ 'opacity-25': form.processing }" 
                :disabled="form.processing"
                color="gold"
            >
                <span v-if="!form.processing">Finalizar Cadastro</span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processando...
                </span>
            </PrimaryButton>
        </template>
    </FormSection>
</template>

<style scoped>
/* Estilo para a área de drop quando arrastando arquivos */
.border-dashed.border-gray-300:hover {
    background-color: rgba(190, 165, 90, 0.05);
    border-color: #bea55a;
}

/* Animação de fade para mensagens de feedback */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.mb-4 {
    animation: fadeIn 0.3s ease-out;
}

/* Estilos responsivos adicionais para telas pequenas */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .h-24 {
        height: 6rem;
    }
}

/* Estilos para acessibilidade focus */
button:focus, input:focus {
    outline: 2px solid #bea55a;
    outline-offset: 2px;
}

/* Melhoria nas visualizações de imagem */
img {
    cursor: pointer;
    transition: transform 0.2s ease;
}

img:hover {
    transform: scale(1.05);
}
</style>