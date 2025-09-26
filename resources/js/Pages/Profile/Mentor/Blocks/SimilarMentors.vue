<script setup>
import { StarIcon } from '@heroicons/vue/20/solid/index.js';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="grid grid-cols-4 gap-4 py-4">
    <div
      v-for="mentor in data"
      :key="mentor.id"
      class="overflow-hidden rounded-2xl bg-white"
    >
      <div
        class="mx-auto max-w-xs overflow-hidden rounded-lg bg-white shadow-sm"
      >
        <div class="flex h-48 w-full items-center justify-center bg-gray-400">
          <img
            :src="mentor.avatar"
            alt="James Wilson"
            class="h-full w-full object-cover"
          />
        </div>
        <div class="p-4">
          <div class="mb-2 flex items-start justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ mentor.name }}
            </h3>
            <span v-if="mentor.rate" class="text-lg font-semibold text-blue-600"
              >{{ mentor.currency }}{{ mentor.rate }}/hr</span
            >
          </div>
          <p class="mb-3 text-sm text-gray-600">{{ mentor.title }}</p>

          <div class="mb-4 flex items-center">
            <StarIcon
              v-for="rating in [0, 1, 2, 3, 4]"
              :key="rating"
              :class="[
                3 > rating ? 'text-yellow-400' : 'text-gray-200',
                'size-5 shrink-0',
              ]"
              aria-hidden="true"
            />
            <span class="text-sm text-gray-600"
              >{{ mentor.rating }} ({{ mentor.reviews }} reviews)</span
            >
          </div>

          <a
            :href="mentor.slug"
            class="flex w-full items-center justify-center rounded-md border border-blue-600 px-4 py-2 text-blue-600 transition-colors duration-200 hover:bg-blue-50"
          >
            View Profile
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
