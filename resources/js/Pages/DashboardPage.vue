<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import MainPageText from "@/Components/MainPageText.vue";
import MentorsList from "@/Components/UI/Table/MentorsList.vue";

import GlobeAltIcon from "@heroicons/vue/24/outline/GlobeAltIcon";
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <MainPageText title="Dashboard"/>
    </template>

    <div class="py-12">
        <GlobeAltIcon />
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
          <div class="p-6 text-gray-900">You're logged in!</div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
