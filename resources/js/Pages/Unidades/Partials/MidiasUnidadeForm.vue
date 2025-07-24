<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToast } from '@/Composables/useToast';

const toast = useToast();

const BATCH_SIZE = 10; // 10 arquivos (fotos) por lote
const MAX_BATCH_SIZE_MB = 150; // 150MB por lote
const MAX_TOTAL_FILES = 129; // Máximo teórico de fotos (43 campos × 3)

const props = defineProps({
    unidade: Object,
    midias: Array,
    acessibilidade: Object,
    midiaTipos: Array,
    isNew: {
        type: Boolean,
        default: false
    },
    permissions: Object,
    isEditable: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['saved', 'midiasUpdated']);

const isMounted = ref(false);
const isLoading = ref(true);
const midiaTipos = ref([]);
const previewImages = ref({});
const midiasToRemove = ref([]);
const midiasToReplace = ref({});
const ambientesNaoPossui = ref({});

// Reactive para mídias - permitir atualização dinâmica
const midiasAtuais = ref([]);

// Estados para feedback visual
const isUploading = ref(false);
const uploadProgress = ref({
    currentBatch: 0,
    totalBatches: 0,
    currentFile: 0,
    totalFiles: 0,
    currentBatchProgress: 0,
    overallProgress: 0,
    currentFileName: '',
    estimatedTimeLeft: '',
    startTime: null
});

onMounted(async () => {
    isMounted.value = true;
    
    // Inicializar mídias atuais
    midiasAtuais.value = props.midias ? [...props.midias] : [];
    
    try {
        if (props.midiaTipos && props.midiaTipos.length > 0) {
            midiaTipos.value = props.midiaTipos;
        } else {
            const response = await axios.get(`/midia-tipos/ativos`);
            midiaTipos.value = response.data.map(tipo => {
                const isAcessibilidadeAtiva = props.acessibilidade && props.acessibilidade[tipo.nome] === true;
                
                return {
                    id: tipo.id,
                    nome: tipo.nome,
                    descricao: tipo.descricao || 'Sem descrição disponível',
                    isRequired: ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                                'foto_medidor_agua', 'foto_medidor_energia'].includes(tipo.nome),
                    isAreaInterna: !['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                                   'foto_medidor_agua', 'foto_medidor_energia', 'rampa_acesso', 
                                   'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                                   'sinalizacao_braile'].includes(tipo.nome),
                    isAcessibilidade: ['rampa_acesso', 'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                                      'sinalizacao_braile'].includes(tipo.nome),
                    isAcessibilidadeObrigatoria: isAcessibilidadeAtiva,
                    shouldShow: !['rampa_acesso', 'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                                  'sinalizacao_braile'].includes(tipo.nome) || isAcessibilidadeAtiva
                };
            }).filter(tipo => tipo.shouldShow);
        }
        
        midiaTipos.value.forEach(tipo => {
            if (tipo.isAreaInterna) {
                const temRegistroNaoPossui = midiasAtuais.value?.some(midia => {
                    const midiaTipoId = midia.midia_tipo_id || midia.midia_tipo?.id || midia.midiaTipo?.id;
                    const isDummyRecord = midia.path === 'nao_possui_ambiente';
                    const hasPivotFlag = midia.pivot && midia.pivot.nao_possui_ambiente === true;
                    
                    return midiaTipoId === tipo.id && (isDummyRecord || hasPivotFlag);
                }) || false;
                
                ambientesNaoPossui.value[tipo.id] = temRegistroNaoPossui;
                
                if (temRegistroNaoPossui) {
                    form.ambientes_nao_possui[tipo.id] = true;
                }
            }
        });
        
        isLoading.value = false;
    } catch (error) {
        console.error('Erro ao carregar tipos de mídia:', error);
        isLoading.value = false;
        toast.error('Erro ao carregar tipos de mídia. Tente recarregar a página.');
    }
});

// Watch para atualizar midiasAtuais quando props.midias muda
watch(() => props.midias, (newMidias) => {
    if (newMidias) {
        midiasAtuais.value = [...newMidias];
    }
}, { deep: true });

onUnmounted(() => {
    isMounted.value = false;
});

watch([() => midiasAtuais.value, () => midiaTipos.value], () => {
    if (midiasAtuais.value && midiaTipos.value.length > 0) {
        midiaTipos.value.forEach(tipo => {
            if (tipo.isAreaInterna) {
                const temRegistroNaoPossui = midiasAtuais.value.some(midia => {
                    const midiaTipoId = midia.midia_tipo_id || midia.midia_tipo?.id || midia.midiaTipo?.id;
                    const isDummyRecord = midia.path === 'nao_possui_ambiente';
                    const hasPivotFlag = midia.pivot && midia.pivot.nao_possui_ambiente === true;
                    
                    return midiaTipoId === tipo.id && (isDummyRecord || hasPivotFlag);
                }) || false;
                
                ambientesNaoPossui.value[tipo.id] = temRegistroNaoPossui;
                if (temRegistroNaoPossui) {
                    form.ambientes_nao_possui[tipo.id] = true;
                }
            }
        });
    }
}, { immediate: true, deep: true });

const form = useForm({
    unidade_id: props.unidade?.id || '',
    midia_files: {},
    midia_tipos: {},
    midia_replace: {},
    midia_remover: [],
    ambientes_nao_possui: {}
});

// Função para buscar mídias atualizadas do servidor
const refreshMidias = async () => {
    try {
        const response = await axios.get(`/unidades/${props.unidade.id}/midias`);
        if (response.data && response.data.midias) {
            midiasAtuais.value = response.data.midias;
            // Emitir evento para o componente pai atualizar
            emit('midiasUpdated', response.data.midias);
        }
    } catch (error) {
        console.error('Erro ao buscar mídias atualizadas:', error);
    }
};

// Funções para upload em lotes
const calculateBatchSize = (files) => {
    // Primeiro, agrupar por tipo para manter fotos do mesmo tipo juntas
    const filesByType = {};
    files.forEach(file => {
        if (!filesByType[file.midiaTipoId]) {
            filesByType[file.midiaTipoId] = [];
        }
        filesByType[file.midiaTipoId].push(file);
    });
    
    const batches = [];
    let currentBatch = [];
    let currentSizeMB = 0;
    
    // Processar cada tipo completo por vez
    Object.values(filesByType).forEach(typeFiles => {
        typeFiles.forEach(file => {
            const fileSizeMB = file.file.size / (1024 * 1024);
            
            // Se exceder limites do lote, criar novo lote
            if (currentBatch.length >= BATCH_SIZE || 
                (currentSizeMB + fileSizeMB) > MAX_BATCH_SIZE_MB) {
                if (currentBatch.length > 0) {
                    batches.push(currentBatch);
                    currentBatch = [];
                    currentSizeMB = 0;
                }
            }
            
            currentBatch.push(file);
            currentSizeMB += fileSizeMB;
        });
    });
    
    // Adicionar último lote se não estiver vazio
    if (currentBatch.length > 0) {
        batches.push(currentBatch);
    }
    
    return batches;
};

const uploadInBatches = async (allFiles) => {
    const batches = calculateBatchSize(allFiles);
    let uploadedCount = 0;
    
    // Inicializar estado de upload
    isUploading.value = true;
    uploadProgress.value = {
        currentBatch: 0,
        totalBatches: batches.length,
        currentFile: 0,
        totalFiles: allFiles.length,
        currentBatchProgress: 0,
        overallProgress: 0,
        currentFileName: '',
        estimatedTimeLeft: 'Calculando...',
        startTime: Date.now()
    };
    
    toast.info(`Iniciando upload de ${allFiles.length} imagens em ${batches.length} lote(s)...`, { duration: 2000 });
    
    try {
        for (let i = 0; i < batches.length; i++) {
            const batch = batches[i];
            const batchFormData = new FormData();
            
            // Atualizar progresso do lote
            uploadProgress.value.currentBatch = i + 1;
            uploadProgress.value.currentBatchProgress = 0;
            
            // Dados base sempre presentes
            batchFormData.append('unidade_id', form.unidade_id);
            
            // NOVO: Sinalizar que é upload em lotes
            batchFormData.append('is_batch_upload', '1');
            batchFormData.append('total_files_in_batch', allFiles.length.toString());
            
            // Adicionar dados de "não possui ambiente" sempre
            Object.entries(ambientesNaoPossui.value).forEach(([tipoId, naoPossui]) => {
                batchFormData.append(`ambientes_nao_possui[${tipoId}]`, naoPossui ? "1" : "0");
            });
            
            // Adicionar remoções apenas no primeiro lote
            if (i === 0 && midiasToRemove.value.length > 0) {
                midiasToRemove.value.forEach((id, index) => {
                    batchFormData.append(`midia_remover[${index}]`, id);
                });
            }
            
            // Adicionar arquivos do lote atual
            let fileIndex = 0;
            batch.forEach(fileData => {
                batchFormData.append(`files[${fileIndex}]`, fileData.file);
                batchFormData.append(`midia_tipos[${fileIndex}]`, fileData.midiaTipoId);
                
                if (midiasToReplace.value[fileData.midiaTipoId]) {
                    batchFormData.append(`midia_replace[${fileIndex}]`, "1");
                }
                
                fileIndex++;
            });
            
            // Enviar lote com timeout aumentado
            const response = await axios.post(
                props.isNew ? route('midias.store') : route('midias.update', props.unidade.id),
                batchFormData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    timeout: 300000, // 5 minutos de timeout
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        uploadProgress.value.currentBatchProgress = percentCompleted;
                        
                        // Calcular progresso geral
                        const batchesCompleted = i;
                        const currentBatchContribution = (percentCompleted / 100) * (batch.length / allFiles.length);
                        const completedBatchesContribution = (uploadedCount / allFiles.length);
                        uploadProgress.value.overallProgress = Math.round((completedBatchesContribution + currentBatchContribution) * 100);
                        
                        // Calcular tempo estimado
                        const elapsed = Date.now() - uploadProgress.value.startTime;
                        const rate = uploadProgress.value.overallProgress / elapsed;
                        const remaining = (100 - uploadProgress.value.overallProgress) / rate;
                        
                        if (remaining > 0 && isFinite(remaining)) {
                            const minutes = Math.floor(remaining / 60000);
                            const seconds = Math.floor((remaining % 60000) / 1000);
                            uploadProgress.value.estimatedTimeLeft = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
                        }
                        
                        console.log(`Lote ${i + 1}/${batches.length}: ${percentCompleted}% | Geral: ${uploadProgress.value.overallProgress}%`);
                    }
                }
            );
            
            if (response.status === 200 || response.status === 201) {
                uploadedCount += batch.length;
                uploadProgress.value.currentFile = uploadedCount;
                
                // Aguardar um pouco entre lotes
                if (i < batches.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            } else {
                throw new Error(`Erro no lote ${i + 1}: ${response.data?.message || 'Erro desconhecido'}`);
            }
        }
        
        // ÚLTIMO LOTE: Verificar se precisa finalizar (apenas se é rascunho)
        if (props.unidade?.is_draft === true) {
            console.log('Finalizando cadastro após upload em lotes...');
            await finalizarCadastro();
            return;
        }
        
        // Finalizar upload
        isUploading.value = false;
        uploadProgress.value.overallProgress = 100;
        
        toast.success(`Sucesso! Todas as ${allFiles.length} imagens foram enviadas.`);
        
        // Atualizar mídias e limpar formulário
        await refreshMidias();
        clearForm();
        emit('saved', `${uploadedCount} imagens enviadas com sucesso!`);
        
    } catch (error) {
        isUploading.value = false;
        
        console.error('Erro no upload em lotes:', error);
        
        let errorMsg = 'Erro ao enviar imagens.';
        
        if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
            errorMsg = 'Timeout: O envio demorou muito. Tente com menos imagens ou verifique sua conexão.';
        } else if (error.response?.status === 413) {
            errorMsg = 'Erro 413: Arquivos muito grandes. Tente reduzir o tamanho das imagens ou envie menos por vez.';
        } else if (error.response?.status === 422) {
            // Erros de validação
            const errors = error.response.data.errors;
            if (errors) {
                toast.validationErrors(errors, { showAll: false });
                return;
            }
            errorMsg = error.response.data.message || 'Erro de validação.';
        } else if (error.response?.data?.message) {
            errorMsg = error.response.data.message;
        }
        
        toast.error(errorMsg);
        emit('saved', errorMsg);
    }
};

// Finalizar cadastro após upload em lotes
const finalizarCadastro = async () => {
    try {
        // FormData
        const formData = new FormData();
        formData.append('unidade_id', form.unidade_id);
        formData.append('is_batch_upload', '0');
        
        // Adicionar dados de "não possui ambiente" sempre
        Object.entries(ambientesNaoPossui.value).forEach(([tipoId, naoPossui]) => {
            formData.append(`ambientes_nao_possui[${tipoId}]`, naoPossui ? "1" : "0");
        });
        
        const response = await axios.post(
            route('midias.store'),
            formData,
            {
                headers: { 
                    'Content-Type': 'multipart/form-data'
                },
                timeout: 30000
            }
        );
        
        if (response.data.redirect) {
            toast.clear();
            isUploading.value = false;
            toast.success('Cadastro finalizado com sucesso! Redirecionando...');
            
            setTimeout(() => {
                window.location.href = response.data.redirect;
            }, 1500);
            return;
        }
        
        isUploading.value = false;
        toast.success('Upload e finalização concluídos com sucesso!');
        await refreshMidias();
        clearForm();
        emit('saved', 'Cadastro finalizado com sucesso!');
        
    } catch (error) {
        isUploading.value = false;
        console.error('Erro na finalização:', error);
        
        let errorMsg = 'Erro ao finalizar cadastro.';
        if (error.response?.data?.message) {
            errorMsg = error.response.data.message;
        }
        
        toast.error(errorMsg);
        emit('saved', errorMsg);
    }
};

const clearForm = () => {
    form.midia_files = {};
    form.midia_tipos = {};
    form.midia_replace = {};
    form.midia_remover = [];
    midiasToRemove.value = [];
    midiasToReplace.value = {};
    previewImages.value = {};
    
    midiaTipos.value.forEach(tipo => {
        const input = document.getElementById(`file-${tipo.id}`);
        if (input) {
            input.value = '';
        }
    });
};

const validateQuantityPerType = () => {
    const errors = [];
    
    midiaTipos.value.forEach(tipo => {
        const existingCount = midiasPorTipo.value[tipo.id]?.length || 0;
        const newFilesCount = form.midia_files[tipo.id]?.length || 0;
        const willBeReplaced = midiasToReplace.value[tipo.id] === true;
        const removedCount = midiasToRemove.value.filter(removedId => {
            return midiasPorTipo.value[tipo.id]?.some(midia => midia.id === removedId);
        }).length;
        
        let finalCount = existingCount - removedCount + newFilesCount;
        if (willBeReplaced) {
            finalCount = newFilesCount;
        }
        
        // Pular validação se marcou "não possui ambiente"
        if (tipo.isAreaInterna && ambientesNaoPossui.value[tipo.id]) {
            return;
        }
        
        //Só validar se realmente há fotos (novas ou existentes)
        if (finalCount > 0) {
            if (finalCount < 2) {
                errors.push(`${formatarNomeTipoMidia(tipo.nome)}: mínimo de 2 fotos (atual: ${finalCount})`);
            }
            if (finalCount > 3) {
                errors.push(`${formatarNomeTipoMidia(tipo.nome)}: máximo de 3 fotos (atual: ${finalCount})`);
            }
        }
    });
    
    return errors;
};

const getPhotoCount = (midiaTipoId) => {
    const existingCount = midiasPorTipo.value[midiaTipoId]?.filter(midia => 
        !midiasToRemove.value.includes(midia.id)
    ).length || 0;
    const newFilesCount = form.midia_files[midiaTipoId]?.length || 0;
    const willBeReplaced = midiasToReplace.value[midiaTipoId] === true;
    
    if (willBeReplaced) {
        return newFilesCount;
    }
    return existingCount + newFilesCount;
};

const canAddMorePhotos = (midiaTipoId) => {
    return getPhotoCount(midiaTipoId) < 3;
};

const openFileInput = (midiaTipoId) => {
    if (!ambientesNaoPossui.value[midiaTipoId] && 
        canAddMorePhotos(midiaTipoId) && 
        props.permissions?.canUpdateTeam && 
        props.isEditable) {
        const input = document.getElementById(`file-${midiaTipoId}`);
        if (input) {
            input.click();
        }
    }
};

// Função para adicionar múltiplos arquivos (não sobrescrever)
const handleFileChange = (midiaTipoId, event) => {
    const files = Array.from(event.target.files);

    if (!files.length) return;

    const currentCount = getPhotoCount(midiaTipoId);
    const newCount = files.length;
    const totalCount = currentCount + newCount;

    if (totalCount > 3) {
        alert(`Máximo de 3 fotos permitidas por tipo de mídia. Você pode adicionar mais ${3 - currentCount} foto(s).`);
        event.target.value = '';
        return;
    }

    const tipo = midiaTipos.value.find(t => t.id === midiaTipoId);
    if (tipo?.isAreaInterna) {
        ambientesNaoPossui.value[midiaTipoId] = false;
        form.ambientes_nao_possui[midiaTipoId] = false;
    }
    
    // Inicializar array se não existir
    if (!form.midia_files[midiaTipoId]) {
        form.midia_files[midiaTipoId] = [];
    }
    
    // ADICIONAR aos arquivos existentes, não substituir
    form.midia_files[midiaTipoId] = [...form.midia_files[midiaTipoId], ...files];
    form.midia_tipos[midiaTipoId] = midiaTipoId;
        
    if (!props.isNew && !midiasPorTipo.value[midiaTipoId]?.length) {
        form.midia_replace[midiaTipoId] = true;
        midiasToReplace.value[midiaTipoId] = true;
    }
    
    //ADICIONAR aos previews existentes, não substituir
    if (!previewImages.value[midiaTipoId]) {
        previewImages.value[midiaTipoId] = [];
    }
    
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImages.value[midiaTipoId].push(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Limpar o input para permitir selecionar os mesmos arquivos novamente se necessário
    event.target.value = '';
};

const handleNaoPossuiChange = (midiaTipoId, checked) => {
    ambientesNaoPossui.value[midiaTipoId] = checked;
    form.ambientes_nao_possui[midiaTipoId] = checked;
    
    if (checked) {
        removeAllFilesForType(midiaTipoId);
    }
};

const removeAllFilesForType = (midiaTipoId) => {
    if (previewImages.value[midiaTipoId]) {
        previewImages.value[midiaTipoId] = [];
    }
    
    if (form.midia_files[midiaTipoId]) {
        delete form.midia_files[midiaTipoId];
        delete form.midia_tipos[midiaTipoId];
        delete form.midia_replace[midiaTipoId];
        delete midiasToReplace.value[midiaTipoId];
    }
    
    const input = document.getElementById(`file-${midiaTipoId}`);
    if (input) {
        input.value = '';
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

const isAreaInternaCompleta = (midiaTipoId) => {
    const hasFile = form.midia_files[midiaTipoId]?.length > 0;
    const hasExistingMedia = midiasPorTipo.value[midiaTipoId]?.length > 0;
    const marcouNaoPossui = ambientesNaoPossui.value[midiaTipoId];
    
    return hasFile || hasExistingMedia || marcouNaoPossui;
};

const saveOrFinalizeMidias = () => {
    if (!props.permissions?.canUpdateTeam || !props.isEditable) {
        toast.error('O cadastro não pode ser editado.');
        emit('saved');
        return;
    }

    const quantityErrors = validateQuantityPerType();
    if (quantityErrors.length > 0) {
        const errorMsg = `Erro na quantidade de fotos: ${quantityErrors.join(', ')}.`;
        toast.error(errorMsg);
        emit('saved', errorMsg);
        return;
    }

    const requiredTypes = ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                          'foto_medidor_agua', 'foto_medidor_energia'];
    
    const requiredTypeIds = midiaTipos.value
        .filter(tipo => requiredTypes.includes(tipo.nome))
        .map(tipo => tipo.id);
    
    const missingRequiredTypes = [];
    
    requiredTypeIds.forEach(tipoId => {
        const tipoName = midiaTipos.value.find(t => t.id === tipoId)?.nome || `Tipo ${tipoId}`;
        
        const existingMediaNotRemoved = midiasPorTipo.value[tipoId]?.length > 0;
        const hasNewMedia = form.midia_files[tipoId]?.length > 0;
        const willBeReplaced = midiasToReplace.value[tipoId] === true;
        
        const isMissing = (!existingMediaNotRemoved || willBeReplaced) && !hasNewMedia;
        
        if (isMissing) {
            missingRequiredTypes.push(formatarNomeTipoMidia(tipoName));
        }
    });

    const acessibilidadeObrigatoriaIds = midiaTipos.value
        .filter(tipo => tipo.isAcessibilidadeObrigatoria)
        .map(tipo => tipo.id);
    
    const missingAcessibilidadeTypes = [];
    
    acessibilidadeObrigatoriaIds.forEach(tipoId => {
        const tipoName = midiaTipos.value.find(t => t.id === tipoId)?.nome || `Tipo ${tipoId}`;
        
        const existingMediaNotRemoved = midiasPorTipo.value[tipoId]?.length > 0;
        const hasNewMedia = form.midia_files[tipoId]?.length > 0;
        const willBeReplaced = midiasToReplace.value[tipoId] === true;
        
        const isMissing = (!existingMediaNotRemoved || willBeReplaced) && !hasNewMedia;
        
        if (isMissing) {
            missingAcessibilidadeTypes.push(formatarNomeTipoMidia(tipoName));
        }
    });

    const missingAreaInternaTypes = [];
    
    midiaTipos.value
        .filter(tipo => tipo.isAreaInterna)
        .forEach(tipo => {
            if (!isAreaInternaCompleta(tipo.id)) {
                missingAreaInternaTypes.push(formatarNomeTipoMidia(tipo.nome));
            }
        });
    
    // Preparar lista de todos os arquivos
    const allFiles = [];
    Object.entries(form.midia_files).forEach(([midiaTipoId, files]) => {
        if (!files || files.length === 0) return;
        
        files.forEach(file => {
            allFiles.push({
                file: file,
                midiaTipoId: parseInt(midiaTipoId)
            });
        });
    });

    const willUseBatches = allFiles.length > 8;
    const isFinalizing = props.unidade?.is_draft === true;
    
    // Validação relaxada para uploads em lotes
    // Só exigir fotos obrigatórias se for finalização ou upload pequeno
    if (!willUseBatches || isFinalizing) {
        if (missingRequiredTypes.length > 0 || missingAcessibilidadeTypes.length > 0 || missingAreaInternaTypes.length > 0) {
            let errorMessage = '';
            
            if (missingRequiredTypes.length > 0) {
                errorMessage += `É obrigatório incluir fotos para: ${missingRequiredTypes.join(', ')}.`;
            }
            
            if (missingAcessibilidadeTypes.length > 0) {
                if (errorMessage) errorMessage += ' ';
                errorMessage += `É obrigatório incluir fotos dos recursos de acessibilidade: ${missingAcessibilidadeTypes.join(', ')}.`;
            }
            
            if (missingAreaInternaTypes.length > 0) {
                if (errorMessage) errorMessage += ' ';
                errorMessage += `Para área interna, é necessário incluir uma foto OU marcar "Não possui" em: ${missingAreaInternaTypes.join(', ')}.`;
            }
            
            toast.error(errorMessage);
            emit('saved', errorMessage);
            return;
        }
    }

    const hasFiles = allFiles.length > 0;
    const hasRemovals = midiasToRemove.value.length > 0;
    const hasNaoPossuiChanges = Object.values(ambientesNaoPossui.value).some(v => v);
    
    if (!hasFiles && !hasRemovals && !hasNaoPossuiChanges) {
        const errorMsg = 'Por favor, faça pelo menos uma alteração antes de salvar.';
        toast.warning(errorMsg);
        emit('saved', errorMsg);
        return;
    }

    // Verificar se há muitos arquivos
    if (allFiles.length > MAX_TOTAL_FILES) {
        toast.warning(`Você selecionou ${allFiles.length} imagens. O sistema suporta até ${MAX_TOTAL_FILES} fotos (43 campos × 3 fotos cada).\n\nSe necessário, você pode enviar em etapas: primeiro Área Externa, depois Área Interna, depois Acessibilidade.`);
        return;
    }

    // Calcular tamanho total estimado
    const totalSizeMB = allFiles.reduce((sum, file) => sum + (file.file.size / (1024 * 1024)), 0);
    const maxTotalSizeMB = MAX_TOTAL_FILES * 10; // 10MB por foto × 129 = 1.3GB
    
    if (totalSizeMB > maxTotalSizeMB) {
        toast.warning(`O tamanho total das imagens (${Math.round(totalSizeMB)}MB) excede o limite recomendado de ${maxTotalSizeMB}MB.\n\nTente comprimir as imagens ou enviar em etapas menores.`);
        return;
    }

    // Decisão: usar upload em lotes ou processo normal
    if (willUseBatches) {
        // Upload em lotes para 9+ arquivos
        uploadInBatches(allFiles);
        return;
    }

    // Para 8 ou menos arquivos, usar o processo normal otimizado
    const formData = new FormData();
    formData.append('unidade_id', form.unidade_id);
    
    // Sinalizar que NÃO é upload em lotes
    formData.append('is_batch_upload', '0');

    // Inicializar upload para processo normal
    isUploading.value = true;
    uploadProgress.value = {
        currentBatch: 1,
        totalBatches: 1,
        currentFile: 0,
        totalFiles: allFiles.length,
        currentBatchProgress: 0,
        overallProgress: 0,
        currentFileName: '',
        estimatedTimeLeft: 'Calculando...',
        startTime: Date.now()
    };

    // Dados de "não possui ambiente"
    Object.entries(ambientesNaoPossui.value).forEach(([tipoId, naoPossui]) => {
        formData.append(`ambientes_nao_possui[${tipoId}]`, naoPossui ? "1" : "0");
    });

    // Remoções
    if (midiasToRemove.value.length > 0) {
        midiasToRemove.value.forEach((id, index) => {
            formData.append(`midia_remover[${index}]`, id);
        });
    }

    // Arquivos
    let fileIndex = 0;
    Object.entries(form.midia_files).forEach(([midiaTipoId, files]) => {
        if (!files || files.length === 0) return;
                
        files.forEach(file => {
            formData.append(`files[${fileIndex}]`, file);
            formData.append(`midia_tipos[${fileIndex}]`, midiaTipoId);
            
            if (midiasToReplace.value[midiaTipoId]) {
                formData.append(`midia_replace[${fileIndex}]`, "1");
            }
            
            fileIndex++;
        });
    });

    const url = props.isNew ? 
        route('midias.store') : 
        route('midias.update', props.unidade.id );

    axios.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        timeout: 120000, // 2 minutos para uploads menores
        onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            uploadProgress.value.currentBatchProgress = percentCompleted;
            uploadProgress.value.overallProgress = percentCompleted;
            
            // Calcular tempo estimado
            const elapsed = Date.now() - uploadProgress.value.startTime;
            const rate = percentCompleted / elapsed;
            const remaining = (100 - percentCompleted) / rate;
            
            if (remaining > 0 && isFinite(remaining)) {
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                uploadProgress.value.estimatedTimeLeft = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            }
        }
    })
    .then(async response => {
        if (!isMounted.value) return;

        isUploading.value = false;
        
        if (response.status === 200 || response.status === 201) {
            const successMsg = response.data.message || 'Mídias salvas com sucesso!';
            
            // Verificar se há redirecionamento (finalização)
            if (response.data.redirect) {
                toast.success('Cadastro finalizado com sucesso! Redirecionando...');
                
                // Aguardar um pouco antes de redirecionar
                setTimeout(() => {
                    window.location.href = response.data.redirect;
                }, 1500);
                return;
            }
            
            toast.success(successMsg);
            
            await refreshMidias();
            clearForm();
            emit('saved', successMsg);

        } else {
            const errorMsg = response.data.message || 'Erro inesperado ao salvar as mídias.';
            toast.error(errorMsg);
            emit('saved', errorMsg);
        }
    })
    .catch(error => {
        if (!isMounted.value) return;
        
        isUploading.value = false;
        
        let errorMsg = 'Erro ao salvar as mídias.';
        
        if (error.response?.status === 422 && error.response?.data?.errors) {
            toast.validationErrors(error.response.data.errors, { showAll: false });
            return;
        } else if (error.response?.data?.message) {
            errorMsg = error.response.data.message;
        }
        
        toast.error(errorMsg);
        emit('saved', errorMsg);
    });
};

