<script setup>
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { computed } from "vue";

const props = defineProps({
    team: Object,
    permissions: Object,
});

const teamName = computed(() => props.team?.name || "");

const form = useForm({
    name: teamName.value,
});

const updateTeamName = () => {
    form.put(route("teams.update", props.team), {
        errorBag: "updateTeamName",
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title> Nome da Equipe </template>

        <template #description>
            O nome da equipe e as informações do proprietário.
        </template>

        <template #form>
            <!-- Team Owner Information -->
            <div class="col-span-6">
                <InputLabel value="Proprietário da Equipe" />

                <div v-if="team" class="flex items-center mt-2">
                    <img
                        class="size-12 rounded-full object-cover"
                        :src="
                            team.owner?.profile_photo_url ||
                            '/images/default-avatar.png'
                        "
                        :alt="team.owner?.name || 'Carregando...'"
                    />

                    <div class="ms-4 leading-tight">
                        <div class="text-gray-900">
                            {{ team.owner?.name || "Carregando..." }}
                        </div>
                        <div class="text-gray-700 text-sm">
                            {{ team.owner?.email || "Carregando..." }}
                        </div>
                    </div>
                </div>
                <div v-else class="mt-2 text-gray-600">
                    Carregando informações do proprietário...
                </div>
            </div>

            <!-- Team Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nome da Equipe" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions?.canUpdateTeam"
                />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template v-if="permissions?.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>

            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Salvar
            </PrimaryButton>
        </template>
    </FormSection>
</template>
