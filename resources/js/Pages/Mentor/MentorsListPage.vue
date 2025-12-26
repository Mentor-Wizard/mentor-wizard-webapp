<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import FiltersSidebar from '@/Components/Mentor/FiltersSidebar.vue';
import MentorCard from '@/Components/Mentor/MentorCard.vue';
import MentorPagination from '@/Components/Mentor/MentorPagination.vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { useMentorFilters } from '@/Stores/mentorFilters';

const mentorFilters = useMentorFilters();

const sortBy = ref('relevance');
const view = ref('grid');
const page = ref(1);

const pageProps = usePage().props;
const filtersData = computed(() => pageProps.filtersData || {});
const mentorsPagination = computed(() => pageProps.mentors || {});
const mentors = computed(() => mentorsPagination.value.data || []);
const totalPages = computed(() => mentorsPagination.value.last_page || 1);
const totalMentors = computed(() => mentorsPagination.value.total || 0);

onMounted(() => {
  const query = pageProps.queryParams || {};

  // Parse query parameters in Spatie Query Builder format
  mentorFilters.parseQueryParams(query);

  // Initialize filter options from backend
  const filtersDataValue = filtersData.value || {};
  mentorFilters.setOptions({
    stacks: filtersDataValue.stackOptions || [],
    languages: filtersDataValue.languageOptions || [],
    currencies: filtersDataValue.currencyOptions || [],
  });

  // Initialize pagination and sorting
  if (query.sortBy) sortBy.value = query.sortBy;
  if (query.page) page.value = Number(query.page);
});

watch(
  () => mentorFilters.buildQueryParams(),
  (newParams, oldParams) => {
    // Build query params in Spatie Query Builder format
    const params = { ...newParams };

    // Add pagination if needed
    if (page.value > 1) {
      params.page = page.value;
    }

    // Add sorting if needed
    if (sortBy.value !== 'relevance') {
      params.sortBy = sortBy.value;
    }

    router.get('/mentors', params, {
      preserveScroll: true,
      replace: true,
      only: ['mentors'],
    });
  },
  { deep: true },
);
</script>

<template>
  <Head title="Find Mentors" />
  <LandingLayout>
    <div class="mx-auto max-w-7xl px-6 py-12">
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <h1 class="text-3xl font-bold">Find Mentors</h1>
          <span
            class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600"
          >
            {{ totalMentors }} mentors available
          </span>
        </div>

        <div class="flex items-center space-x-3">
          <div class="flex overflow-hidden rounded-md">
            <button
              class="flex items-center px-3 py-2 text-gray-500 hover:bg-gray-50"
              :class="view === 'grid' ? 'bg-blue-50 text-blue-600' : ''"
              @click="view = 'grid'"
            >
              <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                data-slot="icon"
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

          <div class="relative inline-flex items-center">
            <select
              v-model="sortBy"
              class="w-72 appearance-none rounded-md border border-gray-200 px-4 py-2 pr-12 text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
              <option value="relevance">Sort by: Relevance</option>
              <option value="latest">Sort by: Latest</option>
              <option value="popular">Sort by: Popular</option>
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

      <main class="lg:grid lg:grid-cols-4 lg:gap-x-8">
        <aside class="hidden lg:block">
          <FiltersSidebar />
        </aside>

        <section class="lg:col-span-3">
          <div v-if="totalMentors === 0" class="mt-8 text-center text-gray-500">
            No mentors found with current filters
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

          <MentorPagination v-model:page="page" :total-pages="totalPages" />
        </section>
      </main>
    </div>
  </LandingLayout>
</template>
