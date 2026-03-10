<script setup>
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import {
  AdjustmentsHorizontalIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import FiltersSidebar from '@/Components/Mentor/FiltersSidebar.vue';
import MentorCard from '@/Components/Mentor/MentorCard.vue';
import MentorCardSkeleton from '@/Components/Mentor/MentorCardSkeleton.vue';
import MentorPagination from '@/Components/Mentor/MentorPagination.vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { useMentorFilters } from '@/Stores/mentorFilters';

const mentorFilters = useMentorFilters();

const sortBy = ref('');
const view = ref('grid');
const page = ref(1);
const isFilterDrawerOpen = ref(false);
const isLoading = ref(false);

const sortOptions = [
  { value: '', label: 'Relevance' },
  { value: '-id', label: 'Latest' },
  { value: 'rate', label: 'Price: Low to High' },
  { value: '-rate', label: 'Price: High to Low' },
  { value: '-experience_started_at', label: 'Most Experienced' },
];

const pageProps = usePage().props;
const filtersData = computed(() => pageProps.filtersData || {});
const mentorsPagination = computed(() => pageProps.mentors || {});
const mentors = computed(() => mentorsPagination.value.data || []);
const totalPages = computed(() => mentorsPagination.value.last_page || 1);
const totalMentors = computed(() => mentorsPagination.value.total || 0);

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

onMounted(() => {
  const query = pageProps.queryParams || {};

  mentorFilters.parseQueryParams(query);

  const filtersDataValue = filtersData.value || {};
  mentorFilters.setOptions({
    stacks: filtersDataValue.stackOptions || [],
    languages: filtersDataValue.languageOptions || [],
    currencies: filtersDataValue.currencyOptions || [],
  });

  if (query.sort) sortBy.value = query.sort;
  if (query.page) page.value = Number(query.page);
});

function applyFilters() {
  const params = { ...mentorFilters.buildQueryParams() };

  if (page.value > 1) {
    params.page = page.value;
  }

  if (sortBy.value) {
    params.sort = sortBy.value;
  }

  router.get('/mentors', params, {
    preserveScroll: true,
    replace: true,
    only: ['mentors'],
    onBefore: () => {
      isLoading.value = true;
    },
    onFinish: () => {
      isLoading.value = false;
    },
  });
}

const debouncedApply = debounce(() => {
  page.value = 1;
  applyFilters();
}, 300);

watch(() => mentorFilters.buildQueryParams(), debouncedApply, { deep: true });

watch(sortBy, () => {
  page.value = 1;
  applyFilters();
});

function goToPage(newPage) {
  page.value = newPage;
  applyFilters();
}
</script>

<template>
  <Head title="Find Mentors" />
  <LandingLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12">
      <!-- Header -->
      <div
        class="mb-6 flex flex-wrap items-center justify-between gap-4 sm:mb-8"
      >
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold sm:text-3xl">Find Mentors</h1>
          <span
            role="status"
            aria-live="polite"
            class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600"
          >
            {{ totalMentors }} mentors
          </span>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Mobile filter button -->
          <button
            aria-label="Open filters"
            class="relative inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 lg:hidden"
            @click="isFilterDrawerOpen = true"
          >
            <AdjustmentsHorizontalIcon class="h-5 w-5" />
            <span>Filters</span>
            <span
              v-if="mentorFilters.activeFilterCount > 0"
              class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-xs font-medium text-white"
            >
              {{ mentorFilters.activeFilterCount }}
            </span>
          </button>

          <!-- View toggle -->
          <div class="hidden overflow-hidden rounded-md sm:flex">
            <button
              aria-label="Grid view"
              class="flex items-center px-3 py-2 text-gray-500 hover:bg-gray-50"
              :class="view === 'grid' ? 'bg-blue-50 text-blue-600' : ''"
              @click="view = 'grid'"
            >
              <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
                class="size-4"
              >
                <path
                  d="M4.25 2A2.25 2.25 0 0 0 2 4.25v2.5A2.25 2.25 0 0 0 4.25 9h2.5A2.25 2.25 0 0 0 9 6.75v-2.5A2.25 2.25 0 0 0 6.75 2h-2.5Zm0 9A2.25 2.25 0 0 0 2 13.25v2.5A2.25 2.25 0 0 0 4.25 18h2.5A2.25 2.25 0 0 0 9 15.75v-2.5A2.25 2.25 0 0 0 6.75 11h-2.5Zm9-9A2.25 2.25 0 0 0 11 4.25v2.5A2.25 2.25 0 0 0 13.25 9h2.5A2.25 2.25 0 0 0 18 6.75v-2.5A2.25 2.25 0 0 0 15.75 2h-2.5Zm0 9A2.25 2.25 0 0 0 11 13.25v2.5A2.25 2.25 0 0 0 13.25 18h2.5A2.25 2.25 0 0 0 18 15.75v-2.5A2.25 2.25 0 0 0 15.75 11h-2.5Z"
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                ></path>
              </svg>
            </button>
            <button
              aria-label="List view"
              class="flex items-center px-3 py-2 text-gray-500 hover:bg-gray-50"
              :class="view === 'list' ? 'bg-blue-50 text-blue-600' : ''"
              @click="view = 'list'"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 20 20"
              >
                <path d="M4 6h12M4 10h12M4 14h12" stroke-linecap="round" />
              </svg>
            </button>
          </div>

          <!-- Sort dropdown -->
          <div class="relative inline-flex items-center">
            <select
              v-model="sortBy"
              aria-label="Sort mentors"
              class="w-48 appearance-none rounded-md border border-gray-200 px-3 py-2 pr-10 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:w-56 sm:px-4 sm:text-base"
            >
              <option
                v-for="option in sortOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>

            <div
              class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-500"
            >
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0
                               011.06.02L10 11.168l3.71-3.938a.75.75 0
                               111.08 1.04l-4.25 4.5a.75.75 0
                               01-1.08 0l-4.25-4.5a.75.75 0
                               01.02-1.06z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Active filter chips -->
      <div
        v-if="mentorFilters.activeFilters.length > 0"
        class="mb-6 flex flex-wrap items-center gap-2"
      >
        <span
          v-for="filter in mentorFilters.activeFilters"
          :key="`${filter.type}-${filter.value}`"
          class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700"
        >
          {{ filter.label }}
          <button
            type="button"
            :aria-label="`Remove filter: ${filter.label}`"
            class="ml-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
            @click="mentorFilters.removeFilter(filter.type, filter.value)"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </span>
        <button
          type="button"
          class="text-sm font-medium text-gray-500 hover:text-gray-700"
          @click="mentorFilters.clearAllFilters()"
        >
          Clear all
        </button>
      </div>

      <!-- Main content -->
      <main class="lg:grid lg:grid-cols-4 lg:gap-x-8">
        <!-- Desktop sidebar -->
        <aside class="hidden lg:block">
          <div class="sticky top-24 max-h-[calc(100vh-8rem)] overflow-y-auto">
            <FiltersSidebar />
          </div>
        </aside>

        <!-- Mentor cards -->
        <section class="lg:col-span-3">
          <div
            v-if="isLoading"
            :class="
              view === 'grid' ?
                'grid gap-6 sm:grid-cols-2 lg:grid-cols-3'
              : 'space-y-4'
            "
          >
            <MentorCardSkeleton v-for="i in 6" :key="i" :view="view" />
          </div>

          <div v-else-if="totalMentors === 0" class="py-16 text-center">
            <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900">
              No mentors found
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Try adjusting your filters to find what you're looking for.
            </p>
            <button
              v-if="mentorFilters.activeFilterCount > 0"
              class="mt-6 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
              @click="mentorFilters.clearAllFilters()"
            >
              Clear all filters
            </button>
          </div>

          <div
            v-else
            :class="
              view === 'grid' ?
                'grid gap-6 sm:grid-cols-2 lg:grid-cols-3'
              : 'space-y-4'
            "
          >
            <MentorCard
              v-for="mentor in mentors"
              :key="mentor.id"
              :mentor="mentor"
              :view="view"
            />
          </div>

          <MentorPagination
            v-if="!isLoading && totalMentors > 0"
            :page="page"
            :total-pages="totalPages"
            @update:page="goToPage"
          />
        </section>
      </main>
    </div>

    <!-- Mobile filter drawer -->
    <TransitionRoot :show="isFilterDrawerOpen" as="template">
      <Dialog
        class="relative z-50 lg:hidden"
        @close="isFilterDrawerOpen = false"
      >
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 flex">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="ease-in duration-200"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel
              class="relative flex w-full max-w-sm flex-col overflow-y-auto bg-gray-50 shadow-xl"
            >
              <div
                class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4"
              >
                <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
                <button
                  aria-label="Close filters"
                  class="-mr-2 flex h-10 w-10 items-center justify-center rounded-md text-gray-400 hover:text-gray-500"
                  @click="isFilterDrawerOpen = false"
                >
                  <XMarkIcon class="h-6 w-6" />
                </button>
              </div>

              <div class="flex-1 px-4 py-4">
                <FiltersSidebar />
              </div>

              <div class="border-t border-gray-200 bg-white px-4 py-4">
                <button
                  class="w-full rounded-lg bg-blue-600 py-3 text-base font-medium text-white transition hover:bg-blue-700"
                  @click="isFilterDrawerOpen = false"
                >
                  Show results ({{ totalMentors }})
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
  </LandingLayout>
</template>
