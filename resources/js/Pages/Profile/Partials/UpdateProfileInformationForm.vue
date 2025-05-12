<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    matricula: props.user.matricula,
    cargo: props.user.cargo,
    telefone: props.user.telefone,
});

const verificationLinkSent = ref(null);

const updateProfileInformation = () => {

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Informação do Usuário
        </template>

        <template #form>
            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nome" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full bg-gray-100"
                    required
                    autocomplete="name"
                    disabled
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="bg-gray-100 mt-1 block w-full"
                    required
                    autocomplete="username"
                    disabled
                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>

            <!-- Matrícula -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="matricula" value="Matrícula" />
                <TextInput
                    id="matricula"
                    v-model="form.matricula"
                    type="text"
                    class="bg-gray-100 mt-1 block w-full"
                    required
                    autocomplete="matricula"
                    disabled
                />
                <InputError :message="form.errors.matricula" class="mt-2" />
            </div>

            <!-- Cargo -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="cargo" value="Cargo" />
                <TextInput
                    id="cargo"
                    v-model="form.cargo"
                    type="text"
                    class="bg-gray-100 mt-1 block w-full"
                    required
                    autocomplete="cargo"
                    disabled
                />
                <InputError :message="form.errors.cargo" class="mt-2" />
            </div>

            <!-- Telefone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="telefone" value="Telefone" />
                <TextInput
                    id="telefone"
                    v-model="form.telefone"
                    type="text"
                    class="bg-gray-100 mt-1 block w-full"
                    required
                    autocomplete="telefone"
                    disabled
                />
                <InputError :message="form.errors.telefone" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo com sucesso!
            </ActionMessage>

            <!-- <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Salvar
            </PrimaryButton> -->
        </template>
    </FormSection>
</template>
