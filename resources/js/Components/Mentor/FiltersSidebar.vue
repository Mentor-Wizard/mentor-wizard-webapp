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
  <aside class="hidden space-y-6 lg:block">
    <form class="space-y-6">
      <div class="rounded-lg border border-gray-200 bg-white">
        <h3
          class="border-b border-gray-200 px-4 py-3.5 text-base font-semibold text-gray-900"
        >
          Expertise Area
        </h3>

        <div class="p-4">
          <div class="relative">
            <input
              type="text"
              v-model="expertiseSearch"
              placeholder="Search expertise..."
              class="block w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pr-4 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none"
            />
            <div
              class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-gray-400"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                />
              </svg>
            </div>
          </div>

          <div class="mt-4 space-y-3">
            <label
              v-for="(option, i) in filteredExpertise"
              :key="i"
              class="flex cursor-pointer items-center gap-3 text-base text-gray-700"
            >
              <input
                v-model="selectedExpertise"
                type="checkbox"
                :value="option.value"
                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
              />
              {{ option.label }}
            </label>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <h3
          class="border-b border-gray-200 px-4 py-3.5 text-base font-semibold text-gray-900"
        >
          Experience Level
        </h3>

        <div class="p-4">
          <div class="space-y-3">
            <label
              v-for="(option, i) in experienceOptions"
              :key="i"
              class="flex cursor-pointer items-center gap-3 text-base text-gray-700"
            >
              <input
                v-model="selectedExperience"
                type="checkbox"
                :value="option.value"
                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
              />
              {{ option.label }}
            </label>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <h3
          class="border-b border-gray-200 px-4 py-3.5 text-base font-semibold text-gray-900"
        >
          Price Range (per hour)
        </h3>

        <div class="p-4">
          <div
            class="mb-4 flex justify-between text-base font-medium text-gray-700"
          >
            <span>${{ priceMin }}</span>
            <span>${{ priceMax }}+</span>
          </div>

          <div class="relative h-2">
            <div class="absolute h-2 w-full rounded-full bg-gray-200"></div>
            <div
              class="absolute h-2 rounded-full bg-blue-600"
              :style="{
                left: (priceMin / 200) * 100 + '%',
                width: ((priceMax - priceMin) / 200) * 100 + '%',
              }"
            ></div>

            <input
              v-model.number="priceMin"
              type="range"
              min="0"
              max="200"
              step="5"
              class="range-input"
            />
            <input
              v-model.number="priceMax"
              type="range"
              min="0"
              max="200"
              step="5"
              class="range-input"
            />
          </div>

          <div class="mt-5 flex justify-between text-sm text-gray-500">
            <span>$0</span>
            <span>$100</span>
            <span>$200+</span>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <h3
          class="border-b border-gray-200 px-4 py-3.5 text-base font-semibold text-gray-900"
        >
          Rating
        </h3>

        <div class="p-4">
          <div class="space-y-3">
            <label
              v-for="r in ratings"
              :key="r.value"
              class="flex cursor-pointer items-center gap-3 text-base text-gray-700"
            >
              <input
                v-model="selectedRatings"
                type="checkbox"
                :value="r.value"
                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
              />
              <span class="flex items-center gap-1">
                <span v-for="i in r.value" :key="i" class="text-yellow-400"
                  >★</span
                >
                <span
                  v-for="i in 5 - r.value"
                  :key="'e' + i"
                  class="text-gray-300"
                  >★</span
                >
                <span class="ml-1">{{ r.label }}</span>
              </span>
            </label>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <h3
          class="border-b border-gray-200 px-4 py-3.5 text-base font-semibold text-gray-900"
        >
          Availability
        </h3>

        <div class="p-4">
          <div class="space-y-3">
            <label
              v-for="(option, i) in availabilityOptions"
              :key="i"
              class="flex cursor-pointer items-center gap-3 text-base text-gray-700"
            >
              <input
                v-model="selectedAvailability"
                type="checkbox"
                :value="option.value"
                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
              />
              {{ option.label }}
            </label>
          </div>
        </div>
      </div>

      <div>
        <button
          type="button"
          class="w-full rounded-lg border border-blue-600 bg-white px-4 py-3 text-base font-medium text-blue-600 transition-colors hover:bg-blue-50"
          @click="clearFilters"
        >
          Clear all filters
        </button>
      </div>
    </form>
  </aside>
</template>
<style scoped>
.range-input {
  position: absolute;
  width: 100%;
  height: 8px;
  background: transparent;
  appearance: none;
  -webkit-appearance: none;
  pointer-events: none;
}

.range-input::-webkit-slider-thumb {
  appearance: none;
  -webkit-appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.range-input::-moz-range-thumb {
  appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.range-input::-webkit-slider-runnable-track {
  background: transparent;
  height: 8px;
}

.range-input::-moz-range-track {
  background: transparent;
  height: 8px;
}
</style>
