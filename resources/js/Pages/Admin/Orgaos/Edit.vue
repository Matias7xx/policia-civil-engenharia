<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import { Link } from "@inertiajs/vue3";
import { BuildingOfficeIcon, PencilIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    orgao: Object,
});

const form = useForm({
    nome: props.orgao.nome,
    status: props.orgao.status,
});

const updateOrgao = () => {
    form.put(route("admin.orgaos.update", props.orgao.id), {
        errorBag: "updateOrgao",
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Editar Órgão">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/dashboard">
                    <img
                        src="/images/logo-pc-branca.png"
                        alt="Logo da Polícia Civil"
                        class="h-14 w-auto"
                    />
                </Link>
                <div class="border-l border-white h-8"></div>
                <span class="text-white text-xl font-semibold pl-2"
                    >ENGENHARIA - Editar Órgão</span
                >
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="updateOrgao">
                    <template #title>
                        <div class="flex items-center">
                            <BuildingOfficeIcon
                                class="h-6 w-6 text-[#bea55a] mr-2"
                            />
                            Editar Órgão
                        </div>
                    </template>

                    <template #description>
                        Atualize as informações do órgão no sistema.
                    </template>

                    <template #form>
                        <!-- Informações do Órgão -->
                        <div class="col-span-6">
                            <h3
                                class="text-lg font-medium text-gray-900 flex items-center"
                            >
                                <BuildingOfficeIcon
                                    class="h-5 w-5 text-gray-400 mr-2"
                                />
                                Informações do Órgão
                            </h3>
                        </div>

                        <!-- Nome -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel
                                for="nome"
                                value="Nome"
                                class="flex items-center"
                            >
                                <BuildingOfficeIcon
                                    class="h-5 w-5 text-gray-400 mr-2"
                                />
                                Nome
                            </InputLabel>
                            <TextInput
                                id="nome"
                                v-model="form.nome"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                                required
                                autofocus
                            />
                            <InputError
                                :message="form.errors.nome"
                                class="mt-2"
                            />
                        </div>

                        <!-- Status -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel
                                for="status"
                                value="Status"
                                class="flex items-center"
                            >
                                <BuildingOfficeIcon
                                    class="h-5 w-5 text-gray-400 mr-2"
                                />
                                Status
                            </InputLabel>
                            <SelectInput
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base"
                            >
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </SelectInput>
                            <InputError
                                :message="form.errors.status"
                                class="mt-2"
                            />
                        </div>
                    </template>

                    <template #actions>
                        <div
                            class="mt-8 flex flex-col sm:flex-row sm:space-x-4 space-y-3 sm:space-y-0"
                        >
                            <ActionMessage
                                :on="form.recentlySuccessful"
                                class="me-3"
                            >
                                Órgão atualizado com sucesso.
                            </ActionMessage>

                            <Link
                                :href="route('admin.orgaos.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="h-5 w-5 mr-2 -ml-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    ></path>
                                </svg>
                                Voltar
                            </Link>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm uppercase tracking-widest focus:outline-none focus:ring-2 transition ease-in-out duration-150"
                                color="gold"
                            >
                                <PencilIcon class="h-5 w-5 mr-2 -ml-1" />
                                Atualizar Órgão
                            </PrimaryButton>
                        </div>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>
