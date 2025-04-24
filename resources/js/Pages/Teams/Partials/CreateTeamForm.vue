<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const page = usePage();
const form = useForm({
    nome: '',
    codigo: '',
    tipo_estrutural: '',
    cep: '',
    rua: '',
    numero: '',
    bairro: '',
    complemento: '',
});

const submitForm = () => {
    const teamId = page.props.auth.user.current_team_id;

    // Enviar para a rota de unidades
    form.post(route('unidades.store', { team_id: teamId }), {
        errorBag: 'createUnidade',
        preserveScroll: false, //Permite que a página faça redirecionamento
        onSuccess: () => {
            //O redirecionamento será tratado pelo controller
        },
    });
};

</script>

<template>
    <FormSection @submitted="submitForm">
        <template #title>
            Dados da Unidade Policial
        </template>

        <template #description>
            Cadastre uma nova unidade policial no sistema para o censo anual da engenharia.
        </template>

        <template #form>
            <div class="col-span-6">
                <InputLabel value="Responsável pelo Cadastro" />

                <div class="flex items-center mt-2">
                    <img class="object-cover size-12 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">

                    <div class="ms-4 leading-tight">
                        <div class="text-gray-900">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-gray-700">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="nome" value="Nome da Unidade" />
                <TextInput
                    id="nome"
                    v-model="form.nome"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.nome" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="codigo" value="Código da Unidade" />
                <TextInput
                    id="codigo"
                    v-model="form.codigo"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.codigo" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="tipo_estrutural" value="Tipo Estrutural" />
                <TextInput
                    id="tipo_estrutural"
                    v-model="form.tipo_estrutural"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.tipo_estrutural" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="cep" value="CEP" />
                <TextInput
                    id="cep"
                    v-model="form.cep"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.cep" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="rua" value="Rua" />
                <TextInput
                    id="rua"
                    v-model="form.rua"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.rua" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="numero" value="Número" />
                <TextInput
                    id="numero"
                    v-model="form.numero"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.numero" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="bairro" value="Bairro" />
                <TextInput
                    id="bairro"
                    v-model="form.bairro"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.bairro" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="complemento" value="Complemento" />
                <TextInput
                    id="complemento"
                    v-model="form.complemento"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.complemento" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Cadastrar Unidade
            </PrimaryButton>
        </template>
    </FormSection>
</template>