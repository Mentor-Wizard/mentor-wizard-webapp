<script setup>
import {
  AcademicCapIcon,
  ChevronDownIcon,
  CodeBracketIcon,
  CurrencyDollarIcon,
  FunnelIcon,
  LanguageIcon,
  StarIcon,
} from '@heroicons/vue/24/outline';
import { computed, reactive, ref } from 'vue';

import { useMentorFilters } from '@/Stores/mentorFilters';

const mentorFilters = useMentorFilters();
const stackSearch = ref('');
const languageSearch = ref('');

const showAllStacks = ref(false);
const showAllLanguages = ref(false);

const sections = reactive({
  stacks: true,
  languages: true,
  experience: true,
  price: true,
  rating: true,
});

const filteredStacks = computed(() =>
  mentorFilters.stackOptions.filter((option) =>
    option.label.toLowerCase().includes(stackSearch.value.toLowerCase()),
  ),
);

const filteredLanguages = computed(() =>
  mentorFilters.languageOptions.filter((option) =>
    option.label.toLowerCase().includes(languageSearch.value.toLowerCase()),
  ),
);

const visibleStacks = computed(() => {
  const filtered = filteredStacks.value;
  return showAllStacks.value ? filtered : filtered.slice(0, 10);
});

const visibleLanguages = computed(() => {
  const filtered = filteredLanguages.value;
  return showAllLanguages.value ? filtered : filtered.slice(0, 10);
});

const hiddenStacksCount = computed(() =>
  Math.max(0, filteredStacks.value.length - 10),
);

const hiddenLanguagesCount = computed(() =>
  Math.max(0, filteredLanguages.value.length - 10),
);

const stacksCount = computed(() => mentorFilters.selectedStacks.length);
const languagesCount = computed(() => mentorFilters.selectedLanguages.length);
const experienceCount = computed(() => mentorFilters.selectedExperience.length);

const priceActive = computed(
  () => mentorFilters.minRate > 0 || mentorFilters.maxRate < 200,
);

const ratingActive = computed(() => mentorFilters.minRating !== null);

const toggleSection = (key) => {
  sections[key] = !sections[key];
};

const MIN_GAP = 10;

const onMinRateInput = (e) => {
  const val = Number(e.target.value);
  mentorFilters.minRate = Math.min(val, mentorFilters.maxRate - MIN_GAP);
};

const onMaxRateInput = (e) => {
  const val = Number(e.target.value);
  mentorFilters.maxRate = Math.max(val, mentorFilters.minRate + MIN_GAP);
};

const clearFilters = () => mentorFilters.clearAllFilters();
</script>

