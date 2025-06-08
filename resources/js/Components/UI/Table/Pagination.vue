<template>
    <div class="justify-center items-center">
        <template v-for="link in pagination" :key="link.label">
            <Link
                preserve-scroll
                :href="link.url??''"
                v-html="link.label"
                :class="{ 'bg-gray-200':link.active, '!test-gray-300': !link.url }"
                class="inline-flex items-center justify-center border-t-2 border-transparent px-4 py-2 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
            />
        </template>
    </div>
</template>

<script setup>
import {Link} from "@inertiajs/vue3";

defineProps({
    pagination: Object,
    required: true
})
</script>
