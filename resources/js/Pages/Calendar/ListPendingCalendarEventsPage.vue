<script setup>
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const pastExpanded = ref(false);

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

const notification = ref({ show: false, success: false, message: '' });

const showNotification = (success, message) => {
  notification.value = { show: true, success, message };
  setTimeout(() => {
    notification.value.show = false;
  }, 5000);
};

const confirmCalendarEvent = (calendarEventId, mentorProgramId) => {
  router.patch(
    route('calendar.confirm.booking', {
      mentorProgram: mentorProgramId,
      calendarEvent: calendarEventId,
    }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        if (page.props.flash?.error) {
          showNotification(false, page.props.flash.error);
        } else {
          showNotification(
            true,
            page.props.flash?.success ?? 'Event was successfully confirmed.',
          );
        }
      },
      onError: () => {
        showNotification(false, 'Failed to confirm the event.');
      },
    },
  );
};

const cancelEvent = (calendarEventId) => {
  router.delete(
    route('pages.calendar.delete', { calendarEvent: calendarEventId }),
    {
      preserveScroll: true,
      onSuccess: () => {
        showNotification(true, 'Event was successfully cancelled.');
      },
      onError: () => {
        showNotification(false, 'Failed to cancel the event.');
      },
    },
  );
};

const openEvent = (calendarEventId) => {
  router.visit(route('pages.calendar.show', { id: calendarEventId }));
};

const formatDateTime = (datetimeStr) => {
  if (!datetimeStr) return '—';
  const d = new Date(datetimeStr);
  return (
    d.toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    })
    + ' '
    + d.toLocaleTimeString('en-GB', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    })
  );
};

const hasPastEvents = () =>
  Object.keys(props.pastCalendarEvents ?? {}).length > 0;
const hasUpcomingEvents = () =>
  Object.keys(props.upcomingCalendarEvents ?? {}).length > 0;
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
        <h1 class="text-3xl font-bold text-gray-900">
          Pending Calendar Events
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Events awaiting your confirmation.
        </p>
      </div>

      <!-- Upcoming events -->
      <div v-if="hasUpcomingEvents()" class="space-y-4">
        <div
          v-for="(events, mentorProgramName) in upcomingCalendarEvents"
          :key="mentorProgramName"
          class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <h2 class="mb-4 text-lg font-semibold text-gray-900">
            {{ mentorProgramName }}
          </h2>
          <div
            v-for="event in events"
            :key="event.id"
            class="mb-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm last:mb-0"
          >
            <div class="mb-2 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">
                {{ event.title }}
              </h3>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                  @click="openEvent(event.id)"
                >
                  View / Edit
                </button>
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 shadow-xs ring-1 ring-red-300 ring-inset hover:bg-red-50"
                  @click="cancelEvent(event.id)"
                >
                  <XMarkIcon
                    class="mr-1.5 -ml-0.5 h-5 w-5"
                    aria-hidden="true"
                  />
                  Cancel
                </button>
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                  @click="
                    confirmCalendarEvent(event.id, event.mentor_program_id)
                  "
                >
                  <PlusIcon class="mr-1.5 -ml-0.5 h-5 w-5" aria-hidden="true" />
                  Confirm
                </button>
              </div>
            </div>
            <p class="text-sm text-gray-600">
              <span class="font-medium">From:</span>
              {{ event.participants[0]?.username ?? 'Unknown' }}
            </p>
            <p class="text-sm text-gray-600">
              <span class="font-medium">When:</span>
              {{ formatDateTime(event.start_date_time) }} —
              {{ formatDateTime(event.end_date_time) }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-else-if="!hasPastEvents()"
        class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm"
      >
        No pending events.
      </div>

      <!-- Past events (collapsible) -->
      <div v-if="hasPastEvents()" class="mt-6">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100"
          @click="pastExpanded = !pastExpanded"
        >
          <span>Past pending events</span>
          <ChevronDownIcon
            class="h-4 w-4 text-gray-400 transition-transform duration-200"
            :class="{ 'rotate-180': pastExpanded }"
          />
        </button>

        <div v-if="pastExpanded" class="mt-2 space-y-4">
          <div
            v-for="(events, mentorProgramName) in pastCalendarEvents"
            :key="mentorProgramName"
            class="rounded-lg border border-gray-200 bg-white p-4 opacity-75 shadow-sm"
          >
            <h2 class="mb-4 text-lg font-semibold text-gray-700">
              {{ mentorProgramName }}
            </h2>
            <div
              v-for="event in events"
              :key="event.id"
              class="mb-3 rounded-lg border border-gray-200 bg-gray-50 p-4 last:mb-0"
            >
              <div class="mb-2 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-700">
                  {{ event.title }}
                </h3>
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
                {{ formatDateTime(event.start_date_time) }} —
                {{ formatDateTime(event.end_date_time) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
