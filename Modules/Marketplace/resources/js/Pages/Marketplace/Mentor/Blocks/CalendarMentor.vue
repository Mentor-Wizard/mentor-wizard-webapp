<script setup>
import { GlobeAltIcon } from '@heroicons/vue/16/solid/index.js';
import { router, usePage } from '@inertiajs/vue3';
import CreateCalendarEvent from '@modules/Calendar/resources/js/Pages/Calendar/CreateCalendarEvent.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  calendarBlock: {
    type: Object,
    default: null,
  },
  mainProgramSlug: {
    type: String,
    default: null,
  },
  mainProgram: {
    type: Object,
    default: null,
  },
  weekDays: {
    type: Array,
    default: () => [
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday',
    ],
  },
  currentDate: {
    type: String,
    default: null,
  },
});

const mentorSlug = usePage().props.mentor.slug;
const selectedDate = ref(null);
const showCreateEventModal = ref(false);
const selectedSlot = ref(null);

const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

const selectedDaySlots = computed(() => {
  if (!selectedDate.value || !props.calendarBlock) return [];
  const day = props.calendarBlock.calendarSlots.find(
    (d) => d.date === selectedDate.value,
  );
  return day?.slots ?? [];
});

const monthLabel = computed(() => {
  const d = props.currentDate ? new Date(props.currentDate) : new Date();
  return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
});

const selectedDateLabel = computed(() => {
  if (!selectedDate.value) return '';
  return new Date(selectedDate.value).toLocaleDateString(undefined, {
    month: 'long',
    day: 'numeric',
  });
});

const addMonths = (dateStr, diff) => {
  const d = dateStr ? new Date(dateStr) : new Date();
  d.setDate(1);
  d.setMonth(d.getMonth() + diff);
  return d.toISOString().slice(0, 10);
};

const goToMonth = (diff) => {
  const target = addMonths(props.currentDate, diff);
  selectedDate.value = null;
  router.visit(route('page.mentor', mentorSlug), {
    preserveScroll: true,
    preserveState: true,
    data: { calendar_date: target },
    only: ['calendarBlock', 'currentDate'],
  });
};

const selectDate = (day) => {
  if (!day.slots || day.slots.length === 0) return;
  selectedDate.value = day.date;
};

const openModal = (slot) => {
  selectedSlot.value = slot;
  showCreateEventModal.value = true;
};

const closeModal = () => {
  showCreateEventModal.value = false;
  selectedSlot.value = null;
};

const formatTime = (datetime) => {
  return new Date(datetime).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
};

const dayClass = (day, index, total) => {
  const isFirst = index === 0;
  const isLast = index === total - 1;
  const isFirstInLastRow = index === total - 7;

  return [
    day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
    day.slots && day.slots.length > 0 ?
      'cursor-pointer hover:bg-gray-100'
    : 'cursor-not-allowed',
    selectedDate.value === day.date ? 'ring-2 ring-inset ring-indigo-400' : '',
    isFirst ? 'rounded-tl-lg' : '',
    index === 6 ? 'rounded-tr-lg' : '',
    isFirstInLastRow ? 'rounded-bl-lg' : '',
    isLast ? 'rounded-br-lg' : '',
    'py-1.5 focus:z-10',
  ];
};

const timeClass = (day) => [
  'mx-auto flex size-7 items-center justify-center rounded-full',
  selectedDate.value === day.date && day.isToday ?
    'bg-indigo-600 text-white'
  : '',
  selectedDate.value === day.date && !day.isToday ?
    'bg-gray-900 text-white'
  : '',
  selectedDate.value !== day.date && day.isToday ?
    'text-indigo-600 font-semibold'
  : '',
  !day.isCurrentMonth ? 'text-gray-400' : '',
];
</script>

