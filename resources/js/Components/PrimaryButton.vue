<script setup>
import { computed } from "vue";

const props = defineProps({
    type: {
        type: String,
        default: "submit",
    },
    color: {
        type: String,
        default: "default", // valores possíveis: 'default', 'gold'
    },
});

const buttonClass = computed(() => {
    const baseClasses =
        "inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150";

    if (props.color === "gold") {
        return `${baseClasses} bg-[#bea55a] hover:bg-[#d4bf7a] focus:bg-[#d4bf7a] active:bg-[#a89043] focus:ring-[#bea55a]`;
    }

    return `${baseClasses} bg-gray-800 hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-indigo-500`;
});
</script>

<template>
    <button :type="type" :class="buttonClass">
        <slot />
    </button>
</template>