<template>
  <form class="space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3.5"
        :aria-expanded="sections.stacks"
        @click="toggleSection('stacks')"
      >
        <span
          class="flex items-center gap-2 text-base font-semibold text-gray-900"
        >
          <CodeBracketIcon class="h-5 w-5 text-blue-600" />
          Technology Stacks
          <span
            v-if="stacksCount > 0"
            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600"
          >
            {{ stacksCount }}
          </span>
        </span>
        <ChevronDownIcon
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': sections.stacks }"
        />
      </button>

      <div v-show="sections.stacks" class="border-t border-gray-100 p-4">
        <div class="relative">
          <input
            v-model="stackSearch"
            type="text"
            placeholder="Search stacks..."
            aria-label="Search technology stacks"
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

        <div class="mt-4 space-y-1">
          <label
            v-for="(option, i) in visibleStacks"
            :key="i"
            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
          >
            <input
              v-model="mentorFilters.selectedStacks"
              type="checkbox"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>

          <button
            v-if="filteredStacks.length > 10"
            type="button"
            class="mt-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-700"
            @click="showAllStacks = !showAllStacks"
          >
            {{
              showAllStacks ? 'Show less' : `Show more (${hiddenStacksCount})`
            }}
          </button>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3.5"
        :aria-expanded="sections.languages"
        @click="toggleSection('languages')"
      >
        <span
          class="flex items-center gap-2 text-base font-semibold text-gray-900"
        >
          <LanguageIcon class="h-5 w-5 text-blue-600" />
          Languages
          <span
            v-if="languagesCount > 0"
            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600"
          >
            {{ languagesCount }}
          </span>
        </span>
        <ChevronDownIcon
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': sections.languages }"
        />
      </button>

      <div v-show="sections.languages" class="border-t border-gray-100 p-4">
        <div class="relative">
          <input
            v-model="languageSearch"
            type="text"
            placeholder="Search languages..."
            aria-label="Search languages"
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

        <div class="mt-4 space-y-1">
          <label
            v-for="(option, i) in visibleLanguages"
            :key="i"
            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
          >
            <input
              v-model="mentorFilters.selectedLanguages"
              type="checkbox"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>

          <button
            v-if="filteredLanguages.length > 10"
            type="button"
            class="mt-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-700"
            @click="showAllLanguages = !showAllLanguages"
          >
            {{
              showAllLanguages ? 'Show less' : (
                `Show more (${hiddenLanguagesCount})`
              )
            }}
          </button>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3.5"
        :aria-expanded="sections.experience"
        @click="toggleSection('experience')"
      >
        <span
          class="flex items-center gap-2 text-base font-semibold text-gray-900"
        >
          <AcademicCapIcon class="h-5 w-5 text-blue-600" />
          Experience Level
          <span
            v-if="experienceCount > 0"
            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600"
          >
            {{ experienceCount }}
          </span>
        </span>
        <ChevronDownIcon
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': sections.experience }"
        />
      </button>

      <div v-show="sections.experience" class="border-t border-gray-100 p-4">
        <div class="space-y-1">
          <label
            v-for="(option, i) in mentorFilters.experienceOptions"
            :key="i"
            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
          >
            <input
              v-model="mentorFilters.selectedExperience"
              type="checkbox"
              :value="option.value"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            {{ option.label }}
          </label>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3.5"
        :aria-expanded="sections.price"
        @click="toggleSection('price')"
      >
        <span
          class="flex items-center gap-2 text-base font-semibold text-gray-900"
        >
          <CurrencyDollarIcon class="h-5 w-5 text-blue-600" />
          Price Range (per hour)
          <span
            v-if="priceActive"
            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600"
          >
            1
          </span>
        </span>
        <ChevronDownIcon
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': sections.price }"
        />
      </button>

      <div v-show="sections.price" class="border-t border-gray-100 p-4">
        <div class="mb-4">
          <select
            id="currency"
            v-model="mentorFilters.selectedCurrency"
            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 focus:border-blue-600 focus:ring-blue-600"
          >
            <option
              v-for="(currency, i) in mentorFilters.currencyOptions"
              :key="i"
              :value="currency.value"
            >
              {{ currency.label }}
            </option>
          </select>
        </div>

        <div
          class="mb-4 flex justify-between text-sm font-semibold text-gray-900"
        >
          <span>
            {{ mentorFilters.selectedCurrency }}
            {{ mentorFilters.minRate }}
          </span>
          <span>
            {{ mentorFilters.selectedCurrency }}
            {{ mentorFilters.maxRate
            }}{{ mentorFilters.maxRate >= 200 ? '+' : '' }}
          </span>
        </div>

        <div class="relative h-2">
          <div class="absolute h-2 w-full rounded-full bg-gray-200"></div>
          <div
            class="absolute h-2 rounded-full bg-blue-600"
            :style="{
              left: (mentorFilters.minRate / 200) * 100 + '%',
              width:
                ((mentorFilters.maxRate - mentorFilters.minRate) / 200) * 100
                + '%',
            }"
          ></div>

          <input
            type="range"
            min="0"
            max="200"
            step="5"
            :value="mentorFilters.minRate"
            class="range-input"
            aria-label="Minimum price"
            @input="onMinRateInput"
          />
          <input
            type="range"
            min="0"
            max="200"
            step="5"
            :value="mentorFilters.maxRate"
            class="range-input"
            aria-label="Maximum price"
            @input="onMaxRateInput"
          />
        </div>

        <div class="mt-5 flex justify-between text-xs text-gray-400">
          <span>{{ mentorFilters.selectedCurrency }} 0</span>
          <span>{{ mentorFilters.selectedCurrency }} 100</span>
          <span>{{ mentorFilters.selectedCurrency }} 200+</span>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3.5"
        :aria-expanded="sections.rating"
        @click="toggleSection('rating')"
      >
        <span
          class="flex items-center gap-2 text-base font-semibold text-gray-900"
        >
          <StarIcon class="h-5 w-5 text-blue-600" />
          Minimum Rating
          <span
            v-if="ratingActive"
            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600"
          >
            1
          </span>
        </span>
        <ChevronDownIcon
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': sections.rating }"
        />
      </button>

      <div v-show="sections.rating" class="border-t border-gray-100 p-4">
        <div class="space-y-1">
          <label
            v-for="r in mentorFilters.ratingOptions"
            :key="r.value"
            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
          >
            <input
              v-model="mentorFilters.minRating"
              type="radio"
              :value="r.value"
              class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-600"
            />
            <span class="flex items-center gap-1">
              <span
                v-for="i in r.value"
                :key="i"
                class="text-lg text-yellow-400"
              >
                &#9733;
              </span>
              <span
                v-for="i in 5 - r.value"
                :key="'e' + i"
                class="text-lg text-gray-300"
              >
                &#9733;
              </span>
              <span class="ml-1 text-gray-500">{{ r.label }}</span>
            </span>
          </label>
        </div>
      </div>
    </div>

    <div>
      <button
        type="button"
        class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900"
        @click="clearFilters"
      >
        <FunnelIcon class="h-5 w-5" />
        Clear all filters
      </button>
    </div>
  </form>
</template>

<style scoped>
.range-input {
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  width: 100%;
  height: 8px;
  background: transparent;
  appearance: none;
  -webkit-appearance: none;
  pointer-events: none;
  margin: 0;
  padding: 0;
}

.range-input::-webkit-slider-thumb {
  appearance: none;
  -webkit-appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  margin-top: -6px;
}

.range-input::-moz-range-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 9999px;
  background: #2563eb;
  cursor: pointer;
  pointer-events: auto;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.range-input:focus::-webkit-slider-thumb {
  box-shadow:
    0 0 0 3px rgba(37, 99, 235, 0.3),
    0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.range-input:focus::-moz-range-thumb {
  box-shadow:
    0 0 0 3px rgba(37, 99, 235, 0.3),
    0 1px 3px 0 rgba(0, 0, 0, 0.1);
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
