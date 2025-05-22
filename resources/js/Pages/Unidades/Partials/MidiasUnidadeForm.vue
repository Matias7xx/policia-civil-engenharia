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
    isEditable: {
        type: Boolean,
        default: true
    },
    isNew: {
        type: Boolean,
        default: true
    }
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
const midiasToRemove = ref([]);
const midiasToReplace = ref({});

const formattedMessage = computed(() => {
  return mensagemFeedback.value.replace(/\(e mais \d+ erro[s]?\)/g, '').trim();
});

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
    midia_replace: {},
    midia_remover: []
});

const handleFileChange = (midiaTipoId, event) => {
    // Pega apenas o primeiro arquivo selecionado
    const file = event.target.files[0];
    if (!file) return;
    
    // Armazena como array com um único item (para manter compatibilidade com o restante do código)
    form.midia_files[midiaTipoId] = [file];
    form.midia_tipos[midiaTipoId] = midiaTipoId;
    
    // Se não estiver no modo de criação, marca para substituição
    if (!props.isNew) {
        form.midia_replace[midiaTipoId] = true;
        midiasToReplace.value[midiaTipoId] = true;
    }
    
    // Limpa previews anteriores para este tipo
    if (previewImages.value[midiaTipoId]) {
        previewImages.value[midiaTipoId] = [];
    }
    
    // Cria preview para a imagem
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
            delete form.midia_replace[midiaTipoId];
            delete midiasToReplace.value[midiaTipoId];
        }
    }
};

const removeMidia = (midia) => {
    if (confirm('Tem certeza que deseja remover esta mídia?')) {
        midiasToRemove.value.push(midia.id);
        form.midia_remover.push(midia.id);
    }
};

const saveOrFinalizeMidias = () => {
    if (!props.permissions?.canUpdateTeam || !props.isEditable) {
        mensagemFeedback.value = 'O cadastro não pode ser editado.';
        tipoFeedback.value = 'error';
        emit('saved', mensagemFeedback.value);
        return;
    }

    const requiredTypes = ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                          'foto_medidor_agua', 'foto_medidor_energia'];
    
    // Obter IDs dos tipos obrigatórios
    const requiredTypeIds = midiaTipos.value
        .filter(tipo => requiredTypes.includes(tipo.nome))
        .map(tipo => tipo.id);
    
    // Verifica se há algum tipo obrigatório sem mídia
    const missingRequiredTypes = [];
    
    requiredTypeIds.forEach(tipoId => {
        const tipoName = midiaTipos.value.find(t => t.id === tipoId)?.nome || `Tipo ${tipoId}`;
        
        //Verifica se há mídias existentes deste tipo que NÃO estão marcadas para remoção
        const existingMediaNotRemoved = midiasPorTipo.value[tipoId]?.length > 0;
        
        // Verificar se há novas mídias sendo enviadas para este tipo
        const hasNewMedia = form.midia_files[tipoId]?.length > 0;
        
        // Verificar se as mídias existentes deste tipo estão marcadas para substituição
        const willBeReplaced = midiasToReplace.value[tipoId] === true;
        
        // Se não existem mídias ou elas serão todas removidas/substituídas sem novas adições
        const isMissing = (!existingMediaNotRemoved || willBeReplaced) && !hasNewMedia;
        
        if (isMissing) {
            missingRequiredTypes.push(tipoName.replace('foto_', ''));
        }
    });
    
    // Se houver tipos obrigatórios faltando, exibir mensagem e interromper o envio
    if (missingRequiredTypes.length > 0) {
        const tiposFormatados = missingRequiredTypes.map(tipo => {
             // Formatação especial para medidores e laterais
            if (tipo === 'medidor_agua') return 'Nº do medidor de Água';
            if (tipo === 'medidor_energia') return 'Nº do medidor de Energia';
            if (tipo === 'lateral_1') return 'Lateral Esquerda';
            if (tipo === 'lateral_2') return 'Lateral Direita';

            // Formatar nomes dos tipos para melhor exibição (ex: foto_frente -> Frente)
            return tipo.charAt(0).toUpperCase() + tipo.slice(1).replace(/_/g, ' ');
        });
        
         // Mensagem mais clara sobre o que está faltando
        if (tiposFormatados.length === 1) {
            mensagemFeedback.value = `É necessário incluir uma imagem para: ${tiposFormatados[0]}`;
        } else {
            mensagemFeedback.value = `É necessário incluir imagens para os seguintes campos: ${tiposFormatados.join(', ')}`;
        }
        tipoFeedback.value = 'error';
        emit('saved', mensagemFeedback.value);
        return;
}

    const formData = new FormData();
    formData.append('unidade_id', form.unidade_id);

    // Verificar se há pelo menos um arquivo a ser enviado
