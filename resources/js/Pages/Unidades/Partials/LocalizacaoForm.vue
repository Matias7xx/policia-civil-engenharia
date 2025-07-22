<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useToast } from '@/Composables/useToast';

const toast = useToast();

const emit = defineEmits(['saved']);
const isLoading = ref(false);
const mapLoaded = ref(false);
const gpsLoading = ref(false);
const addressLoading = ref(false);
const isHTTPS = computed(() => window.location.protocol === 'https:');
const supportsGeolocation = computed(() => 'geolocation' in navigator);

const props = defineProps({
    team: Object,
    unidade: Object,
    permissions: Object,
    isNew: Boolean,
    isEditable: Boolean,
});

const form = useForm({
    team_id: props.team?.id || '',
    cidade: props.unidade?.cidade || '',
    cep: props.unidade?.cep || '',
    rua: props.unidade?.rua || '',
    numero: props.unidade?.numero || '',
    bairro: props.unidade?.bairro || '',
    complemento: props.unidade?.complemento || '',
    latitude: props.unidade?.latitude || '',
    longitude: props.unidade?.longitude || '',
});

const mapContainer = ref(null);
let map = null;
let marker = null;
let resizeObserver = null;

// Computed para validação de campos obrigatórios
const hasRequiredFields = computed(() => {
    return form.cidade && form.cep && form.rua && form.bairro && form.latitude && form.longitude;
});

// Watch para limpar erros quando o usuário digita
watch(() => form.data(), () => {
    if (form.hasErrors) {
        Object.keys(form.errors).forEach(key => {
            if (form[key]) {
                delete form.errors[key];
            }
        });
    }
}, { deep: true });

const debouncedUpdateCoordinates = debounce(async () => {
    if (!props.isEditable || addressLoading.value) return;
    
    const address = `${form.rua} ${form.numero}, ${form.bairro}, ${form.cidade}, ${form.cep}`;
    if (!form.rua || !form.cidade) return;

    addressLoading.value = true;
    try {
        const response = await axios.get('/geocoding/search', {
            params: { q: address },
            timeout: 10000
        });
        const data = response.data;
        if (data.latitude && data.longitude) {
            const lat = parseFloat(data.latitude).toFixed(8);
            const lng = parseFloat(data.longitude).toFixed(8);
            form.latitude = lat;
            form.longitude = lng;
            if (marker && map) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 16, { animate: true });
            }
        }
    } catch (error) {
        console.warn('Erro ao buscar coordenadas:', error);
        // Não mostrar erro para não incomodar o usuário durante digitação
    } finally {
        addressLoading.value = false;
    }
}, 800);

const updateCoordinatesFromAddress = () => {
    debouncedUpdateCoordinates();
};

const updateAddressFromCoordinates = debounce(async (lat, lng) => {
    if (!props.isEditable || addressLoading.value) return;
    
    addressLoading.value = true;
    try {
        const response = await axios.get('/geocoding/reverse', {
            params: { lat, lng },
            timeout: 10000
        });
        const data = response.data;
        if (data.address_details) {
            const address = data.address_details;
            // Sempre atualiza as informações quando o usuário clica no mapa
            if (address.road) form.rua = address.road;
            if (address.house_number) form.numero = address.house_number;
            if (address.suburb) form.bairro = address.suburb;
            if (address.city) form.cidade = address.city;
            if (address.postcode) form.cep = address.postcode;
        }
    } catch (error) {
        console.warn('Erro ao buscar endereço:', error);
    } finally {
        addressLoading.value = false;
    }
}, 300);

