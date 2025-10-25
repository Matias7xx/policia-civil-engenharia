<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
    message: String,
});
</script>

<template>
    <AppLayout title="Erro no Servidor">
        <Head title="Erro no Servidor" />

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-4 py-8 sm:px-10 flex flex-col items-center">
                        <div class="bg-red-100 p-4 rounded-full">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-16 w-16 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z"
                                />
                            </svg>
                        </div>

                        <h1 class="mt-6 text-2xl font-semibold text-gray-900">
                            Erro no Servidor
                        </h1>

                        <p class="mt-3 text-gray-600 text-center">
                            {{
                                message ||
                                "Ocorreu um erro interno no servidor. Por favor, tente novamente mais tarde."
                            }}
                        </p>

                        <div class="mt-8">
                            <a
                                href="/dashboard"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#bea55a] hover:bg-[#d4bf7a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a]"
                            >
                                Voltar para página inicial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