<template>
  <div class="mt-2">
    <h3 class="text-lg font-semibold text-gray-900">Book a consultation</h3>
  </div>

  <!-- No main consultation -->
  <div
    v-if="!mainProgramSlug"
    class="mt-4 flex min-h-48 items-center justify-center rounded-lg bg-gray-100"
  >
    <p class="text-sm text-gray-500">No main consultation is set</p>
  </div>

  <!-- Calendar widget -->
  <div v-else>
    <!-- Month navigation -->
    <div class="mt-4 flex items-center text-gray-900">
      <button
        type="button"
        class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500 disabled:opacity-30"
        :disabled="!calendarBlock?.hasSlotsBefore"
        @click="goToMonth(-1)"
      >
        <span class="sr-only">Previous month</span>
        <svg
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
          class="size-5"
        >
          <path
            d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
            clip-rule="evenodd"
            fill-rule="evenodd"
          />
        </svg>
      </button>
      <div class="flex-auto text-center text-sm font-semibold">
        {{ monthLabel }}
      </div>
      <button
        type="button"
        class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500 disabled:opacity-30"
        :disabled="!calendarBlock?.hasSlotsAfter"
        @click="goToMonth(1)"
      >
        <span class="sr-only">Next month</span>
        <svg
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
          class="size-5"
        >
          <path
            d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
            clip-rule="evenodd"
            fill-rule="evenodd"
          />
        </svg>
      </button>
    </div>

    <!-- Week day headers -->
    <div class="mt-4 grid grid-cols-7 text-center text-xs/6 text-gray-500">
      <div v-for="day in weekDays" :key="day">{{ day.slice(0, 1) }}</div>
    </div>

    <!-- Calendar grid -->
    <div
      class="isolate mt-2 grid grid-cols-7 gap-px rounded-lg bg-gray-200 text-sm shadow-sm ring-1 ring-gray-200"
    >
      <button
        v-for="(day, index) in calendarBlock?.calendarSlots"
        :key="day.date"
        type="button"
        :disabled="!day.slots || day.slots.length === 0"
        :class="
          dayClass(
            day,
            index,
            calendarBlock ? calendarBlock.calendarSlots.length : 0,
          )
        "
        @click="selectDate(day)"
      >
        <time :datetime="day.date" :class="timeClass(day)">
          {{ day.date?.split('-').pop().replace(/^0/, '') }}
        </time>
        <div
          v-if="day.slots && day.slots.length > 0"
          class="mx-auto mt-0.5 size-1.5 rounded-full bg-indigo-400"
        />
      </button>
    </div>

    <!-- Inline slot picker -->
    <div v-if="selectedDate" class="mt-4">
      <h3 class="text-sm font-semibold text-gray-900">
        Available slots — {{ selectedDateLabel }}
      </h3>

      <div
        v-if="selectedDaySlots.length > 0"
        class="mt-2 grid grid-cols-2 gap-2"
      >
        <button
          v-for="slot in selectedDaySlots"
          :key="slot.start"
          type="button"
          class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-center text-xs font-medium text-indigo-700 transition-colors hover:bg-indigo-100"
          @click="openModal(slot)"
        >
          {{ formatTime(slot.start) }} – {{ formatTime(slot.end) }}
        </button>
      </div>
      <p v-else class="mt-2 text-sm text-gray-500">
        No slots available for this day.
      </p>

      <div class="mt-3 flex items-center">
        <GlobeAltIcon class="h-4 w-4 text-gray-400" />
        <p class="ms-1.5 text-xs text-gray-400">
          Times shown in {{ browserTimezone }}
        </p>
      </div>
    </div>
  </div>

  <!-- Booking modal -->
  <CreateCalendarEvent
    :open="showCreateEventModal"
    :close-create-event-page="closeModal"
    :selected-date="selectedDate"
    :available-slots="selectedDaySlots"
    :mentor-program="mainProgram"
    :selected-slot="selectedSlot"
    :rounding-minutes="5"
    :mentor-program-id="mainProgram?.id"
  />
</template>
