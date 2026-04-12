<script setup>
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const pastExpanded = ref(false);
const notification = ref({ show: false, success: false, message: '' });

const showNotification = (success, message) => {
  notification.value = { show: true, success, message };
  setTimeout(() => { notification.value.show = false; }, 5000);
};

onMounted(() => {
  if (page.props.flash?.success) {
    showNotification(true, page.props.flash.success);
  } else if (page.props.flash?.error) {
    showNotification(false, page.props.flash.error);
  }
});

const props = defineProps({
  locale: {
    type: String,
    default: null,
  },
  upcomingCalendarEvents: {
    type: Object,
    default: () => ({}),
  },
  pastCalendarEvents: {
    type: Object,
    default: () => ({}),
  },
});

const openEvent = (calendarEventId) => {
  router.visit(route('pages.calendar.show', { id: calendarEventId }));
};

const formatDateTime = (datetimeStr) => {
  if (!datetimeStr) return '—';
  const d = new Date(datetimeStr);
  return (
    d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
    + ' '
    + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false })
  );
};

const hasPastEvents = () => Object.keys(props.pastCalendarEvents ?? {}).length > 0;
const hasUpcomingEvents = () => Object.keys(props.upcomingCalendarEvents ?? {}).length > 0;
</script>

<template>
  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <PopUp
        :show-status="notification.show"
        :success="notification.success"
        :message="notification.message"
      />

      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Confirmed & Upcoming Events</h1>
        <p class="mt-2 text-sm text-gray-600">All confirmed sessions scheduled for today or later.</p>
      </div>

      <!-- Upcoming events -->
      <div v-if="hasUpcomingEvents()" class="space-y-4">
        <div
          v-for="(group, programId) in upcomingCalendarEvents"
          :key="programId"
          class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ group.name }}</h2>
          <div
            v-for="event in group.events"
            :key="event.id"
            class="mb-3 last:mb-0 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
          >
            <div class="mb-2 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">{{ event.title }}</h3>
              <button
                type="button"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                @click="openEvent(event.id)"
              >
                View / Edit
              </button>
            </div>
            <p class="text-sm text-gray-600">
              <span class="font-medium">From:</span>
              {{ event.participants[0]?.username ?? 'Unknown' }}
            </p>
            <p class="text-sm text-gray-600">
              <span class="font-medium">When:</span>
              {{ formatDateTime(event.start_date_time) }} — {{ formatDateTime(event.end_date_time) }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-else-if="!hasPastEvents()"
        class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm"
      >
        No confirmed upcoming events.
      </div>

      <!-- Past events (collapsible) -->
      <div v-if="hasPastEvents()" class="mt-6">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100"
          @click="pastExpanded = !pastExpanded"
        >
          <span>Past confirmed events</span>
          <ChevronDownIcon
            class="h-4 w-4 text-gray-400 transition-transform duration-200"
            :class="{ 'rotate-180': pastExpanded }"
          />
        </button>

        <div v-if="pastExpanded" class="mt-2 space-y-4">
          <div
            v-for="(group, programId) in pastCalendarEvents"
            :key="programId"
            class="rounded-lg border border-gray-200 bg-white p-4 opacity-75 shadow-sm"
          >
            <h2 class="mb-4 text-lg font-semibold text-gray-700">{{ group.name }}</h2>
            <div
              v-for="event in group.events"
              :key="event.id"
              class="mb-3 last:mb-0 rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
              <div class="mb-2 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-700">{{ event.title }}</h3>
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                  @click="openEvent(event.id)"
                >
                  View
                </button>
              </div>
              <p class="text-sm text-gray-500">
                <span class="font-medium">From:</span>
                {{ event.participants[0]?.username ?? 'Unknown' }}
              </p>
              <p class="text-sm text-gray-500">
                <span class="font-medium">When:</span>
                {{ formatDateTime(event.start_date_time) }} — {{ formatDateTime(event.end_date_time) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
