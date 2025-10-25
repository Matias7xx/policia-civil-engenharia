<script setup>
import { ref, watchEffect, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const show = ref(true);
const style = ref("success");
const message = ref("");

watchEffect(() => {
    style.value = page.props.jetstream.flash?.bannerStyle || "success";
    message.value = page.props.jetstream.flash?.banner || "";
    show.value = true;

    if (message.value) {
        setTimeout(() => {
            show.value = false;
        }, 5000);
    }
});

onMounted(() => {
    if (message.value) {
        const banner = document.querySelector('[role="alert"]');
        if (banner) banner.focus();
    }
});
</script>

<template>
    <div>
        <transition name="slide-fade">
            <div
                v-if="show && message"
                :class="{
                    'bg-[#2e7d32]': style === 'success',
                    'bg-red-800': style === 'danger',
                    'bg-yellow-600': style === 'warning',
                    'bg-gray-600': style === 'info',
                }"
                role="alert"
                tabindex="-1"
            >
                <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between flex-wrap">
                        <div class="w-0 flex-1 flex items-center min-w-0">
                            <span
                                class="flex p-2 rounded-lg"
                                :class="{
                                    'bg-[#1a1a1a]': style === 'success',
                                    'bg-red-900': style === 'danger',
                                    'bg-yellow-700': style === 'warning',
                                    'bg-gray-700': style === 'info',
                                }"
                            >
                                <svg
                                    v-if="style === 'success'"
                                    class="size-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <svg
                                    v-if="style === 'danger'"
                                    class="size-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                                    />
                                </svg>
                                <svg
                                    v-if="style === 'warning'"
                                    class="size-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                                    />
                                </svg>
                                <svg
                                    v-if="style === 'info'"
                                    class="size-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span class="sr-only">{{
                                    style === "success"
                                        ? "Sucesso"
                                        : style === "danger"
                                          ? "Erro"
                                          : style === "warning"
                                            ? "Aviso"
                                            : "Informação"
                                }}</span>
                            </span>
                            <p
                                class="ms-3 font-medium text-sm text-white truncate font-roboto"
                            >
                                {{ message }}
                            </p>
                        </div>
                        <div class="shrink-0 sm:ms-3">
                            <button
                                type="button"
                                class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition"
                                :class="{
                                    'hover:bg-[#1b5e20] focus:bg-[#1b5e20]':
                                        style === 'success',
                                    'hover:bg-red-900 focus:bg-red-900':
                                        style === 'danger',
                                    'hover:bg-yellow-700 focus:bg-yellow-700':
                                        style === 'warning',
                                    'hover:bg-gray-700 focus:bg-gray-700':
                                        style === 'info',
                                }"
                                aria-label="Fechar notificação"
                                @click.prevent="show = false"
                            >
                                <svg
                                    class="size-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                                <span class="sr-only">Fechar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.font-roboto {
    font-family: "Roboto", sans-serif;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.5s ease-in;
}

.slide-fade-enter-from {
    transform: translateX(-20px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
