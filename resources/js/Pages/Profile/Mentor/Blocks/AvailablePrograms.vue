<script setup>
import {Link} from "@inertiajs/vue3";

defineProps({
  data: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="mt-2">
    <h3 class="text-lg font-semibold text-gray-900">Available Programs</h3>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div
      v-for="program in data"
      :key="program.id"
      class="flex h-full max-w-sm flex-col rounded-lg border border-gray-300 bg-white p-6 shadow-sm"
    >
      <div class="mb-3 flex items-start justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ program.name }}</h3>
        <span class="text-lg font-semibold text-blue-600"
          >{{ program.currency }}{{ program.cost }}</span
        >
      </div>

      <p class="mb-4 text-sm leading-relaxed text-gray-600">
        {{ program.description }}
      </p>

      <div class="mb-4 flex items-center text-sm text-gray-600">
        <svg
          class="mr-2 h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12,6 12,12 16,14"></polyline>
        </svg>
        60 minutes
      </div>

      <div class="mb-6 space-y-3">
        <div
          v-for="block in program.blocks"
          :key="block.id"
          class="flex items-start"
        >
          <svg
            class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-green-500"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <span class="text-sm text-gray-700">{{ block.name }}</span>
        </div>
      </div>

      <Link
        :href="route('pages.mentor.program.book', program.slug)"
        class="mt-auto block w-full rounded-lg bg-blue-600 px-4 py-3 text-center font-medium text-white transition-colors duration-200 hover:bg-blue-700"
      >
        Book Session
      </Link>
    </div>
  </div>
</template>