const initMap = () => {
    if (!mapContainer.value) return;

    // Localização padrão (João Pessoa - PB)
    const defaultLocation = [-7.1195, -34.8450]; // Coordenadas de João Pessoa - PB
    const initialLocation = form.latitude && form.longitude 
        ? [parseFloat(form.latitude), parseFloat(form.longitude)]
        : defaultLocation;

    if (map) map.remove();

    map = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false,
        preferCanvas: true, // Melhor performance
        zoomAnimation: true,
        fadeAnimation: true,
        markerZoomAnimation: true
    }).setView(initialLocation, form.latitude ? 16 : 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18,
        loading: 'lazy'
    }).addTo(map);

    // Ícone personalizado para o marker
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div class="marker-pin">
                <div class="marker-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>
               </div>`,
        iconSize: [30, 40],
        iconAnchor: [15, 40]
    });

    marker = L.marker(initialLocation, { 
        draggable: props.isEditable,
        icon: customIcon,
        riseOnHover: true
    }).addTo(map);

    if (props.isEditable) {
        marker.on('dragstart', () => {
            map.dragging.disable(); // Evita conflito entre drag do marker e do mapa
        });

        marker.on('dragend', () => {
            map.dragging.enable();
            const position = marker.getLatLng();
            form.latitude = position.lat.toFixed(8);
            form.longitude = position.lng.toFixed(8);
            updateAddressFromCoordinates(position.lat, position.lng);
        });
        
        map.on('click', (e) => {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            marker.setLatLng([lat, lng]);
            form.latitude = lat.toFixed(8);
            form.longitude = lng.toFixed(8);
            updateAddressFromCoordinates(lat, lng);
        });
    }

    mapLoaded.value = true;

    // Observer para redimensionamento responsivo
    if (!resizeObserver && window.ResizeObserver) {
        resizeObserver = new ResizeObserver(debounce(() => {
            if (map) map.invalidateSize();
        }, 250));
        resizeObserver.observe(mapContainer.value);
    }
};

const getLocationFromGPS = () => {
    if (!props.isEditable || !supportsGeolocation.value) {
        emit('saved', null, 'Geolocalização não suportada pelo navegador.');
        return;
    }

    if (!isHTTPS.value) {
        emit('saved', null, 'Geolocalização requer HTTPS para funcionar. Por favor, acesse o site via HTTPS.');
        return;
    }

    gpsLoading.value = true;
    
    const options = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 60000 // Cache por 1 minuto
    };

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            form.latitude = lat.toFixed(8);
            form.longitude = lng.toFixed(8);
            
            if (marker && map) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 16, { animate: true, duration: 1.5 });
                updateAddressFromCoordinates(lat, lng);
            }
            
            emit('saved', 'Localização capturada com sucesso!');
            gpsLoading.value = false;
        },
        (error) => {
            gpsLoading.value = false;
            let errorMessage = 'Não foi possível obter a localização: ';
            
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Permissão negada. Verifique as configurações do navegador.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Localização indisponível no momento.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Tempo limite excedido. Tente novamente.';
                    break;
                default:
                    errorMessage += 'Erro desconhecido.';
            }
            
            if (!isHTTPS.value) {
                errorMessage += ' (Requer HTTPS)';
            }
            
            emit('saved', null, errorMessage);
        },
        options
    );
};

// Cleanup otimizado
onMounted(() => {
    // Pequeno delay para garantir que o DOM está pronto
    setTimeout(initMap, 100);
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    if (map) {
        map.remove();
        map = null;
    }
    marker = null;
});

const saveLocalizacao = () => {
    if (!props.isEditable) {
        toast.info('O cadastro está finalizado e não pode ser editado.');
        emit('saved', null, 'O cadastro está finalizado e não pode ser editado.');
        return;
    }

    // Validação em tempo real
    const requiredFields = [
        { field: 'cidade', name: 'Cidade' },
        { field: 'cep', name: 'CEP' },
        { field: 'rua', name: 'Rua' },
        { field: 'bairro', name: 'Bairro' },
        { field: 'latitude', name: 'Latitude' },
        { field: 'longitude', name: 'Longitude' }
    ];

    form.clearErrors();
    const errors = [];
    
    requiredFields.forEach(({ field, name }) => {
        if (!form[field]) {
            errors.push(`${name} é obrigatório`);
            form.setError(field, `${name} é obrigatório`);
        }
    });

    if (errors.length > 0) {
        emit('saved', null, `Preencha todos os campos obrigatórios: ${errors.join(', ')}`);
        return;
    }

    // Limpar formatação do CEP
    form.cep = form.cep ? form.cep.replace(/[^0-9]/g, '') : '';

    form.post(route('unidades.saveLocalizacao'), {
        errorBag: 'saveLocalizacao',
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Dados de Localização salvos com sucesso!');
            emit('saved');
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).flat();
            emit('saved', null, `Erro ao salvar: ${errorMessages.join(', ')}`);
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <!-- Loading Overlay Global -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[1000]">
            <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full mx-4">
                <div class="flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-t-[#bea55a] border-gray-200"></div>
                    <div>
                        <p class="text-lg font-semibold text-gray-900">Carregando dados...</p>
                        <p class="text-sm text-gray-500">Aguarde um momento</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header com instruções -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-200 p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Localização da Unidade</h3>
                    <p class="text-sm text-blue-700 leading-relaxed">
                        Defina a localização exata da unidade. Você pode:
                        <strong>clicar no mapa</strong>, <strong>usar o GPS</strong> ou <strong>preencher o endereço manualmente</strong>.
                    </p>
                    <div v-if="!isHTTPS && supportsGeolocation" class="mt-2 text-xs text-amber-600 bg-amber-50 px-3 py-1 rounded-full inline-block">
                        ⚠️ GPS requer HTTPS para funcionar
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="saveLocalizacao" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Mapa -->
                <div class="space-y-4">
                    <div class="relative">
                        <div class="border-2 border-gray-200 rounded-xl overflow-hidden bg-gray-50 h-[450px] relative group hover:border-gray-300 transition-colors">
                            <div ref="mapContainer" class="w-full h-full"></div>
                            
                            <!-- Loading overlay do mapa -->
                            <div v-if="!mapLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-t-[#bea55a] border-gray-300 mx-auto mb-3"></div>
                                    <p class="text-sm text-gray-600">Carregando mapa...</p>
                                </div>
                            </div>

                            <!-- Overlay de loading para geocoding -->
                            <div v-if="addressLoading" class="absolute top-4 left-4 bg-white bg-opacity-90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="animate-spin rounded-full h-4 w-4 border-2 border-t-[#bea55a] border-gray-300"></div>
                                    <span class="text-xs text-gray-700">Buscando...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botão GPS melhorado -->
                    <div class="flex justify-center">
                        <button
                            type="button"
                            @click="getLocationFromGPS"
                            :disabled="!isEditable || !permissions?.canUpdateTeam || gpsLoading || !isHTTPS"
                            class="group relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-[#bea55a] to-[#d4bf7a] hover:from-[#a89043] hover:to-[#bea55a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl"
                        >
                            <div v-if="gpsLoading" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            {{ gpsLoading ? 'Capturando...' : 'Capturar Via GPS' }}
                        </button>
                    </div>
                </div>

                <!-- Formulário -->
                <div class="space-y-6">
                    <!-- Coordenadas -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            Coordenadas
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="latitude" value="Latitude *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="0.00000001"
                                    class="mt-1 block w-full bg-white border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                    disabled
                                    placeholder="-7.1195000"
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
                                    class="mt-1 block w-full bg-white border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a]"
                                    disabled
                                    placeholder="-34.8450000"
                                />
                                <InputError :message="form.errors.longitude" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            Endereço
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cep" value="CEP *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="cep"
                                    v-model="form.cep"
                                    type="text"
                                    v-imask="{ mask: '00000-000' }"
                                    placeholder="12345-678"
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @input="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.cep" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="cidade" value="Cidade *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="cidade"
                                    v-model="form.cidade"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                    placeholder="Nome da cidade"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @input="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.cidade" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="rua" value="Rua *" class="text-sm font-medium text-gray-700" />
                            <TextInput
                                id="rua"
                                v-model="form.rua"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                placeholder="Nome da rua"
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                                @input="updateCoordinatesFromAddress"
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
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                    placeholder="123"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @input="updateCoordinatesFromAddress"
                                />
                                <InputError :message="form.errors.numero" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="bairro" value="Bairro *" class="text-sm font-medium text-gray-700" />
                                <TextInput
                                    id="bairro"
                                    v-model="form.bairro"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                    placeholder="Nome do bairro"
                                    :disabled="!isEditable || !permissions?.canUpdateTeam"
                                    @input="updateCoordinatesFromAddress"
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
                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-[#bea55a] focus:ring-[#bea55a] transition-colors"
                                placeholder="Apartamento, sala, andar..."
                                :disabled="!isEditable || !permissions?.canUpdateTeam"
                            />
                            <InputError :message="form.errors.complemento" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer com botão de salvar -->
            <div v-if="isEditable && permissions?.canUpdateTeam" class="border-t border-gray-200 mt-8 pt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            <span class="text-red-500 font-medium">*</span> Campos obrigatórios
                        </div>
                        
                        <!-- Indicador de progresso -->
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full" :class="hasRequiredFields ? 'bg-green-400' : 'bg-gray-300'"></div>
                            <span class="text-xs text-gray-500">
                                {{ hasRequiredFields ? 'Completo' : 'Pendente' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <ActionMessage :on="form.recentlySuccessful" class="text-green-600 font-medium">
                            ✓ Salvo com sucesso
                        </ActionMessage>
                        
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            color="gold"
                            class="relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <div v-if="form.processing" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                            <svg v-else-if="props.unidade?.is_draft === true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ form.processing ? 'Salvando...' : (props.unidade?.is_draft === true ? 'Salvar e Continuar' : 'Atualizar Localização') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
/* Estilos personalizados para o marker */
:deep(.custom-marker) {
    background: transparent !important;
    border: none !important;
}

:deep(.marker-pin) {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 40px;
}

:deep(.marker-pin::before) {
    content: '';
    position: absolute;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #bea55a 0%, #d4bf7a 100%);
    border: 3px solid white;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

:deep(.marker-pin:hover::before) {
    transform: rotate(-45deg) scale(1.1);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

:deep(.marker-icon) {
    position: relative;
    z-index: 10;
    color: white;
    transform: rotate(45deg);
    margin-top: -8px;
    margin-left: -1px;
}

/* Estilos para o Leaflet */
:deep(.leaflet-container) {
    font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
    border-radius: 0.75rem;
}

:deep(.leaflet-control-zoom) {
    border: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    border-radius: 0.5rem !important;
}

:deep(.leaflet-control-zoom a) {
    background-color: white !important;
    color: #374151 !important;
    border: none !important;
    border-radius: 0.375rem !important;
    width: 32px !important;
    height: 32px !important;
    line-height: 30px !important;
    transition: all 0.2s ease !important;
}

:deep(.leaflet-control-zoom a:hover) {
    background-color: #f9fafb !important;
    color: #bea55a !important;
    transform: scale(1.05);
}

:deep(.leaflet-control-zoom a:first-child) {
    border-bottom: 1px solid #e5e7eb !important;
}

/* Responsividade */
@media (max-width: 640px) {
    :deep(.leaflet-control-zoom) {
        margin-bottom: 60px !important;
        margin-right: 10px !important;
    }
    
    :deep(.marker-pin) {
        width: 25px;
        height: 35px;
    }
    
    :deep(.marker-pin::before) {
        width: 25px;
        height: 25px;
    }
}

/* Animações suaves */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Scrollbar personalizada para navegadores webkit */
:deep(.leaflet-container) {
    scrollbar-width: thin;
    scrollbar-color: #bea55a #f1f5f9;
}

:deep(.leaflet-container::-webkit-scrollbar) {
    width: 6px;
    height: 6px;
}

:deep(.leaflet-container::-webkit-scrollbar-track) {
    background: #f1f5f9;
    border-radius: 3px;
}

:deep(.leaflet-container::-webkit-scrollbar-thumb) {
    background: #bea55a;
    border-radius: 3px;
}

:deep(.leaflet-container::-webkit-scrollbar-thumb:hover) {
    background: #a89043;
}

/* Melhorar foco nos inputs */
:deep(.border-gray-300:focus) {
    border-color: #bea55a !important;
    box-shadow: 0 0 0 3px rgba(190, 165, 90, 0.1) !important;
}

/* Estilos para loading states */
.loading-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* Transições suaves para mudanças de estado */
.smooth-transition {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Melhorar aparência dos botões desabilitados */
button:disabled {
    cursor: not-allowed;
    filter: grayscale(0.3);
}

/* Indicador visual para campos obrigatórios */
.required-field {
    position: relative;
}

.required-field::after {
    content: '*';
    color: #ef4444;
    margin-left: 4px;
    font-weight: 600;
}
</style>