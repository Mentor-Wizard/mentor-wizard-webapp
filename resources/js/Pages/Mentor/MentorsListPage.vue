<script setup>
import { ref, watch, onMounted } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';

import LandingLayout from '@/Layouts/LandingLayout.vue';
import FiltersSidebar from '@/Components/Mentor/FiltersSidebar.vue';
import MentorCard from '@/Components/Mentor/MentorCard.vue';
// import Pagination from '@/Components/Mentor/Pagination.vue';

const expertiseOptions = [
  { value: 'web-dev', label: 'Web Development (324)' },
  { value: 'mobile-dev', label: 'Mobile Development (218)' },
  { value: 'data-science', label: 'Data Science (195)' },
  { value: 'ux-ui', label: 'UX/UI Design (167)' },
  { value: 'digital-marketing', label: 'Digital Marketing (142)' },
  { value: 'product-mgmt', label: 'Product Management (118)' },
];

const experienceOptions = [
  { value: 'entry', label: 'Entry Level (1–3 years)' },
  { value: 'mid', label: 'Mid Level (4–7 years)' },
  { value: 'senior', label: 'Senior Level (8–12 years)' },
  { value: 'expert', label: 'Expert (12+ years)' },
];

const ratings = [
  { value: 5, label: '5.0' },
  { value: 4, label: '4.0 & up' },
  { value: 3, label: '3.0 & up' },
];

const availabilityOptions = [
  { value: 'today', label: 'Available today' },
  { value: 'booked', label: 'Booked' },
  { value: 'tomorrow', label: 'Available tomorrow' },
];

const mentors = ref([
  {
    id: 1,
    name: 'Michael Anderson',
    title: 'Senior Web Developer & Instructor',
    price: '85',
    tags: ['web-dev', 'react', 'node'],
    rating: 5,
    reviews: 432,
    experience: 12,
    image: 'https://via.placeholder.com/150',
    availability: 'today',
    availabilityLabel: 'Available today',
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    title: 'UX/UI Design Lead',
    price: '65',
    tags: ['ux-ui', 'figma'],
    rating: 4.2,
    reviews: 187,
    experience: 8,
    image: 'https://via.placeholder.com/150',
    availability: 'tomorrow',
    availabilityLabel: 'Available tomorrow',
  },
]);

const total = ref(mentors.value.length);
const page = ref(1);
const sortBy = ref('relevance');
const view = ref('grid');

const filters = ref({
  expertise: [],
  experience: [],
  priceMin: 0,
  priceMax: 200,
  ratings: [],
  availability: [],
});

// Debounce timer for price range
let priceDebounceTimer = null;
let isInitializing = true;

// Initialize filters from URL IMMEDIATELY (before component mount)
initializeFiltersFromUrl();

// Allow watch to run after Vue's next tick
onMounted(() => {
  // Use nextTick to ensure all child components have processed initial props
  setTimeout(() => {
    isInitializing = false;
  }, 0);
});

// Watch filters and update URL
watch(
  filters,
  (newFilters, oldFilters) => {
    // Skip update during initialization
    if (isInitializing) {
      return;
    }

    // Check if price changed (for debouncing)
    const priceChanged =
      newFilters.priceMin !== oldFilters?.priceMin
      || newFilters.priceMax !== oldFilters?.priceMax;

    if (priceChanged) {
      // Debounce price range changes
      if (priceDebounceTimer) {
        clearTimeout(priceDebounceTimer);
      }

      priceDebounceTimer = setTimeout(() => {
        updateUrl();
      }, 500);
    } else {
      // Update URL immediately for other filters
      updateUrl();
    }
  },
  { deep: true },
);

// Watch sort and view changes
watch([sortBy, page], () => {
  if (isInitializing) {
    return;
  }
  updateUrl();
});

function initializeFiltersFromUrl() {
  const query = usePage().props.ziggy.query ?? {};

  filters.value = {
    expertise: parseArray(query.expertise),
    experience: parseArray(query.experience),
    priceMin: parseInt(query.priceMin, 10) || 0,
    priceMax: parseInt(query.priceMax, 10) || 200,
    ratings: parseArray(query.ratings).map((r) => parseInt(r, 10)),
    availability: parseArray(query.availability),
  };

  page.value = parseInt(query.page, 10) || 1;
  sortBy.value = query.sort || 'relevance';
}

function updateUrl() {
  const params = buildUrlParams();

  router.get(route('pages.mentors'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['mentors', 'total'],
    replace: true,
  });
}

function buildUrlParams() {
  const params = {};

  // Add array filters only if they have values
  if (filters.value.expertise.length > 0) {
    params.expertise = filters.value.expertise;
  }

  if (filters.value.experience.length > 0) {
    params.experience = filters.value.experience;
  }

  if (filters.value.ratings.length > 0) {
    params.ratings = filters.value.ratings;
  }

  if (filters.value.availability.length > 0) {
    params.availability = filters.value.availability;
  }

  // Add price filters only if they differ from defaults
  if (filters.value.priceMin !== 0) {
    params.priceMin = filters.value.priceMin;
  }

  if (filters.value.priceMax !== 200) {
    params.priceMax = filters.value.priceMax;
  }

  // Add sort if not default
  if (sortBy.value !== 'relevance') {
    params.sort = sortBy.value;
  }

  // Add page if not first
  if (page.value !== 1) {
    params.page = page.value;
  }

  return params;
}

function parseArray(value) {
  if (!value) {
    return [];
  }

  // Handle object with numeric keys (Laravel array format: {0: "entry", 1: "mid"})
  if (typeof value === 'object' && !Array.isArray(value)) {
    return Object.values(value);
  }

  // Handle single value or already an array
  return Array.isArray(value) ? value : [value];
}
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
            {{ total }} mentors available
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
          <FiltersSidebar
            v-model="filters"
            :expertise-options="expertiseOptions"
            :experience-options="experienceOptions"
            :ratings="ratings"
            :availability-options="availabilityOptions"
          />
        </aside>

        <section class="lg:col-span-3">
          <div
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

          <div
            v-if="mentors.length === 0"
            class="mt-8 text-center text-gray-500"
          >
            No mentors found with current filters
          </div>

          <!--          <Pagination :total="total" v-model:page="page" />-->
        </section>
      </main>
    </div>
  </LandingLayout>
</template>
