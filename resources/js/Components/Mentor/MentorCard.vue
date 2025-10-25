<script setup>
defineProps({
  mentor: Object,
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:shadow-lg"
  >
    <div
      v-if="mentor.availability"
      class="absolute top-3 right-3 rounded-full px-2 py-1 text-xs"
      :class="{
        'bg-green-100 text-green-700': mentor.availability === 'today',
        'bg-yellow-100 text-yellow-700': mentor.availability === 'tomorrow',
        'bg-red-100 text-red-700': mentor.availability === 'booked',
      }"
    >
      {{ mentor.availabilityLabel }}
    </div>

    <img
      class="h-48 w-full object-cover"
      :src="mentor.image"
      :alt="mentor.name"
    />

    <div class="p-4">
      <div class="flex justify-between">
        <h3 class="text-lg font-semibold">{{ mentor.name }}</h3>
        <p class="font-bold text-blue-600">${{ mentor.price }}/hr</p>
      </div>
      <p class="text-sm text-gray-600">{{ mentor.title }}</p>

      <div class="mt-3 flex flex-wrap gap-2">
        <span
          v-for="tag in mentor.tags"
          :key="tag"
          class="rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-700"
          >{{ tag }}</span
        >
      </div>

      <div class="mt-3 flex items-center text-sm">
        <span class="text-yellow-400">{{
          '★'.repeat(Math.min(Math.max(mentor.rating, 0), 5))
        }}</span>
        <span class="text-gray-300">{{
          '★'.repeat(5 - Math.min(Math.max(mentor.rating, 0), 5))
        }}</span>
        <span class="ml-2"
          >{{ mentor.rating }}.0 ({{ mentor.reviews }} reviews)</span
        >
      </div>

      <div class="mt-2 text-sm text-gray-600">
        {{ mentor.experience }}+ years experience
      </div>
    </div>
  </div>
</template>
