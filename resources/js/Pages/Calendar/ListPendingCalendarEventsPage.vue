<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const timezoneCalculated = ref(null);
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

// Notification state
const notification = ref({
  show: false,
  success: false,
  message: '',
});

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
        showNotification(true, 'Event was successfully confirmed.');
      },
      onError: () => {
        showNotification(false, 'Failed to confirm the event.');
      },
    },
  );
};
const openEvent = (calendarEventId) => {
  router.visit(route('pages.calendar.show', { id: calendarEventId }));
};

// Check for flash messages on mount
onMounted(() => {
  const page = usePage();
  timezoneCalculated.value =
    page.props.timezone ?
      page.props.timezone
    : ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Notification Popup -->
      <div>
        <PopUp
          :show-status="notification.show"
          :success="notification.success"
          :message="notification.message"
        ></PopUp>
      </div>

      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            List of Pending Calendar Events
          </h1>
          <!--          <h4>Timezone: {{ timezoneCalculated }}</h4>-->
          <p class="mt-2 text-sm text-gray-600">
            Manage your weekly schedule by adding time slots for each day.
          </p>
        </div>
      </div>

      <div class="space-y-4">
        <div
          v-for="(events, mentorProgramName) in props.calendarEvents"
          :key="mentorProgramName"
          class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ mentorProgramName }}
            </h2>
          </div>
          <div
            v-for="event in events"
            :key="event.id"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
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
                  class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
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
