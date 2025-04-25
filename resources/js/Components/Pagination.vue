<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, i) in links" :key="i">
                <div v-if="link.url === null" 
                     class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400" 
                     :class="{ 'ml-auto': i === links.length - 1 }"
                     v-html="link.label" />

                <Link v-else
                      class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-[#d4bf7a] focus:border-[#bea55a] focus:text-black"
                      :class="{ 'bg-[#bea55a] text-white': link.active, 'ml-auto': i === links.length - 1 }"
                      :href="link.url"
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>