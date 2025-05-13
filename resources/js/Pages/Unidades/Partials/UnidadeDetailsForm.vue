<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

const emit = defineEmits(['saved']);
const isLoading = ref(false);
const mapLoaded = ref(false);

const props = defineProps({
    team: Object,
    unidade: Object,
    orgaos: Array,
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

// Inicializar os IDs dos órgãos compartilhados, garantindo que sejam números
const orgaoIds = props.unidade?.orgaosCompartilhados?.length
    ? props.unidade.orgaosCompartilhados.map(orgao => Number(orgao.id))
    : [];
    
const form = useForm({
    team_id: props.team?.id || '',
    nome: props.unidade?.nome || props.team?.name || '',
    tipo_estrutural: props.unidade?.tipo_estrutural || '',
    srpc: props.unidade?.srpc || '',
    dspc: props.unidade?.dspc || '',
    sede: props.unidade?.sede || false,
    cidade: props.unidade?.cidade || '',
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
    imovel_compartilhado_orgao: props.unidade?.imovel_compartilhado_orgao || false,
    imovel_compartilhado_orgao_ids: orgaoIds,
    observacoes: props.unidade?.observacoes || '',
    numero_medidor_agua: props.unidade?.numero_medidor_agua || '',
    numero_medidor_energia: props.unidade?.numero_medidor_energia || '',
});

// Watch para depurar mudanças em imovel_compartilhado_orgao_ids
/* watch(() => form.imovel_compartilhado_orgao_ids, (newValue) => {
    console.log('imovel_compartilhado_orgao_ids atualizado:', newValue);
});

// Watch para depurar mudanças em props.unidade
watch(() => props.unidade, (newUnidade) => {
    console.log('Props unidade atualizado:', newUnidade);
    console.log('Orgaos Compartilhados atualizado:', newUnidade?.orgaosCompartilhados);
    if (newUnidade?.orgaosCompartilhados?.length) {
        form.imovel_compartilhado_orgao_ids = newUnidade.orgaosCompartilhados.map(orgao => Number(orgao.id));
    }
}); */

const activeSection = ref('geral');
const mapContainer = ref(null);
const orgaoSelect = ref(null); // Referência ao select
let map = null;
let marker = null;
let resizeObserver = null;

const debouncedUpdate = debounce((field, value) => {
    form[field] = value;
}, 300);

const methods = {
    updateField: (field, value) => {
        debouncedUpdate(field, value);
    },
    setActiveSection: (section) => {
        activeSection.value = section;
        if (section === 'localizacao' && mapLoaded.value) {
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 100);
        }
    }
};

const initMap = () => {
    if (!window.L || !mapContainer.value) {
        setTimeout(initMap, 500);
        return;
    }

    const defaultLocation = [-23.55052, -46.633308];
    const initialLocation = form.latitude && form.longitude 
        ? [parseFloat(form.latitude), parseFloat(form.longitude)]
        : defaultLocation;

    if (map) map.remove();

    map = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false
    }).setView(initialLocation, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    marker = L.marker(initialLocation, { draggable: props.isEditable }).addTo(map);
    marker.on('dragend', () => {
        if (!props.isEditable) return;
        const position = marker.getLatLng();
        form.latitude = position.lat.toFixed(8);
        form.longitude = position.lng.toFixed(8);
        updateAddressFromCoordinates(position.lat, position.lng);
    });
    
    map.on('click', (e) => {
        if (!props.isEditable) return;
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        form.latitude = lat.toFixed(8);
        form.longitude = lng.toFixed(8);
        updateAddressFromCoordinates(lat, lng);
    });

    mapLoaded.value = true;

    if (!resizeObserver && window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => {
            if (map) map.invalidateSize();
        });
        resizeObserver.observe(mapContainer.value);
    }
};

const getLocationFromGPS = () => {
    if (!props.isEditable || !navigator.geolocation) {
        emit('saved', null, 'Geolocalização não suportada pelo navegador.');
        return;
    }
    isLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            form.latitude = lat.toFixed(8);
            form.longitude = lng.toFixed(8);
            if (marker && map) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 15);
                updateAddressFromCoordinates(lat, lng);
            }
            isLoading.value = false;
        },
        (error) => {
            isLoading.value = false;
            let errorMessage = 'Não foi possível obter a localização: ';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Permissão negada.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Localização indisponível.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Tempo limite excedido.';
                    break;
                default:
                    errorMessage += error.message;
            }
            emit('saved', null, errorMessage);
        },
        { timeout: 10000, maximumAge: 0, enableHighAccuracy: true }
    );
};

