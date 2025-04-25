<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Link } from '@inertiajs/vue3';

// Ícones do Heroicons
import { UserIcon, IdentificationIcon, EnvelopeIcon, BriefcaseIcon, PhoneIcon, LockClosedIcon, BuildingOfficeIcon, ShieldCheckIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    teams: Array,
    roles: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    matricula: '',
    cargo: '',
    telefone: '',
    team_id: '',
    role: '',
});

const createUser = () => {
    form.post(route('admin.users.store'), {
        errorBag: 'createUser',
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Criar Novo Usuário">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img src="/images/logo-pc-branca.png" alt="Logo da Polícia Civil" class="h-14 w-auto" />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2">ENGENHARIA - Criar Novo Usuário</span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="createUser">
                    <!-- Cabeçalho da Seção -->
                    <template #title>
                        <div class="flex items-center">
                            <UserIcon class="h-6 w-6 text-[#bea55a] mr-2" />
                            Criar Novo Usuário
                        </div>
                    </template>

                    <template #description>
                        Crie um novo usuário no sistema. Defina suas informações básicas, lotação e permissões.
                    </template>

                    <template #form>
                        <!-- Seção: Informações do Usuário -->
                        <div class="col-span-6">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <UserIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Informações do Usuário
                            </h3>
                        </div>

                        <!-- Nome -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="name" value="Nome Completo" class="flex items-center">
                                <UserIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Nome Completo
                            </InputLabel>
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Matrícula -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="matricula" value="Matrícula" class="flex items-center">
                                <IdentificationIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Matrícula
                            </InputLabel>
                            <TextInput
                                id="matricula"
                                v-model="form.matricula"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                            />
                            <InputError :message="form.errors.matricula" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="email" value="E-mail" class="flex items-center">
                                <EnvelopeIcon class="h-5 w-5 text-gray-400 mr-2" />
                                E-mail
                            </InputLabel>
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Cargo -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="cargo" value="Cargo (opcional)" class="flex items-center">
                                <BriefcaseIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Cargo (opcional)
                            </InputLabel>
                            <TextInput
                                id="cargo"
                                v-model="form.cargo"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                            />
                            <InputError :message="form.errors.cargo" class="mt-2" />
                        </div>

                        <!-- Telefone -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="telefone" value="Telefone (opcional)" class="flex items-center">
                                <PhoneIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Telefone (opcional)
                            </InputLabel>
                            <TextInput
                                id="telefone"
                                v-model="form.telefone"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                            />
                            <InputError :message="form.errors.telefone" class="mt-2" />
                        </div>

                        <!-- Senha -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="password" value="Senha" class="flex items-center">
                                <LockClosedIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Senha
                            </InputLabel>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <!-- Confirmar Senha -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="password_confirmation" value="Confirmar Senha" class="flex items-center">
                                <LockClosedIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Confirmar Senha
                            </InputLabel>
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>

                        <!-- Seção: Lotação e Permissões -->
                        <div class="col-span-6 pt-4 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <BuildingOfficeIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Lotação e Permissões
                            </h3>
                        </div>

                        <!-- Unidade/Team -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="team_id" value="Unidade de Lotação" class="flex items-center">
                                <BuildingOfficeIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Unidade de Lotação
                            </InputLabel>
                            <SelectInput
                                id="team_id"
                                v-model="form.team_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                            >
                                <option value="">Selecione uma unidade</option>
                                <option 
                                    v-for="team in teams" 
                                    :key="team.id" 
                                    :value="team.id"
                                >
                                    {{ team.name }}
                                </option>
                            </SelectInput>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="role" value="Função do Usuário" class="flex items-center">
                                <ShieldCheckIcon class="h-5 w-5 text-gray-400 mr-2" />
                                Função do Usuário
                            </InputLabel>
                            <SelectInput
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                            >
                                <option value="">Selecione uma função</option>
                                <option 
                                    v-for="role in roles" 
                                    :key="role.key" 
                                    :value="role.key"
                                >
                                    {{ role.name }}
                                </option>
                            </SelectInput>
                            <InputError :message="form.errors.role" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="form.recentlySuccessful" class="me-3">
                            Usuário criado com sucesso.
                        </ActionMessage>

                        <PrimaryButton 
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 bg-[#bea55a] border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-[#d4bf7a] focus:outline-none focus:ring-2 focus:ring-indigo-300 transition ease-in-out duration-150"
                        >
                            <PlusIcon class="h-5 w-5 mr-2 -ml-1" />
                            Criar Usuário
                        </PrimaryButton>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>