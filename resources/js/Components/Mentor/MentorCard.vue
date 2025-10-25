<script setup>
defineProps({
  mentor: Object,
  view: {
    type: String,
    default: 'grid',
  },
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:shadow-lg"
  >
    <div
      v-if="mentor.availability"
      class="absolute top-3 right-3 z-10 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
      :class="{
        'bg-green-100 text-green-800': mentor.availability === 'today',
        'bg-yellow-100 text-yellow-800': mentor.availability === 'tomorrow',
        'bg-red-100 text-red-800': mentor.availability === 'booked',
      }"
    >
      <span
        class="h-2 w-2 rounded-full"
        :class="{
          'bg-green-500': mentor.availability === 'today',
          'bg-yellow-500': mentor.availability === 'tomorrow',
          'bg-red-500': mentor.availability === 'booked',
        }"
      ></span>
      {{ mentor.availabilityLabel }}
    </div>

    <div class="h-48 w-full overflow-hidden">
      <img
        class="h-full w-full object-cover"
        :src="mentor.image"
        :alt="mentor.name"
      />
    </div>

    <div class="space-y-3 p-4">
      <div class="flex items-start justify-between gap-2">
        <h3 class="text-lg leading-tight font-semibold text-gray-900">
          {{ mentor.name }}
        </h3>
        <p class="font-bold whitespace-nowrap text-blue-600">
          ${{ mentor.price }}/hr
        </p>
      </div>

      <p class="text-sm leading-relaxed text-gray-600">{{ mentor.title }}</p>

      <div class="flex flex-wrap gap-2">
        <span
          v-for="tag in mentor.tags"
          :key="tag"
          class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
        >
          {{ tag }}
        </span>
      </div>

      <div class="flex items-center text-sm">
        <span class="text-yellow-400">
          {{ '★'.repeat(Math.min(Math.max(mentor.rating, 0), 5)) }}
        </span>
        <span class="text-gray-300">
          {{ '★'.repeat(5 - Math.min(Math.max(mentor.rating, 0), 5)) }}
        </span>
        <span class="ml-2 text-gray-700">
          {{ mentor.rating }}.0 ({{ mentor.reviews }} reviews)
        </span>
      </div>

      <div class="text-sm text-gray-600">
        {{ mentor.experience }}+ years experience
      </div>

      <button
        class="w-full rounded-lg bg-blue-600 py-2.5 text-base font-medium text-white transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
      >
        View Profile
      </button>
    </div>
  </div>
</template>
