<script setup>
import { router } from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  locale: {
    type: String,
    default: null,
  },
  calendarEvents: {
    type: Object,
    default: () => {},
  },
});

const openEvent = (calendarEventId) => {
  router.visit(route('pages.calendar.show', { id: calendarEventId }));
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Confirmed & Upcoming Events
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            All confirmed sessions scheduled for the future.
          </p>
        </div>
      </div>

      <div
        v-if="Object.keys(props.calendarEvents).length === 0"
        class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm"
      >
        No confirmed upcoming events.
      </div>

      <div class="space-y-4">
        <div
          v-for="(group, programId) in props.calendarEvents"
          :key="programId"
          class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ group.name }}
            </h2>
          </div>

          <div
            v-for="event in group.events"
            :key="event.id"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
          >
            <div class="mb-2 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">
                {{ event.title }}
              </h3>
              <button
                type="button"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                @click="openEvent(event.id)"
              >
                View / Edit
              </button>
            </div>
            <p class="text-sm text-gray-600">
              <span class="font-medium">When:</span>
              {{ new Date(event.start_date_time).toLocaleString() }}
              —
              {{ new Date(event.end_date_time).toLocaleString() }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
