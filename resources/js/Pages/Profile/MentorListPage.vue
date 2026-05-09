<script setup>
import { Link } from '@inertiajs/vue3';

import CategoryTreePanel from '@/Components/Category/CategoryTreePanel.vue';
import AppPagination from '@/Components/Navigation/AppPagination.vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineProps({
  mentors: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  selectedCategoryId: {
    type: Number,
    default: null,
  },
});
</script>

<template>
  <LandingLayout>
    <div class="bg-gray-100 py-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="mb-8 text-3xl font-bold text-gray-900">Find a Mentor</h1>

        <div class="flex flex-col gap-6 lg:flex-row">
          <aside class="w-full shrink-0 lg:w-64">
            <CategoryTreePanel
              :categories="categories"
              :selected-category-id="selectedCategoryId"
              route-name="page.profile-programs"
            />
          </aside>

          <div class="min-w-0 flex-1">
            <div class="space-y-6">
              <div
                v-for="mentor in mentors.data"
                :key="mentor.userSlug"
                class="overflow-hidden rounded-lg bg-white shadow-sm"
              >
                <div class="flex items-start gap-6 p-6">
                  <Link :href="route('page.mentor', mentor.userSlug)">
                    <img
                      :src="mentor.userAvatar"
                      :alt="mentor.userName"
                      class="h-20 w-20 flex-shrink-0 rounded-full object-cover"
                    />
                  </Link>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between">
                      <div>
                        <Link
                          :href="route('page.mentor', mentor.userSlug)"
                          class="text-lg font-semibold text-gray-900 hover:text-indigo-600"
                        >
                          {{ mentor.userName }}
                        </Link>
                        <p class="mt-0.5 text-sm text-gray-600">
                          {{ mentor.title }}
                        </p>
                      </div>

                      <div class="ml-4 flex-shrink-0 text-right">
                        <p class="text-lg font-semibold text-indigo-600">
                          {{ mentor.rate }}
                          <span v-if="mentor.currency">{{
                            mentor.currency.symbol
                          }}</span>
                          <span class="text-sm font-normal text-gray-500"
                            >/hour</span
                          >
                        </p>
                      </div>
                    </div>

                    <p class="mt-2 line-clamp-2 text-sm text-gray-500">
                      {{ mentor.description }}
                    </p>

                    <div class="mt-4 flex items-center justify-end gap-3">
                      <Link
                        :href="route('page.mentor', mentor.userSlug)"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                      >
                        View Profile
                      </Link>
                      <Link
                        v-if="mentor.mainProgramSlug"
                        :href="
                          route(
                            'pages.mentor.program.book',
                            mentor.mainProgramSlug,
                          )
                        "
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                      >
                        Book Consultation
                      </Link>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="mentors.data.length === 0"
                class="py-12 text-center text-gray-500"
              >
                No mentors found.
              </div>
            </div>

            <AppPagination :data="mentors" />
          </div>
        </div>
      </div>
    </div>
  </LandingLayout>
</template>
