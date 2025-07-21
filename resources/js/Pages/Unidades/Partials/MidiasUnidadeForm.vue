<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, onMounted, computed, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    unidade: Object,
    midias: Array,
    acessibilidade: Object,
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

// controle de "não possui ambiente"
const ambientesNaoPossui = ref({});

const formattedMessage = computed(() => {
  return mensagemFeedback.value.replace(/\(e mais \d+ erro[s]?\)/g, '').trim();
});

onMounted(async () => {
    try {
        const response = await axios.get('/midia-tipos/ativos');
        if (isMounted.value) {
            midiaTipos.value = response.data.map(tipo => {
                // Verificar se este tipo de acessibilidade está marcado como disponível
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
                    // Se é acessibilidade, só aparece se estiver marcado na aba de acessibilidade
                    shouldShow: !['rampa_acesso', 'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                                  'sinalizacao_braile'].includes(tipo.nome) || isAcessibilidadeAtiva
                };
            }).filter(tipo => tipo.shouldShow); // Filtrar apenas os que devem aparecer

            //Inicializar estados de "não possui" para área interna
            midiaTipos.value.forEach(tipo => {
                if (tipo.isAreaInterna) {
                    const temRegistroNaoPossui = props.midias?.some(midia => {
                        // Verificar por ID do tipo de mídia
                        const midiaTipoId = midia.midia_tipo_id || midia.midia_tipo?.id || midia.midiaTipo?.id;
                        
                        // Condições para considerar como "não possui":
                        // 1. Path é 'nao_possui_ambiente'
                        // 2. Pivot tem nao_possui_ambiente = true
                        const isDummyRecord = midia.path === 'nao_possui_ambiente';
                        const hasPivotFlag = midia.pivot && midia.pivot.nao_possui_ambiente === true;
                        
                        return midiaTipoId === tipo.id && (isDummyRecord || hasPivotFlag);
                    }) || false;
                    
                    // Inicializar o estado do checkbox
                    ambientesNaoPossui.value[tipo.id] = temRegistroNaoPossui;
                    
                    // Se não possui, também atualizar o form
                    if (temRegistroNaoPossui) {
                        form.ambientes_nao_possui[tipo.id] = true;
                    }
                }
            });

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

// Watcher para reagir a mudanças nas props.midias
watch(() => props.midias, (newMidias) => {
    if (newMidias && midiaTipos.value.length > 0) {
        // Reinicializar estados de "não possui" quando as props mudarem
        midiaTipos.value.forEach(tipo => {
            if (tipo.isAreaInterna) {
                const temRegistroNaoPossui = newMidias.some(midia => {
                    const midiaTipoId = midia.midia_tipo_id || midia.midia_tipo?.id || midia.midiaTipo?.id;
                    const isDummyRecord = midia.path === 'nao_possui_ambiente';
                    const hasPivotFlag = midia.pivot && midia.pivot.nao_possui_ambiente === true;
                    
                    return midiaTipoId === tipo.id && (isDummyRecord || hasPivotFlag);
                }) || false;
                
                // Atualizar os estados
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

const handleFileChange = (midiaTipoId, event) => {
    // Pega apenas o primeiro arquivo selecionado
    const file = event.target.files[0];
    if (!file) return;

    // Se é área interna e o usuário selecionou um arquivo, desmarcar "não possui"
    const tipo = midiaTipos.value.find(t => t.id === midiaTipoId);
    if (tipo?.isAreaInterna) {
        ambientesNaoPossui.value[midiaTipoId] = false;
        form.ambientes_nao_possui[midiaTipoId] = false;
    }
    
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

const handleNaoPossuiChange = (midiaTipoId, checked) => {
    // Atualizar o estado local
    ambientesNaoPossui.value[midiaTipoId] = checked;
    
    // Atualizar o form para ser enviado
    form.ambientes_nao_possui[midiaTipoId] = checked;
    
    // Se marcou "não possui", limpar qualquer arquivo selecionado
    if (checked) {
        removeAllFilesForType(midiaTipoId);
    }
};

const removeAllFilesForType = (midiaTipoId) => {
    // Remover previews
    if (previewImages.value[midiaTipoId]) {
        previewImages.value[midiaTipoId] = [];
    }
    
    // Remover arquivos do form
    if (form.midia_files[midiaTipoId]) {
        delete form.midia_files[midiaTipoId];
        delete form.midia_tipos[midiaTipoId];
        delete form.midia_replace[midiaTipoId];
        delete midiasToReplace.value[midiaTipoId];
    }
    
    // Limpar input de arquivo
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

// Função para verificar se um ambiente de área interna está completo
const isAreaInternaCompleta = (midiaTipoId) => {
    const hasFile = form.midia_files[midiaTipoId]?.length > 0;
    const hasExistingMedia = midiasPorTipo.value[midiaTipoId]?.length > 0;
    const marcouNaoPossui = ambientesNaoPossui.value[midiaTipoId];
    
    return hasFile || hasExistingMedia || marcouNaoPossui;
};

const saveOrFinalizeMidias = () => {
    if (!props.permissions?.canUpdateTeam || !props.isEditable) {
        mensagemFeedback.value = 'O cadastro não pode ser editado.';
        tipoFeedback.value = 'error';
        emit('saved', mensagemFeedback.value);
        return;
    }

    // VALIDAÇÃO ÁREA EXTERNA (fotos obrigatórias)
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

    // VALIDAÇÃO ACESSIBILIDADE (fotos obrigatórias para recursos marcados)
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

    // VALIDAÇÃO ÁREA INTERNA (foto OU "não possui")
    const missingAreaInternaTypes = [];
    
    midiaTipos.value
        .filter(tipo => tipo.isAreaInterna)
        .forEach(tipo => {
            if (!isAreaInternaCompleta(tipo.id)) {
                missingAreaInternaTypes.push(formatarNomeTipoMidia(tipo.nome));
            }
        });
    
    // EXIBIR ERROS DE VALIDAÇÃO
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
        
        mensagemFeedback.value = errorMessage;
        tipoFeedback.value = 'error';
        emit('saved', mensagemFeedback.value);
        return;
    }

    // PREPARAR DADOS PARA ENVIO
    const formData = new FormData();
    formData.append('unidade_id', form.unidade_id);

    // Adicionar dados de "não possui ambiente"
    Object.entries(ambientesNaoPossui.value).forEach(([tipoId, naoPossui]) => {
        formData.append(`ambientes_nao_possui[${tipoId}]`, naoPossui ? "1" : "0");
    });

    // Verificar se há pelo menos uma alteração
    const hasFiles = Object.keys(form.midia_files).length > 0;
    const hasRemovals = midiasToRemove.value.length > 0;
    const hasNaoPossuiChanges = Object.values(ambientesNaoPossui.value).some(v => v);
    
    if (!hasFiles && !hasRemovals && !hasNaoPossuiChanges) {
        mensagemFeedback.value = 'Por favor, faça pelo menos uma alteração antes de salvar.';
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

    // Adicionar arquivos
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
    
    // ENVIAR DADOS
    mensagemFeedback.value = 'Processando alterações nas mídias...';
    tipoFeedback.value = 'info';

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
            
            // Limpar arrays de controle após sucesso
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
    //  Área Externa
    if (nome === 'foto_frente') return 'Frente';
    if (nome === 'foto_lateral_1') return 'Lateral esquerda';
    if (nome === 'foto_lateral_2') return 'Lateral direita';
    if (nome === 'foto_fundos') return 'Fundos';
    if (nome === 'foto_medidor_agua') return 'Número do medidor de água';
    if (nome === 'foto_medidor_energia') return 'Número do medidor de energia';
    
    //  Área Interna
    if (nome === 'recepção') return 'Recepção';
    if (nome === 'sala_oitiva') return 'Sala de oitiva';
    if (nome === 'sala_boletim_de_ocorrência') return 'Sala de boletim de ocorrência';
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
    if (nome === 'cozinha') return 'Cozinha';
    if (nome === 'copa') return 'Copa';
    if (nome === 'área_de_serviço') return 'Área de serviço';
    if (nome === 'dispensa') return 'Dispensa';
    if (nome === 'depósito_apreensão') return 'Depósito de apreensão';
    if (nome === 'garagem') return 'Garagem';
    
    // acessibilidade
    if (nome === 'rampa_acesso') return 'Rampa de acesso';
    if (nome === 'corrimao') return 'Corrimão';
    if (nome === 'piso_tatil') return 'Piso tátil';
    if (nome === 'banheiro_adaptado') return 'Banheiro adaptado';
    if (nome === 'elevador') return 'Elevador';
    if (nome === 'sinalizacao_braile') return 'Sinalização em braille';

    // Equipamentos de segurança
    if (nome === 'porta_principal') return 'Porta principal';
    if (nome === 'luminarias_emergencia') return 'Luminárias de emergência';
    if (nome === 'escada_acesso') return 'Escada de acesso';
    if (nome === 'demarcacao_extintor') return 'Demarcação no piso do extintor';
    
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
            
            if (midia.path === 'nao_possui_ambiente') return;
            if (midia.pivot && midia.pivot.nao_possui_ambiente === true) return;
            
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
    
    // Ordenar as categorias para ter uma ordem fixa: Área Externa, Área Interna, Acessibilidade
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
            <p>
                {{ isNew ? 'Adicione' : 'Gerencie' }} fotos da unidade para cada tipo de mídia. 
                <strong v-if="isNew" class="text-red-600">Os itens com * são obrigatórios.</strong>
            </p>
            <p class="mt-2 text-sm text-gray-600">
                Formatos aceitos: JPG, PNG (máx. 10MB)
            </p>
            <p class="mt-2 text-sm text-blue-600">
                <strong>Para Área Interna:</strong> Inclua uma foto OU marque "Não possui" se a unidade não tem esse ambiente.
            </p>
            <p class="mt-1 text-sm text-blue-600">
                <strong>Para Acessibilidade:</strong> É obrigatório incluir fotos dos recursos marcados no formulário de acessibilidade.
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
                
                <div v-if="isLoading" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-gray-900"></div>
                    <span class="ml-2">Carregando tipos de mídia...</span>
                </div>
                
                <div v-else-if="midiaTipos.length === 0" class="mt-2">
                    <p class="text-red-600">Nenhum tipo de mídia disponível ou erro ao carregar.</p>
                </div>
                
                <div v-else>

                    <!-- Seções para cada categoria -->
                    <div v-for="(grupo, index) in getTabelas" :key="index" :id="`section-${index}`" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200 flex items-center">
                            {{ grupo[0] }}
                            <span v-if="grupo[0] === 'Área Externa'" class="ml-2 text-sm text-red-600 font-normal">
                                (Obrigatório)
                            </span>
                            <span v-else-if="grupo[0] === 'Área Interna'" class="ml-2 text-sm text-blue-600 font-normal">
                                (Foto OU "Não possui")
                            </span>
                            <span v-else-if="grupo[0] === 'Acessibilidade'" class="ml-2 text-sm text-red-600 font-normal">
                                (Obrigatório para recursos marcados)
                            </span>
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <div v-for="midiaTipo in grupo[1]" :key="midiaTipo.id" 
                                 class="border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200"
                                 :class="{
                                    'border-red-200 ': (midiaTipo.isRequired || midiaTipo.isAcessibilidadeObrigatoria) && !midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length,
                                    'border-blue-200': midiaTipo.isAreaInterna && !isAreaInternaCompleta(midiaTipo.id),
                                    'border-green-200 bg-green-50': ((midiaTipo.isRequired || midiaTipo.isAcessibilidadeObrigatoria) && (midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length)) || 
                                                                    (midiaTipo.isAreaInterna && isAreaInternaCompleta(midiaTipo.id))
                                 }">
                                 
                                 <div class="flex justify-between items-start mb-2">
                                    <InputLabel 
                                        :for="`file-${midiaTipo.id}`" 
                                        :value="formatarNomeTipoMidia(midiaTipo.nome)"
                                        class="font-medium text-gray-800"
                                    />
                                    <div class="flex items-center">
                                        <span v-if="midiaTipo.isRequired || midiaTipo.isAcessibilidadeObrigatoria" 
                                            class="text-red-600 text-lg font-bold ml-1"
                                            title="Campo obrigatório">*</span>
                                    </div>
                                </div>

                                <!-- Badge de status logo abaixo do título -->
                                <div v-if="midiaTipo.isRequired || midiaTipo.isAreaInterna || midiaTipo.isAcessibilidadeObrigatoria" 
                                     class="mb-3">
                                    <span class="inline-flex items-center text-xs px-2 py-1 rounded-full font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': midiaTipo.isAreaInterna ? isAreaInternaCompleta(midiaTipo.id) : (midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length),
                                            'bg-red-100 text-red-800': midiaTipo.isAreaInterna ? !isAreaInternaCompleta(midiaTipo.id) : (!midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length)
                                        }">
                                        <span class="mr-1">
                                            {{ (midiaTipo.isAreaInterna ? isAreaInternaCompleta(midiaTipo.id) : (midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length)) ? '✓' : '⚠' }}
                                        </span>
                                        {{ (midiaTipo.isAreaInterna ? isAreaInternaCompleta(midiaTipo.id) : (midiasPorTipo[midiaTipo.id]?.length || form.midia_files[midiaTipo.id]?.length)) ? 'Completo' : 'Pendente' }}
                                    </span>
                                </div>

                                <!-- Checkbox "Não possui" apenas para Área Interna -->
                                <div v-if="midiaTipo.isAreaInterna" class="mb-3 p-2 rounded-md border border-blue-200">
                                    <div class="flex items-center">
                                        <Checkbox
                                            :id="`nao-possui-${midiaTipo.id}`"
                                            :checked="ambientesNaoPossui[midiaTipo.id]"
                                            @update:checked="handleNaoPossuiChange(midiaTipo.id, $event)"
                                            :disabled="!permissions?.canUpdateTeam || !isEditable"
                                        />
                                        <InputLabel
                                            :for="`nao-possui-${midiaTipo.id}`"
                                            value="A unidade não possui este ambiente"
                                            class="ml-2 text-sm text-blue-700 cursor-pointer font-medium"
                                        />
                                    </div>
                                </div>
                                
                                <!-- Área de drop e upload -->
                                <div 
                                    v-if="!midiaTipo.isAreaInterna || !ambientesNaoPossui[midiaTipo.id]"
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-gray-50 transition-colors duration-200 cursor-pointer mb-3"
                                    :class="{ 
                                        'border-red-300 bg-red-50': (isNew && (midiaTipo.isRequired || midiaTipo.isAcessibilidadeObrigatoria) && !midiasPorTipo[midiaTipo.id]?.length && !form.midia_files[midiaTipo.id]?.length) ||
                                                         (midiaTipo.isAreaInterna && !isAreaInternaCompleta(midiaTipo.id)),
                                        'opacity-50 cursor-not-allowed': ambientesNaoPossui[midiaTipo.id]
                                    }"
                                    @click="!ambientesNaoPossui[midiaTipo.id] && $refs[`fileInput-${midiaTipo.id}`][0].click()"
                                >
                                    <input
                                        :id="`file-${midiaTipo.id}`"
                                        :ref="`fileInput-${midiaTipo.id}`"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        :disabled="!permissions?.canUpdateTeam || !isEditable || ambientesNaoPossui[midiaTipo.id]"
                                        @change="handleFileChange(midiaTipo.id, $event)"
                                    />
                                    
                                    <div class="flex flex-col items-center justify-center py-3">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="text-sm text-gray-600">
                                            {{ ambientesNaoPossui[midiaTipo.id] ? 'Marcado como "Não possui"' : 'Clique para selecionar uma foto' }}
                                        </p>
                                        <p v-if="!ambientesNaoPossui[midiaTipo.id]" class="text-xs text-gray-500 mt-1">JPG, PNG até 10MB</p>
                                    </div>
                                </div>

                                <InputError :message="form.errors[`files[${midiaTipo.id}]`]" class="mt-2" />
                                
                                <!-- Previews de imagens a serem enviadas -->
                                <div v-if="previewImages[midiaTipo.id]?.length" class="mt-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm font-medium text-gray-700">Nova imagem selecionada:</p>
                                        <div v-if="!isNew && midiasPorTipo[midiaTipo.id]?.length" class="flex items-center">
                                            <span class="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded-full">Substituirá a existente</span>
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
                                        <p class="text-sm font-medium text-gray-700">Imagem cadastrada:</p>
                                        <p v-if="!isNew && previewImages[midiaTipo.id]?.length" class="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded-full">
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
                                                class="h-24 w-full object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity"
                                                @click="window.open(midia.url, '_blank')"
                                            />
                                            <!-- Botão para remover mídia existente -->
                                            <button 
                                                v-if="permissions?.canUpdateTeam && isEditable && !isNew"
                                                type="button"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                @click.prevent="removeMidia(midia)"
                                                title="Remover esta imagem"
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
                    <span>Salvo com sucesso!</span>
                </div>
                
                <!-- Mensagem de erro -->
                <div v-else-if="tipoFeedback === 'error'" 
                     class="mr-4 inline-flex items-center px-3 py-1 rounded-md bg-red-100 text-red-800 text-sm animate-fade-in max-w-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="truncate">{{ formattedMessage }}</span>
                </div>

                <PrimaryButton 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                    color="gold"
                >
                    <div v-if="form.processing" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                    <svg v-else-if="props.unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ form.processing ? 'Salvando...' : (props.unidade?.is_draft === true ? 'Salvar e Finalizar' : 'Atualizar Mídias') }}
                </PrimaryButton>
            </div>
        </template>
    </FormSection>
</template>

<style scoped>
/* Estilo para a área de drop quando arrastando arquivos */
.border-dashed.border-gray-300:hover:not(.opacity-50) {
    background-color: rgba(190, 165, 90, 0.05);
    border-color: #bea55a;
}

/* Animação de fade para mensagens de feedback */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
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
    transition: transform 0.2s ease;
}

img:hover {
    transform: scale(1.02);
}
</style>