const updateAddressFromCoordinates = async (lat, lng) => {
    if (!props.isEditable) return;
    isLoading.value = true;
    try {
        const response = await axios.get('/geocoding/reverse', {
            params: { lat, lng },
        });
        const data = response.data;
        if (data.address_details) {
            const address = data.address_details;
            form.rua = address.road || form.rua;
            form.numero = address.house_number || form.numero;
            form.bairro = address.suburb || form.bairro;
            form.cidade = address.city || form.cidade;
            form.cep = address.postcode || form.cep;
        } else {
            emit('saved', null, 'Endereço não encontrado para estas coordenadas.');
        }
    } catch (error) {
        const errorMessage = `Erro ao buscar endereço: ${error.response?.status || 'Conexão falhou'}.`;
        emit('saved', null, errorMessage);
    } finally {
        isLoading.value = false;
    }
};

const debouncedUpdateCoordinates = debounce(async () => {
    if (!props.isEditable) return;
    isLoading.value = true;
    const address = `${form.rua} ${form.numero}, ${form.bairro}, ${form.cidade}, ${form.cep}`;
    try {
        const response = await axios.get('/geocoding/search', {
            params: { q: address },
        });
        const data = response.data;
        if (data.latitude && data.longitude) {
            const lat = parseFloat(data.latitude).toFixed(8);
            const lng = parseFloat(data.longitude).toFixed(8);
            form.latitude = lat;
            form.longitude = lng;
            if (marker && map) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 15);
            }
        } else {
            emit('saved', null, 'Coordenadas não encontradas para este endereço.');
        }
    } catch (error) {
        const errorMessage = `Erro ao buscar coordenadas: ${error.response?.status || 'Conexão falhou'}.`;
        emit('saved', null, errorMessage);
    } finally {
        isLoading.value = false;
    }
}, 500);

const updateCoordinatesFromAddress = () => {
    debouncedUpdateCoordinates();
};

watch(() => form.tipo_judicial, (newValue) => {
    if (newValue !== 'locado') {
        form.nome_proprietario = '';
        form.cpf_cnpj = '';
        form.telefone_proprietario = '';
        form.valor_locacao = '';
        form.data_inicio = '';
        form.data_fim = '';
    }
    if (newValue !== 'cedido') {
        form.orgao_cedente = '';
        form.termo_cessao = '';
        form.prazo_cessao = '';
    }
});

watch(() => form.imovel_compartilhado_orgao, (newValue) => {
    if (!newValue) {
        form.imovel_compartilhado_orgao_ids = [];
    }
});

onMounted(() => {
    // Forçar atualização do select
    if (orgaoSelect.value && form.imovel_compartilhado_orgao_ids.length) {
        form.imovel_compartilhado_orgao_ids.forEach(id => {
            const option = orgaoSelect.value.querySelector(`option[value="${id}"]`);
            if (option) option.selected = true;
        });
    }

    if (!window.L) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = initMap;
        document.head.appendChild(script);
    } else {
        initMap();
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
    if (map) {
        map.remove();
        map = null;
    }
});