if (Object.keys(form.midia_files).length === 0 && midiasToRemove.value.length === 0) {
    mensagemFeedback.value = 'Por favor, inclua pelo menos uma imagem antes de salvar.';
    tipoFeedback.value = 'error';
    emit('saved', mensagemFeedback.value);
    return;
}

    // Adicionar midias para remover
    if (midiasToRemove.value.length > 0) {
        midiasToRemove.value.forEach((id, index) => {
            formData.append(`midia_remover[${index}]`, id);
        });
    }

    let fileIndex = 0;
    Object.entries(form.midia_files).forEach(([midiaTipoId, files]) => {
        if (!files || files.length === 0) return;
        files.forEach(file => {
            formData.append(`files[${fileIndex}]`, file);
            formData.append(`midia_tipos[${fileIndex}]`, midiaTipoId);
            
            if (midiasToReplace.value[midiaTipoId]) {
                formData.append(`midia_replace[${fileIndex}]`, "1"); // Usando "1" para representar true
            }
            
            fileIndex++;
        });
    });

    if (fileIndex === 0 && midiasToRemove.value.length === 0) {
        // Não há arquivos para salvar nem remover
        mensagemFeedback.value = 'Nenhuma alteração nas mídias foi realizada.';
        tipoFeedback.value = 'info';
        emit('saved');
        return;
    }
    
    mensagemFeedback.value = 'Processando alterações nas mídias...';
    tipoFeedback.value = 'info';

    // Usar rota específica para modo de edição se não for novo
    const url = props.isNew ? route('midias.store') : route('midias.update', props.unidade.id);

    axios.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            mensagemFeedback.value = `Processando... ${percentCompleted}%`;
        }
    })
    .then(response => {
        if (!isMounted.value) return;
        if (response.data.success) {
            mensagemFeedback.value = props.isNew ? 'Mídias salvas com sucesso!' : 'Mídias atualizadas com sucesso!';
            tipoFeedback.value = 'success';
            
            // Limpar midias para remover após sucesso
            midiasToRemove.value = [];
            midiasToReplace.value = {};
            
            // Se estiver no modo de criação e for um rascunho, finaliza o cadastro
            if (props.isNew && props.unidade?.is_draft) {
                mensagemFeedback.value = 'Mídias salvas! Finalizando cadastro...';
                
                form.post(route('unidades.finalize', props.unidade.id), {
                    errorBag: 'finalizeUnidade',
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!isMounted.value) return;
                        mensagemFeedback.value = 'Cadastro finalizado com sucesso!';
                        tipoFeedback.value = 'success';
                        emit('saved');
                        
                        // Limpar os previews após o envio
                        previewImages.value = {};
                    },
                    onError: (errors) => {
                        if (!isMounted.value) return;
                        mensagemFeedback.value = 'Erro ao finalizar o cadastro: ' + (Object.values(errors).join(', ') || 'Verifique os dados.');
                        tipoFeedback.value = 'error';
                        emit('saved', mensagemFeedback.value);
                    },
                });
            } else {
                // No modo de edição, apenas notifica sucesso
                emit('saved');
                
                // Limpar os previews após o envio
                previewImages.value = {};
            }
        } else {
            mensagemFeedback.value = response.data.message || 'Erro ao salvar as mídias.';
            tipoFeedback.value = 'error';
            emit('saved', mensagemFeedback.value);
        }
    })
    .catch(error => {
        if (!isMounted.value) return;
        mensagemFeedback.value = error.response?.data?.message || 'Erro ao salvar as mídias. Verifique os arquivos.';
        tipoFeedback.value = 'error';
        emit('saved', mensagemFeedback.value);
    });
};

