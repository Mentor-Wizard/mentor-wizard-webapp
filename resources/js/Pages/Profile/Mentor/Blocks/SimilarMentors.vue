<script setup>

import {StarIcon} from "@heroicons/vue/20/solid/index.js";

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="py-4 grid grid-cols-4 gap-4">
    <div v-for="mentor in data" :key="mentor.id" class="rounded-2xl bg-white overflow-hidden">
      <div class="bg-white rounded-lg overflow-hidden shadow-sm max-w-xs mx-auto">
          <div class="w-full h-48 bg-gray-400 flex items-center justify-center">
              <img :src="mentor.avatar"
                   alt="James Wilson"
                   class="w-full h-full object-cover">
          </div>
          <div class="p-4">
              <div class="flex justify-between items-start mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ mentor.name }}</h3>
                  <span v-if="mentor.rate" class="text-lg font-semibold text-blue-600">{{ mentor.currency }}{{ mentor.rate  }}/hr</span>
              </div>
              <p class="text-gray-600 text-sm mb-3">{{ mentor.title }}</p>

              <div class="flex items-center mb-4">
                  <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating"
                            :class="[3 > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0']"
                            aria-hidden="true"/>
                  <span class="text-sm text-gray-600">{{ mentor.rating }} ({{ mentor.reviews }} reviews)</span>
              </div>

              <a :href="mentor.slug"
                 class="w-full py-2 px-4 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 transition-colors duration-200 flex justify-center items-center">
                 View Profile
              </a>
          </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>