const saveDadosGerais = () => {
    if (!props.isEditable) {
        emit('saved', 'O cadastro está finalizado e não pode ser editado.');
        return;
    }

    const requiredFields = ['nome', 'cidade', 'cep', 'rua', 'latitude', 'longitude', 'tipo_estrutural', 'tipo_judicial'];
    const errors = [];
    requiredFields.forEach((field) => {
        if (!form[field]) {
            errors.push(`O campo ${field} é obrigatório.`);
            form.errors[field] = `O campo ${field} é obrigatório.`;
        }
    });

    if (form.imovel_compartilhado_orgao && form.imovel_compartilhado_orgao_ids.length === 0) {
        errors.push('Selecione pelo menos um órgão.');
        form.errors.imovel_compartilhado_orgao_ids = 'Selecione pelo menos um órgão.';
    }

    if (errors.length > 0) {
        emit('saved', errors.join(' '));
        return;
    }

    form.cep = form.cep ? form.cep.replace(/[^0-9]/g, '') : '';
    form.telefone_1 = form.telefone_1 ? form.telefone_1.replace(/[^0-9]/g, '') : '';
    form.telefone_2 = form.telefone_2 ? form.telefone_2.replace(/[^0-9]/g, '') : '';

    form.post(route('unidades.saveDadosGerais'), {
        errorBag: 'saveDadosGerais',
        preserveScroll: true,
        onSuccess: () => {
            emit('saved'); // Apenas emite o evento sem forçar transição de aba
        },
        onError: (errors) => {
            emit('saved', 'Erro ao salvar os dados. Verifique os campos.');
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px" aria-label="Tabs">
                <button
                    @click="methods.setActiveSection('geral')"
                    :class="[
                        'py-3 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                        activeSection === 'geral'
                            ? 'border-[#bea55a] text-[#bea55a]'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <i class="fas fa-building mr-1"></i>
                    <span class="hidden sm:inline">Dados Gerais</span>
                    <span class="sm:hidden">Dados</span>
                </button>
                <button
                    @click="methods.setActiveSection('localizacao')"
                    :class="[
                        'py-3 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                        activeSection === 'localizacao'
                            ? 'border-[#bea55a] text-[#bea55a]'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    <span class="hidden sm:inline">Localização</span>
                    <span class="sm:hidden">Mapa</span>
                </button>
            </nav>
        </div>

        <form @submit.prevent="saveDadosGerais">
            <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[1000]">
                <div class="bg-white p-6 rounded-lg shadow-xl">
                    <div class="flex items-center space-x-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#bea55a]"></div>
                        <p class="text-lg font-semibold">Carregando dados...</p>
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'geral'" class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="nome" value="Nome da Unidade *" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="nome"
                            v-model="form.nome"
                            type="text"
                            class="bg-gray-100 mt-1 block w-full"
                            disabled
                            placeholder="Ex: Delegacia Civil de Centro"
                        />
                        <InputError :message="form.errors.nome" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="tipo_estrutural" value="Tipo Estrutural *" class="text-sm font-medium text-gray-700" />
                        <SelectInput
                            id="tipo_estrutural"
                            v-model="form.tipo_estrutural"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option value="">Selecione o tipo</option>
                            <option value="delegacia">Delegacia</option>
                            <option value="unidade_especializada">Unidade Especializada</option>
                            <option value="instituto">Instituto</option>
                            <option value="academia">Academia</option>
                            <option value="superintendencia">Superintendência</option>
                            <option value="outra">Outra</option>
                        </SelectInput>
                        <InputError :message="form.errors.tipo_estrutural" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="srpc" value="Unidade Gestora" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="srpc"
                            v-model="form.srpc"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="DEGEPOL, 1ª SRPC, etc"
                        />
                        <InputError :message="form.errors.srpc" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="dspc" value="Unidade Sub-Gestora" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="dspc"
                            v-model="form.dspc"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="COORDEAM, 1ª DSPC, etc"
                        />
                        <InputError :message="form.errors.dspc" class="mt-1" />
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center h-full mt-6">
                            <Checkbox 
                                id="sede" 
                                v-model:checked="form.sede" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="sede" value="Sede" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.sede" class="mt-1" />
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações de Contato</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="email" value="Email" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="contato@dominio.com"
                        />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="telefone_1" value="Telefone 1" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="telefone_1"
                            :value="form.telefone_1"
                            type="text"
                            v-imask="{
                                mask: '{(00) }0000[0]-0000',
                                lazy: false,
                                overwrite: true
                            }"
                            placeholder="(11) 98765-4321"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            @update:modelValue="methods.updateField('telefone_1', $event)"
                        />
                        <InputError :message="form.errors.telefone_1" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="telefone_2" value="Telefone 2" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="telefone_2"
                            :value="form.telefone_2"
                            type="text"
                            v-imask="{
                                mask: '{(00) }0000[0]-0000',
                                lazy: false,
                                overwrite: true
                            }"
                            placeholder="(11) 98765-4321"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            @update:modelValue="methods.updateField('telefone_2', $event)"
                        />
                        <InputError :message="form.errors.telefone_2" class="mt-1" />
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Técnicas</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 mb-6">
                    <div>
                        <InputLabel for="tipo_judicial" value="Tipo Judicial *" class="text-sm font-medium text-gray-700" />
                        <SelectInput
                            id="tipo_judicial"
                            v-model="form.tipo_judicial"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option value="">Selecione</option>
                            <option value="proprio">Próprio</option>
                            <option value="locado">Locado</option>
                            <option value="cedido">Cedido</option>
                        </SelectInput>
                        <InputError :message="form.errors.tipo_judicial" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="numero_medidor_agua" value="Medidor de Água" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="numero_medidor_agua"
                            v-model="form.numero_medidor_agua"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="Número do medidor"
                        />
                        <InputError :message="form.errors.numero_medidor_agua" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="numero_medidor_energia" value="Medidor de Energia" class="text-sm font-medium text-gray-700" />
                        <TextInput
                            id="numero_medidor_energia"
                            v-model="form.numero_medidor_energia"
                            type="text"
                            class="mt-1 block w-full"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            placeholder="Número do medidor"
                        />
                        <InputError :message="form.errors.numero_medidor_energia" class="mt-1" />
                    </div>
                    <div class="md:col-span-3">
                        <div class="flex items-center mt-2">
                            <Checkbox 
                                id="imovel_compartilhado_orgao" 
                                v-model:checked="form.imovel_compartilhado_orgao" 
                                :disabled="!isEditable || !permissions?.canUpdateTeam" 
                            />
                            <InputLabel for="imovel_compartilhado_orgao" value="Imóvel Compartilhado com Outro Órgão" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.imovel_compartilhado_orgao" class="mt-1" />
                    </div>
                    <div v-if="form.imovel_compartilhado_orgao" class="md:col-span-3">
                        <InputLabel for="imovel_compartilhado_orgao_ids" value="Órgãos Compartilhados *" class="text-sm font-medium text-gray-700" />
                        <select
                            id="imovel_compartilhado_orgao_ids"
                            ref="orgaoSelect"
                            v-model="form.imovel_compartilhado_orgao_ids"
                            multiple
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                        >
                            <option v-for="orgao in orgaos" :key="orgao.id" :value="Number(orgao.id)">
                                {{ orgao.nome }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Segure Ctrl (ou Cmd) para selecionar múltiplos órgãos.</p>
                        <InputError :message="form.errors.imovel_compartilhado_orgao_ids" class="mt-1" />
                    </div>
                    <div class="md:col-span-3">
                        <InputLabel for="observacoes" value="Observações" class="text-sm font-medium text-gray-700" />
                        <textarea
                            id="observacoes"
                            v-model="form.observacoes"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            :disabled="!isEditable || !permissions?.canUpdateTeam"
                            rows="3"
                            placeholder="Informações adicionais sobre a unidade"
                        />
                        <InputError :message="form.errors.observacoes" class="mt-1" />
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'localizacao'" class="p-6">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Clique no mapa para definir a localização precisa ou use o botão para capturar via GPS. Você também pode preencher o endereço manualmente.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-6 mb-6">
                    <div class="w-full md:w-3/5">
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-100 h-[400px]">
                            <div ref="mapContainer" class="w-full h-full"></div>
                        </div>
                        <div class="mt-4">
                            <PrimaryButton
                                type="button"
                                @click="getLocationFromGPS"
                                class="bg-[#bea55a] hover:bg-[#d4bf7a] text-white"
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                Capturar Via GPS
                            </PrimaryButton>
                        </div>
                    </div>
                    <div class="w-full md:w-2/5">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="latitude" value="Latitude *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="0.00000001"
                                    class="mt-1 block w-full bg-gray-100"
                                    disabled
                                    @change="updateAddressFromCoordinates(form.latitude, form.longitude)"
                                />
                                <InputError :message="form.errors.latitude" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="longitude" value="Longitude *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="number"
                                    step="0.00000001"
                                    class="mt-1 block w-full bg-gray-100"
                                    disabled
                                    @change="updateAddressFromCoordinates(form.latitude, form.longitude)"
                                />
                                <InputError :message="form.errors.longitude" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="cep" value="CEP *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="cep"
                                    v-model="form.cep"
                                    type="text"
                                    v-imask="{ mask: '00000-000' }"
                                    placeholder="12345-678"
                                    class="mt-1 block w-full"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @change="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.cep" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="cidade" value="Cidade *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="cidade"
                                    v-model="form.cidade"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Nome da cidade"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @change="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.cidade" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="rua" value="Rua *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="rua"
                                    v-model="form.rua"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Nome da rua"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @change="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.rua" class="mt-1" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="numero" value="Número" class="text-sm font-medium text-gray-700" />
                                    <TextInput
                                        id="numero"
                                        v-model="form.numero"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="123"
                                        :disabled="!isEditable || !permissions?.canUpdateTeam"
                                        @change="updateCoordinatesFromAddress"
                                    />
                                    <InputError :message="form.errors.numero" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="bairro" value="Bairro" class="text-sm font-medium text-gray-700" />
                                    <TextInput
                                        id="bairro"
                                        v-model="form.bairro"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Nome do bairro"
                                        :disabled="!isEditable || !permissions?.canUpdateTeam"
                                        @change="updateCoordinatesFromAddress"
                                    />
                                    <InputError :message="form.errors.bairro" class="mt-1" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="complemento" value="Complemento" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="complemento"
                                    v-model="form.complemento"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Complemento"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                />
                                <InputError :message="form.errors.complemento" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isEditable && permissions?.canUpdateTeam" class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex items-center justify-between sticky bottom-0">
                <div class="text-sm text-gray-600">
                    <span class="text-red-500">*</span> Campos obrigatórios
                </div>
                <div class="flex items-center">
                    <ActionMessage :on="form.recentlySuccessful" class="mr-4">
                        <span class="text-green-600 font-medium">Salvo com sucesso</span>
                    </ActionMessage>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        color="gold"
                    >
                        Salvar e Continuar
                        <svg v-if="unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>

<style>
.leaflet-container {
    font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
}

@media (max-width: 640px) {
    .leaflet-control-zoom {
        margin-bottom: 60px !important;
    }
}
</style>