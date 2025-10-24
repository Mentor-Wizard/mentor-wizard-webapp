<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  expertiseOptions: Array,
  experienceOptions: Array,
  ratings: Array,
  availabilityOptions: Array,
});

const expertiseSearch = ref('');
const selectedExpertise = ref([]);
const selectedExperience = ref([]);
const priceMin = ref(0);
const priceMax = ref(200);
const selectedRatings = ref([]);
const selectedAvailability = ref([]);

const filteredExpertise = computed(() =>
  props.expertiseOptions.filter((option) =>
    option.label.toLowerCase().includes(expertiseSearch.value.toLowerCase()),
  ),
);

const clearFilters = () => {
  expertiseSearch.value = '';
  selectedExpertise.value = [];
  selectedExperience.value = [];
  priceMin.value = 0;
  priceMax.value = 200;
  selectedRatings.value = [];
  selectedAvailability.value = [];
};
</script>

<template>
  <aside class="hidden space-y-8 lg:block">
    <form class="space-y-8">
      <div class="pt-4">
        <h3 class="border-b border-gray-200 pb-5 font-medium text-gray-900">
          Expertise Area
        </h3>

        <div class="relative mt-4">
          <input
            type="text"
            v-model="expertiseSearch"
            placeholder="Search expertise..."
            class="block w-full rounded-md border border-gray-300 bg-white px-10 py-2 text-sm font-normal focus:outline-none"
            style="color: #adaebc"
          />
          <div
            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-gray-400"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
              />
            </svg>
          </div>
        </div>

        <div class="mt-3 space-y-2">
          <label
            v-for="(option, i) in filteredExpertise"
            :key="i"
            class="flex items-center gap-2 text-base text-gray-700"
          >
            <input
              type="checkbox"
              v-model="selectedExpertise"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>
        </div>
      </div>

      <div class="pt-4">
        <h3 class="font-medium text-gray-900">Experience Level</h3>
        <div class="mt-3 space-y-2">
          <label
            v-for="(option, i) in experienceOptions"
            :key="i"
            class="flex items-center gap-2 text-base text-gray-700"
          >
            <input
              type="checkbox"
              v-model="selectedExperience"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>
        </div>
      </div>

      <div class="pt-4">
        <h3 class="font-medium text-gray-900">Price Range (per hour)</h3>

        <div class="mt-4">
          <div class="mb-2 flex justify-between text-sm text-gray-500">
            <span>${{ priceMin }}</span>
            <span>${{ priceMax }}+</span>
          </div>

          <div class="relative h-2 rounded-full bg-gray-200">
            <div
              class="absolute h-2 rounded-full bg-blue-600"
              :style="{
                left: ((priceMin - 0) / (200 - 0)) * 100 + '%',
                right: 100 - ((priceMax - 0) / (200 - 0)) * 100 + '%',
              }"
            ></div>

            <input
              type="range"
              v-model="priceMin"
              min="0"
              max="200"
              step="5"
              class="pointer-events-none absolute h-2 w-full appearance-none bg-transparent"
              @input="handleMinChange"
            />
            <input
              type="range"
              v-model="priceMax"
              min="0"
              max="200"
              step="5"
              class="pointer-events-none absolute h-2 w-full appearance-none bg-transparent"
              @input="handleMaxChange"
            />
          </div>
        </div>
      </div>

      <div class="pt-4">
        <h3 class="font-medium text-gray-900">Rating</h3>
        <div class="mt-3 space-y-2">
          <label
            v-for="r in ratings"
            :key="r.value"
            class="flex items-center gap-2 text-base text-gray-700"
          >
            <input
              type="checkbox"
              v-model="selectedRatings"
              :value="r.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            <span>
              <span v-for="i in r.value" :key="i" class="text-yellow-400"
                >★</span
              >
              <span
                v-for="i in 5 - r.value"
                :key="'e' + i"
                class="text-gray-300"
                >★</span
              >
              {{ r.label }}
            </span>
          </label>
        </div>
      </div>

      <div class="pt-4">
        <h3 class="font-medium text-gray-900">Availability</h3>
        <div class="mt-3 space-y-2">
          <label
            v-for="(option, i) in availabilityOptions"
            :key="i"
            class="flex items-center gap-2 text-base text-gray-700"
          >
            <input
              type="checkbox"
              v-model="selectedAvailability"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>
        </div>
      </div>

      <div class="pt-1">
        <button
          type="button"
          @click="clearFilters"
          class="w-full rounded-lg border border-blue-600 px-3 py-2 font-medium text-blue-600 hover:underline"
        >
          Clear all filters
        </button>
      </div>
    </form>
  </aside>
</template>
<style>
input[type='range'] {
  -webkit-appearance: none;
}
input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
}
input[type='range']::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
}
</style>
