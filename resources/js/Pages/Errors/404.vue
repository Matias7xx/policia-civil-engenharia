<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    message: String,
    status: Number,
});
</script>

<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8"
    >
        <Head title="404 - Página Não Encontrada" />

        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                    Página Não Encontrada
                </h2>

                <p class="text-gray-600 mb-8 text-center max-w-sm mx-auto">
                    {{ "A página que você está procurando não existe." }}
                </p>

                <div class="space-y-4">
                    <Link
                        href="/"
                        class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a] transition-colors"
                    >
                        ← Voltar ao Início
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
