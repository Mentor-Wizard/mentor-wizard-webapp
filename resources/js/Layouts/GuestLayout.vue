<script setup>
import ApplicationLogo from '@/Components/UI/Logo/ApplicationLogo.vue';
import {Link} from '@inertiajs/vue3';
import MentorsList from "@/Components/UI/Table/MentorsList.vue";
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-18 w-18 fill-current text-gray-500"/>
            </Link>
        </div>
        <div class="justify-center mt-4 items-center">
            <MentorsList></MentorsList>
        </div>

        <div
            class="mt-4 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot/>
        </div>
    </div>
</template>