const formatarNomeTipoMidia = (nome) => {
    if (nome === 'foto_frente') return 'Frente';
    if (nome === 'foto_lateral_1') return 'Lateral esquerda';
    if (nome === 'foto_lateral_2') return 'Lateral direita';
    if (nome === 'foto_fundos') return 'Fundos';
    if (nome === 'foto_medidor_agua') return 'Nº medidor de água';
    if (nome === 'foto_medidor_energia') return 'Nº medidor de energia';
    
    if (nome === 'recepção') return 'Recepção';
    if (nome === 'sala_oitiva') return 'Sala de oitiva';
    if (nome === 'sala_boletim_de_ocorrência') return 'Sala de BO';
    if (nome === 'gabinete_01') return 'Gabinete 01';
    if (nome === 'gabinete_02') return 'Gabinete 02';
    if (nome === 'cartório_01') return 'Cartório 01';
    if (nome === 'cartório_02') return 'Cartório 02';
    if (nome === 'sala_de_agentes') return 'Sala de agentes';
    if (nome === 'wc_público_masculino') return 'WC público masculino';
    if (nome === 'wc_público_feminino') return 'WC público feminino';
    if (nome === 'wc_servidores_masculino') return 'WC servidores masculino';
    if (nome === 'wc_servidores_feminino') return 'WC servidores feminino';
    if (nome === 'alojamento_masculino') return 'Alojamento masculino';
    if (nome === 'alojamento_feminino') return 'Alojamento feminino';
    if (nome === 'xadrez_masculino_01') return 'Xadrez masculino 01';
    if (nome === 'xadrez_masculino_02') return 'Xadrez masculino 02';
    if (nome === 'xadrez_masculino_03') return 'Xadrez masculino 03';
    if (nome === 'xadrez_feminino_01') return 'Xadrez feminino 01';
    if (nome === 'xadrez_feminino_02') return 'Xadrez feminino 02';
    if (nome === 'xadrez_feminino_03') return 'Xadrez feminino 03';
    if (nome === 'parlatório') return 'Parlatório';
    if (nome === 'sala_identificação') return 'Sala de identificação';
    if (nome === 'área_de_serviço') return 'Área de serviço';
    if (nome === 'cozinha') return 'Copa/Cozinha';
    if (nome === 'garagem') return 'Garagem';
    if (nome === 'dispensa') return 'Dispensa';
    if (nome === 'depósito_apreensão') return 'Depósito de apreensão';
    if (nome === 'porta_principal') return 'Porta principal';
    if (nome === 'luminarias_emergencia') return 'Luminárias emergência';
    if (nome === 'escada_acesso') return 'Escada de acesso';
    if (nome === 'demarcacao_extintor') return 'Demarcação do extintor';
    if (nome === 'rampa_acesso') return 'Rampa de acesso';
    if (nome === 'corrimao') return 'Corrimão';
    if (nome === 'piso_tatil') return 'Piso tátil';
    if (nome === 'banheiro_adaptado') return 'Banheiro adaptado';
    if (nome === 'elevador') return 'Elevador';
    if (nome === 'sinalizacao_braile') return 'Sinalização em Braile';
    
    return nome.replace('foto_', '').replace(/_/g, ' ');
};