const formatarNomeTipoMidia = (nome) => {
    // Casos específicos
    if (nome === 'foto_medidor_agua') return 'Número do medidor de Água';
    if (nome === 'foto_medidor_energia') return 'Número do medidor de Energia';
    if (nome === 'foto_lateral_1') return 'Lateral Esquerda';
    if (nome === 'foto_lateral_2') return 'Lateral Direita';
    
    // Formato padrão para outros tipos
    // Remove o prefixo 'foto_' e substitui underscores por espaços
    return nome.replace('foto_', '').replace(/_/g, ' ');
};

const midiasPorTipo = computed(() => {
    const grouped = {};
    
    if (props.midias && Array.isArray(props.midias)) {
        // Objeto para rastrear IDs já adicionados por tipo
        const addedMidiaIds = {};
        
        props.midias.forEach(midia => {
            // Pular mídias marcadas para remoção
            if (midiasToRemove.value.includes(midia.id)) return;
            
            // Determinar o ID do tipo de mídia
            let tipoId = null;
            if (midia.midia_tipo_id) {
                tipoId = midia.midia_tipo_id;
            } else if (midia.midiaTipo && midia.midiaTipo.id) {
                tipoId = midia.midiaTipo.id;
            } else if (midia.tipo && midia.tipo.id) {
                tipoId = midia.tipo.id;
            }
            
            if (tipoId) {
                // Inicializar o array para o tipo se necessário
                if (!grouped[tipoId]) {
                    grouped[tipoId] = [];
                    addedMidiaIds[tipoId] = new Set();
                }
                
                // Verificar se esta mídia já foi adicionada para este tipo
                if (!addedMidiaIds[tipoId].has(midia.id)) {
                    grouped[tipoId].push(midia);
                    addedMidiaIds[tipoId].add(midia.id);
                }
            }
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
        let categoria;
        
        // Categorizar os tipos de mídia
        if (['rampa_acesso', 'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 'sinalizacao_braile'].includes(tipo.nome)) {
            categoria = 'Acessibilidade';
        } else if (tipo.nome.includes('foto_')) {
            categoria = 'Área Externa';
        } else {
            categoria = 'Área Interna';
        }
        
        if (!tiposAgrupados[categoria]) {
            tiposAgrupados[categoria] = [];
        }
        
        tiposAgrupados[categoria].push(tipo);
    });
    
    // Ordenar as categorias para ter uma ordem fixa: Área Externa, Área Interna, Acessibilidade
    const categoriaOrdem = ['Área Externa', 'Área Interna', 'Acessibilidade'];
    
    return Object.entries(tiposAgrupados).sort((a, b) => {
        return categoriaOrdem.indexOf(a[0]) - categoriaOrdem.indexOf(b[0]);
    });
});

// Determina o texto do botão com base no modo (criar/editar)
const buttonText = computed(() => {
    if (!props.isNew) {
        return 'Salvar Alterações';
    }
    return 'Finalizar Cadastro';
});
</script>

<template>
    <FormSection @submitted="saveOrFinalizeMidias">
        <template #title>
            Mídias da Unidade
        </template>

        <template #description>
            <p>
                {{ isNew ? 'Adicione' : 'Gerencie' }} fotos da unidade para cada tipo de mídia. 
                <strong v-if="isNew" class="text-red-600">Os itens com * são obrigatórios.</strong>
            </p>
            <p class="mt-2 text-sm text-gray-600">
                Formatos aceitos: JPG, PNG (máx. 5MB)
            </p>
        </template>

        <template #form>
            <div class="col-span-6">
                <!-- Mensagem de feedback -->
                <div v-if="formattedMessage" 
                     class="mb-4 p-3 rounded-md text-sm"
                     :class="{
                        'bg-green-100 text-green-800': tipoFeedback === 'success',
                        'bg-red-100 text-red-800': tipoFeedback === 'error',
                        'bg-blue-100 text-blue-800': tipoFeedback === 'info'
                     }">
                    {{ formattedMessage }}
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
                                        :value="formatarNomeTipoMidia(midiaTipo.nome)"
                                        class="font-medium text-gray-800 capitalize"
                                    />
                                    <div class="flex items-center">
                                        <span v-if="midiaTipo.isRequired" 
                                            class="text-red-600 text-lg font-bold ml-1"
                                            :title="isNew ? 'Campo obrigatório para novo cadastro' : 'Campo obrigatório'">*</span>
                                        
                                        <!-- Indicador de status do campo -->
                                        <span v-if="midiaTipo.isRequired" 
                                            class="ml-2 text-xs px-2 py-1 rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800': midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length,
                                                'bg-red-100 text-red-800': !midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length
                                            }">
                                            {{ midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length ? 'OK' : 'Vazio' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Área de drop e upload -->
                                <div 
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-gray-50 transition-colors duration-200 cursor-pointer mb-3"
                                    :class="{ 'border-red-300': isNew && midiaTipo.isRequired && !midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length }"
                                    @click="$refs[`fileInput-${midiaTipo.id}`][0].click()"
                                >
                                    <input
                                        :id="`file-${midiaTipo.id}`"
                                        :ref="`fileInput-${midiaTipo.id}`"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        :disabled="!permissions?.canUpdateTeam || !isEditable"
                                        @change="handleFileChange(midiaTipo.id, $event)"
                                    />
                                    
                                    <div class="flex flex-col items-center justify-center py-3">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="text-sm text-gray-600">Clique para selecionar uma foto</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG até 5MB</p>
                                    </div>
                                </div>

                                <InputError :message="form.errors[`files[${midiaTipo.id}]`]" class="mt-2" />
                                
                                <!-- Previews de imagens a serem enviadas -->
                                <div v-if="previewImages[midiaTipo.id]?.length" class="mt-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm font-medium text-gray-700">Novas imagens selecionadas:</p>
                                        <div v-if="!isNew && midiasPorTipo[midiaTipo.id]?.length" class="flex items-center">
                                            <span class="text-xs text-red-500 mr-1">Substituirá as existentes</span>
                                        </div>
                                    </div>
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
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm font-medium text-gray-700">Imagens já cadastradas:</p>
                                        <p v-if="!isNew && previewImages[midiaTipo.id]?.length" class="text-xs text-orange-500">
                                            Será substituída
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <div 
                                            v-for="midia in midiasPorTipo[midiaTipo.id]" 
                                            :key="midia.id"
                                            class="relative group"
                                        >
                                            <img
                                                v-if="midia.is_imagem !== false"
                                                :src="midia.url"
                                                :alt="`${midiaTipo.nome} - ${midia.id}`"
                                                class="h-24 w-full object-cover rounded border border-gray-200"
                                                @click="window.open(midia.url, '_blank')"
                                            />
                                            <!-- Botão para remover mídia existente -->
                                            <button 
                                                v-if="permissions?.canUpdateTeam && isEditable && !isNew"
                                                type="button"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                @click.prevent="removeMidia(midia)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <p class="text-xs text-gray-500 mt-1">{{ midia.tamanho_formatado || getFileSize(midia.tamanho || 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="permissions?.canUpdateTeam && isEditable" #actions>
            <div class="flex items-center">
        <!-- Mensagem de sucesso -->
        <div v-if="tipoFeedback === 'success'" 
             class="mr-4 inline-flex items-center px-3 py-1 rounded-md bg-green-100 text-green-800 text-sm animate-fade-in">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>Alterações salvas com sucesso</span>
        </div>
        
        <!-- Mensagem de erro -->
        <div v-else-if="tipoFeedback === 'error'" 
             class="mr-4 inline-flex items-center px-3 py-1 rounded-md bg-red-100 text-red-800 text-sm animate-fade-in">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>{{ formattedMessage }}</span>
        </div>

            <PrimaryButton 
                :class="{ 'opacity-25': form.processing }" 
                :disabled="form.processing"
                color="gold"
            >
                <span v-if="!form.processing">{{ buttonText }}</span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processando...
                </span>
            </PrimaryButton>
            </div>
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

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>