// Usar midiasAtuais.value
const midiasPorTipo = computed(() => {
    const grouped = {};
    
    if (midiasAtuais.value && Array.isArray(midiasAtuais.value)) {
        const addedMidiaIds = {};
        
        midiasAtuais.value.forEach(midia => {
            if (midiasToRemove.value.includes(midia.id)) return;
            
            if (midia.path === 'nao_possui_ambiente') return;
            if (midia.pivot && midia.pivot.nao_possui_ambiente === true) return;
            
            let tipoId = null;
            if (midia.midia_tipo_id) {
                tipoId = midia.midia_tipo_id;
            } else if (midia.midiaTipo && midia.midiaTipo.id) {
                tipoId = midia.midiaTipo.id;
            } else if (midia.tipo && midia.tipo.id) {
                tipoId = midia.tipo.id;
            }
            
            if (tipoId) {
                if (!grouped[tipoId]) {
                    grouped[tipoId] = [];
                    addedMidiaIds[tipoId] = new Set();
                }
                
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
    if (!size || size === 0) return '0 bytes';
    
    const units = ['bytes', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let fileSize = size;
    
    while (fileSize >= 1024 && unitIndex < units.length - 1) {
        fileSize /= 1024;
        unitIndex++;
    }
    
    // Para bytes, não mostrar decimais
    if (unitIndex === 0) {
        return `${Math.round(fileSize)} ${units[unitIndex]}`;
    }
    
    // Para KB, MB, GB - mostrar 1 decimal apenas se necessário
    const rounded = Math.round(fileSize * 10) / 10;
    return `${rounded % 1 === 0 ? Math.round(rounded) : rounded.toFixed(1)} ${units[unitIndex]}`;
};

const getTabelas = computed(() => {
    const tiposAgrupados = {};
    
    midiaTipos.value.forEach(tipo => {
        let categoria;
        
        if (tipo.isAcessibilidade) {
            categoria = 'Acessibilidade';
        } else if (tipo.nome.includes('foto_') && !tipo.isAreaInterna) {
            categoria = 'Área Externa';
        } else {
            categoria = 'Área Interna';
        }
        
        if (!tiposAgrupados[categoria]) {
            tiposAgrupados[categoria] = [];
        }
        
        tiposAgrupados[categoria].push(tipo);
    });
    
    const categoriaOrdem = ['Área Externa', 'Área Interna', 'Acessibilidade'];
    
    return Object.entries(tiposAgrupados).sort((a, b) => {
        return categoriaOrdem.indexOf(a[0]) - categoriaOrdem.indexOf(b[0]);
    });
});
</script>


<template>
    <FormSection @submitted="saveOrFinalizeMidias">
        <template #title>
            Fotos da Unidade
        </template>

        <template #description>
            <p class="mt-2 text-sm text-gray-600">
                Formatos aceitos: JPG, PNG (máx. 10MB por foto) - <strong class="text-blue-600">No mínimo 2 e no máximo 3 fotos por tipo</strong>
            </p>
            <p class="mt-2 text-sm text-blue-600">
                <strong>Para Área Interna:</strong> Inclua fotos OU marque "Não possui" se a unidade não tem esse ambiente.
            </p>
            <p class="mt-1 text-sm text-blue-600">
                <strong>Para Acessibilidade:</strong> É obrigatório incluir fotos dos recursos marcados no formulário de acessibilidade.
            </p>
        </template>

        <template #form>
            <div class="col-span-6">
                
                <div v-if="isLoading" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-gray-900"></div>
                    <span class="ml-2">Carregando tipos de mídia...</span>
                </div>
                
                <div v-else-if="midiaTipos.length === 0" class="mt-2">
                    <p class="text-red-600">Nenhum tipo de mídia disponível ou erro ao carregar.</p>
                </div>
                
                <div v-else>
                    <div v-for="(grupo, index) in getTabelas" :key="index" :id="`section-${index}`" class="mb-10">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3"
                                     :class="{
                                        'bg-indigo-100 text-indigo-600': grupo[0] === 'Área Externa',
                                        'bg-green-100 text-green-600': grupo[0] === 'Área Interna', 
                                        'bg-blue-100 text-blue-600': grupo[0] === 'Acessibilidade'
                                     }">
                                    <svg v-if="grupo[0] === 'Área Externa'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <svg v-else-if="grupo[0] === 'Área Interna'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21l4-4 4 4" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C13.1 2 14 2.9 14 4S13.1 6 12 6 10 5.1 10 4 10.9 2 12 2M21 9V7L15 7.5V9M15 9.5L13.5 7H10V8.5H11.5L13 11L11.5 12.5L10.5 11.5L9.5 12.5L12 15L15 12L13.5 10.5L15 9.5M7.5 22C5.6 22 4 20.4 4 18.5S5.6 15 7.5 15 11 16.6 11 18.5 9.4 22 7.5 22M7.5 17C6.7 17 6 17.7 6 18.5S6.7 20 7.5 20 9 19.3 9 18.5 8.3 17 7.5 17M18.5 22C16.6 22 15 20.4 15 18.5S16.6 15 18.5 15 22 16.6 22 18.5 20.4 22 18.5 22M18.5 17C17.7 17 17 17.7 17 18.5S17.7 20 18.5 20 20 19.3 20 18.5 19.3 17 18.5 17Z"/>
                                    </svg>
                                </div>
                                {{ grupo[0] }}
                                <span v-if="grupo[0] === 'Área Externa'" class="text-red-500 ml-2 text-lg">*</span>
                            </h3>
                            
                            <div class="text-sm text-gray-500 font-medium">
                                {{ grupo[1].length }} {{ grupo[1].length === 1 ? 'tipo' : 'tipos' }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3 gap-5">
                            <div v-for="midiaTipo in grupo[1]" :key="midiaTipo.id" class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 min-h-[100px]">
                                    <div class="flex items-start justify-between h-full">
                                        <div class="flex-1 min-w-0 mr-3 flex flex-col justify-between h-full">
                                            <!-- Área do título com altura fixa -->
                                            <div class="flex-1 flex items-start">
                                                <h4 class="text-sm font-semibold text-gray-900 flex items-start leading-snug">
                                                    <span class="break-words max-w-full">
                                                        {{ formatarNomeTipoMidia(midiaTipo.nome) }}
                                                    </span>
                                                    <span v-if="midiaTipo.nome.includes('foto_') && !midiaTipo.isAreaInterna" class="text-red-500 ml-1 font-bold flex-shrink-0">*</span>
                                                    <span v-if="midiaTipo.isAcessibilidadeObrigatoria" class="text-red-500 ml-1 font-bold flex-shrink-0">*</span>
                                                </h4>
                                            </div>
                                            
                                            <!-- Área dos controles sempre no bottom -->
                                            <div class="flex items-center gap-3 mt-auto pt-3">
                                                <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-full whitespace-nowrap flex items-center">
                                                    {{ getPhotoCount(midiaTipo.id) }}/3 fotos
                                                    <!-- Indicador visual integrado ao contador -->
                                                    <span v-if="getPhotoCount(midiaTipo.id) < 2 && !ambientesNaoPossui[midiaTipo.id]" 
                                                          class="ml-2 w-2 h-2 bg-red-500 rounded-full animate-pulse"
                                                          title="Faltam fotos (mínimo 2)">
                                                    </span>
                                                    <span v-else-if="getPhotoCount(midiaTipo.id) >= 2 && !ambientesNaoPossui[midiaTipo.id]" 
                                                          class="ml-2 w-2 h-2 bg-green-500 rounded-full"
                                                          title="Quantidade adequada">
                                                    </span>
                                                    <span v-else-if="ambientesNaoPossui[midiaTipo.id]" 
                                                          class="ml-2 text-gray-400"
                                                          title="Marcado como não possui">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                    </span>
                                                </span>
                                                
                                                <!-- Checkbox "Não possui" para área interna -->
                                                <div v-if="midiaTipo.isAreaInterna" class="flex items-center">
                                                    <label class="flex items-center text-xs font-medium text-gray-600 cursor-pointer whitespace-nowrap">
                                                        <input 
                                                            type="checkbox" 
                                                            :checked="ambientesNaoPossui[midiaTipo.id]"
                                                            @change="handleNaoPossuiChange(midiaTipo.id, $event.target.checked)"
                                                            :disabled="!permissions?.canUpdateTeam || !isEditable"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 mr-2"
                                                        />
                                                        Não possui
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div 
                                        class="border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 transition-all duration-200 cursor-pointer group min-h-[120px] flex items-center"
                                        :class="{
                                            'opacity-50 cursor-not-allowed hover:border-gray-300': !permissions?.canUpdateTeam || !isEditable || ambientesNaoPossui[midiaTipo.id] || !canAddMorePhotos(midiaTipo.id),
                                            'bg-gray-50 border-gray-200': ambientesNaoPossui[midiaTipo.id] || !canAddMorePhotos(midiaTipo.id),
                                            'hover:bg-blue-50': permissions?.canUpdateTeam && isEditable && !ambientesNaoPossui[midiaTipo.id] && canAddMorePhotos(midiaTipo.id)
                                        }"
                                        @click="openFileInput(midiaTipo.id)"
                                    >
                                        <input
                                            :id="`file-${midiaTipo.id}`"
                                            :ref="`fileInput-${midiaTipo.id}`"
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            class="hidden"
                                            :disabled="!permissions?.canUpdateTeam || !isEditable || ambientesNaoPossui[midiaTipo.id] || !canAddMorePhotos(midiaTipo.id)"
                                            @change="handleFileChange(midiaTipo.id, $event)"
                                        />
                                        
                                        <div class="flex flex-col items-center justify-center py-6 w-full">
                                            <div class="mb-3">
                                                <svg class="w-12 h-12 text-gray-400 group-hover:text-blue-500 transition-colors duration-200" 
                                                     :class="{ 'group-hover:text-gray-400': !permissions?.canUpdateTeam || !isEditable || ambientesNaoPossui[midiaTipo.id] || !canAddMorePhotos(midiaTipo.id) }"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                            
                                            <div class="text-center">
                                                <p class="text-sm font-medium text-gray-700 mb-1">
                                                    <span v-if="ambientesNaoPossui[midiaTipo.id]">
                                                        Marcado como "Não possui"
                                                    </span>
                                                    <span v-else-if="!canAddMorePhotos(midiaTipo.id)">
                                                        Limite máximo atingido (3 fotos)
                                                    </span>
                                                    <span v-else>
                                                        Adicionar {{ 3 - getPhotoCount(midiaTipo.id) > 1 ? 'mais fotos' : 'mais 1 foto' }}
                                                    </span>
                                                </p>
                                                
                                                <p v-if="!ambientesNaoPossui[midiaTipo.id] && canAddMorePhotos(midiaTipo.id)" 
                                                   class="text-xs text-gray-500">
                                                    <span class="block mt-1">Pode adicionar mais {{ 3 - getPhotoCount(midiaTipo.id) }} foto(s)</span>
                                                    <span class="block text-blue-600 font-medium">
                                                        {{ getPhotoCount(midiaTipo.id) === 0 ? '⚠️ Mínimo: 2 fotos' : getPhotoCount(midiaTipo.id) === 1 ? '⚠️ Adicione mais 1 foto' : '✅ Quantidade OK' }}
                                                    </span>
                                                </p>
                                                
                                                <p v-else-if="!canAddMorePhotos(midiaTipo.id) && !ambientesNaoPossui[midiaTipo.id]" 
                                                   class="text-xs text-gray-500">
                                                    <span class="text-green-600 font-medium">✅ Máximo de 3 fotos atingido</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <InputError :message="form.errors[`files[${midiaTipo.id}]`]" class="mt-3" />
                                    
                                    <div v-if="previewImages[midiaTipo.id]?.length" class="mt-6">
                                        <div class="flex justify-between items-center mb-3">
                                            <h5 class="text-sm font-semibold text-gray-900 flex items-center">
                                                <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                                    <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                </div>
                                                {{ previewImages[midiaTipo.id].length > 1 ? 'Novas fotos' : 'Nova foto' }}
                                            </h5>
                                            <div v-if="!isNew && midiasPorTipo[midiaTipo.id]?.length" class="flex items-center">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                      :class="midiasToReplace[midiaTipo.id] ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'">
                                                    {{ midiasToReplace[midiaTipo.id] ? 'Substituirá' : 'Adicionará' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div v-for="(preview, previewIndex) in previewImages[midiaTipo.id]" 
                                                 :key="previewIndex" 
                                                 class="relative group aspect-square">
                                                <img :src="preview" 
                                                     :alt="`Preview ${previewIndex + 1}`" 
                                                     class="w-full h-full object-cover rounded-lg border border-gray-200 shadow-sm group-hover:shadow-md transition-shadow duration-200" />
                                                <button
                                                    type="button"
                                                    @click="removeFile(midiaTipo.id, previewIndex)"
                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity duration-200 shadow-lg"
                                                    :disabled="!permissions?.canUpdateTeam || !isEditable"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="midiasPorTipo[midiaTipo.id]?.length" class="mt-6">
                                        <h5 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            {{ midiasPorTipo[midiaTipo.id].length > 1 ? 'Fotos atuais' : 'Foto atual' }}
                                        </h5>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div v-for="midia in midiasPorTipo[midiaTipo.id]" 
                                                 :key="midia.id" 
                                                 class="relative group aspect-square">
                                                <img :src="midia.url" 
                                                     :alt="`Mídia ${midia.id}`" 
                                                     class="w-full h-full object-cover rounded-lg border border-gray-200 shadow-sm group-hover:shadow-md transition-shadow duration-200" />
                                                
                                                <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded backdrop-blur-sm">
                                                    {{ getFileSize(midia.tamanho) }}
                                                </div>
                                                
                                                <button
                                                    v-if="permissions?.canUpdateTeam && isEditable"
                                                    type="button"
                                                    @click="removeMidia(midia)"
                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity duration-200 shadow-lg"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <div class="flex items-center justify-between">
                <PrimaryButton 
                    :class="{ 'opacity-25': form.processing || isUploading }" 
                    :disabled="form.processing || isUploading || !permissions?.canUpdateTeam || !isEditable"
                    color="gold"
                >
                    <!-- Ícone de loading -->
                    <svg v-if="form.processing || isUploading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <!-- Texto do botão -->
                    <span v-if="isUploading">
                        Enviando... {{ uploadProgress.overallProgress }}%
                    </span>
                    <span v-else-if="form.processing">
                        Salvando...
                    </span>
                    <span v-else>
                        {{ props.unidade?.is_draft === true ? 'Salvar e Finalizar' : 'Atualizar Fotos' }}
                    </span>
                </PrimaryButton>
            </div>
        </template>

        <template #footer>
            <p class="text-xs text-gray-500 mt-2">
                * Campos obrigatórios para Área Externa e Acessibilidade
            </p>
        </template>
    </FormSection>
    <!-- Modal de Progresso de Upload -->
<div v-if="isUploading" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Centralizar modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal -->
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <!-- Ícone de upload -->
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <svg class="h-6 w-6 text-blue-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>

                <!-- Título -->
                <div class="text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">
                        Enviando Imagens
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Por favor, aguarde enquanto suas imagens são processadas...
                    </p>
                </div>

                <!-- Progresso Geral -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm font-medium text-gray-700 mb-2">
                        <span>Progresso Geral</span>
                        <span>{{ uploadProgress.overallProgress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div 
                            class="bg-blue-600 h-3 rounded-full transition-all duration-300 ease-out"
                            :style="{ width: uploadProgress.overallProgress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Progresso do Lote Atual -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm font-medium text-gray-700 mb-2">
                        <span>Lote {{ uploadProgress.currentBatch }} de {{ uploadProgress.totalBatches }}</span>
                        <span>{{ uploadProgress.currentBatchProgress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div 
                            class="bg-green-500 h-2 rounded-full transition-all duration-300 ease-out"
                            :style="{ width: uploadProgress.currentBatchProgress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Arquivos -->
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ uploadProgress.currentFile }}</div>
                        <div class="text-xs text-gray-600">de {{ uploadProgress.totalFiles }} arquivos</div>
                    </div>
                    
                    <!-- Tempo Estimado -->
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ uploadProgress.estimatedTimeLeft }}</div>
                        <div class="text-xs text-gray-600">tempo restante</div>
                    </div>
                </div>

                <!-- Mensagem de Status -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        <span v-if="uploadProgress.totalBatches > 1">
                            Processando lote {{ uploadProgress.currentBatch }} de {{ uploadProgress.totalBatches }}...
                        </span>
                        <span v-else>
                            Processando suas imagens...
                        </span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Não feche esta janela durante o upload
                    </p>
                </div>

                <!-- Barra de animação -->
                <div class="mt-4">
                    <div class="flex justify-center">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<style scoped>
@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
    transform: translate3d(0,0,0);
  }

  40%, 43% {
    animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
    transform: translate3d(0, -6px, 0);
  }

  70% {
    animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
    transform: translate3d(0, -3px, 0);
  }

  90% {
    transform: translate3d(0,-1px,0);
  }
}

.animate-bounce {
  animation: bounce 2s infinite;
}
</style>