<script setup>
import { Link, router } from '@inertiajs/vue3';
import CreateCalendarEvent from '@modules/Calendar/resources/js/Pages/Calendar/CreateCalendarEvent.vue';
import { computed, ref } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  days: {
    type: Object,
    default: () => {},
  },
  weekDays: {
    type: Array,
    default: () => [],
  },
  mentorProgram: {
    type: Object,
    default: () => null,
  },
  roundingMinutes: {
    type: Number,
    default: 5,
  },
  currentDate: {
    type: String,
    default: null,
  },
  isMentorProgramOwner: {
    type: Boolean,
    default: false,
  },
  mentorSlug: {
    type: String,
    default: null,
  },
});

const showCreateEventModal = ref(false);
const selectedDate = ref(null);
const selectedSlots = ref([]);
const selectedSlot = ref(null);

const openCreateEventModal = (date, slots, slotToPrefill = null) => {
  console.log('open create event modal');
  selectedDate.value = date;
  selectedSlots.value = slots;
  selectedSlot.value = slotToPrefill;
  showCreateEventModal.value = true;
};

const closeCreateEventModal = () => {
  showCreateEventModal.value = false;
  selectedDate.value = null;
  selectedSlots.value = [];
  selectedSlot.value = null;
};

const getDaySlots = (slots) => {
  if (!slots || slots.length === 0) return 'No slots available';
  return slots
    .map((slot) => {
      const start = new Date(slot.start).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      });
      const end = new Date(slot.end).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      });
      return `${start} - ${end}`;
    })
    .join(', ');
};

const monthLabel = () => {
  const d = props.currentDate ? new Date(props.currentDate) : new Date();
  return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
};

const addMonths = (dateStr, diff) => {
  const d = dateStr ? new Date(dateStr) : new Date();
  d.setDate(2);
  d.setMonth(d.getMonth() + diff);
  return d.toISOString().slice(0, 10);
};

// Set of week-row indices (0–5) that contain at least one day with available slots
const weeksWithSlots = computed(() => {
  const weeks = new Set();
  (props.days?.calendarSlots ?? []).forEach((day, index) => {
    if (day.slots && day.slots.length > 0) {
      weeks.add(Math.floor(index / 7));
    }
  });
  return weeks;
});

const formatDateTime = (datetimeStr) => {
  if (!datetimeStr) return null;
  const d = new Date(datetimeStr);
  const date = d.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
  const time = d
    .toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    })
    .replace(' ', '');
  return `${date} ${time}`;
};

const programPeriod = computed(() => {
  const start = formatDateTime(props.mentorProgram?.start_time);
  const end = formatDateTime(props.mentorProgram?.end_time);
  if (!start && !end) return null;
  if (start && end) return `${start} — ${end}`;
  if (start) return `${start} — no end date`;
  return `no start date — ${end}`;
});

const goToMonth = (diff) => {
  const target = addMonths(props.currentDate, diff);

  router.visit(route('pages.mentor.program.book', props.mentorProgram.slug), {
    preserveScroll: true,
    preserveState: true,
    data: { date: target },
  });
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="lg:flex lg:h-full lg:flex-col">
      <div
        class="shadow-sm ring-1 ring-black/5 lg:flex lg:flex-auto lg:flex-col"
      >
        <!-- Mentor program header -->
        <div
          class="mx-4 mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex justify-end">
              <Link
                v-if="mentorSlug"
                :href="route('page.mentor', mentorSlug)"
                class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
              >
                &larr; Back to Profile
              </Link>
            </div>
            <div class="flex-1 text-center">
              <h1 class="text-xl font-semibold text-gray-900">
                {{ mentorProgram.name }}
              </h1>
              <p class="mt-2 text-sm text-gray-600">
                {{ mentorProgram.description }}
              </p>
              <p v-if="programPeriod" class="mt-2 text-sm text-gray-600">
                {{ programPeriod }}
              </p>
              <p class="mt-2 text-sm text-gray-600">
                Session duration: {{ mentorProgram.session_duration }} min
              </p>
              <p
                v-if="
                  mentorProgram.session_type_options
                  && mentorProgram.session_type_options.length
                "
                class="mt-2 text-sm text-gray-600"
              >
                Session types:
                <span
                  v-for="type in mentorProgram.session_type_options"
                  :key="type"
                  class="ml-1 inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200 ring-inset"
                  >{{ type }}</span
                >
              </p>
              <p
                v-if="isMentorProgramOwner"
                class="mt-1 text-sm text-gray-600 italic"
              >
                You are owner of this mentor program, so cannot create an event.
              </p>
            </div>
          </div>
        </div>

        <!-- Month navigation + Week day headers -->
        <div class="mx-4 mt-4 flex items-center justify-between">
          <button
            type="button"
            class="mb-1 rounded-md border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!days?.hasSlotsAfter"
            @click="goToMonth(-1)"
          >
            Prev
          </button>
          <div class="text-sm font-semibold text-gray-900">
            {{ monthLabel() }}
          </div>
          <button
            type="button"
            class="mb-1 rounded-md border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!days?.hasSlotsBefore"
            @click="goToMonth(1)"
          >
            Next
          </button>
        </div>

        <!-- Week day headers -->
        <div
          class="grid grid-cols-7 gap-px border-b border-gray-300 bg-gray-200 text-center text-xs/6 font-semibold text-gray-700 lg:flex-none"
        >
          <div v-for="weekDay in weekDays" :key="weekDay" class="bg-white py-2">
            {{ weekDay.slice(0, 1)
            }}<span class="sr-only sm:not-sr-only">{{
              weekDay.slice(1, 3)
            }}</span>
          </div>
        </div>

        <!-- Calendar grid -->
        <div class="flex bg-gray-200 text-xs/6 text-gray-700 lg:flex-auto">
          <div class="hidden w-full lg:grid lg:grid-cols-7 lg:gap-px">
            <div
              v-for="(day, index) in days.calendarSlots"
              :key="day.date"
              :class="[
                day.isCurrentMonth ? 'bg-white' : 'bg-gray-50 text-gray-500',
                day.slots && day.slots.length > 0 ?
                  'cursor-pointer hover:bg-gray-100'
                : 'cursor-not-allowed',
                'relative px-3',
                weeksWithSlots.has(Math.floor(index / 7)) ? 'min-h-[100px] py-2'
                : 'py-1',
              ]"
              :title="getDaySlots(day.slots)"
              @click="
                day.slots
                && day.slots.length > 0
                && openCreateEventModal(day.date, day.slots)
              "
            >
              <time
                :datetime="day.date"
                :class="
                  day.isToday ?
                    'flex size-6 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white'
                  : undefined
                "
              >
                {{ day?.date?.split('-').pop().replace(/^0/, '') }}
              </time>
              <div v-if="day.slots && day.slots.length > 0" class="mt-2">
                <div class="text-xs font-medium text-green-600">
                  {{ day.slots.length }} slot{{
                    day.slots.length > 1 ? 's' : ''
                  }}
                  available
                </div>
                <!-- Quick slot pills -->

                <div class="mt-2 grid grid-cols-2 gap-2">
                  <button
                    v-for="slot in day.slots.slice(0, 10)"
                    :key="slot.start + '-' + slot.end"
                    type="button"
                    :disabled="isMentorProgramOwner"
                    class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[10px]/[14px] font-medium text-indigo-700 hover:bg-indigo-100"
                    @click.stop="
                      openCreateEventModal(day.date, day.slots, slot)
                    "
                  >
                    {{
                      new Date(slot.start).toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                      })
                    }}
                    -
                    {{
                      new Date(slot.end).toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                      })
                    }}
                  </button>

                  <span
                    v-if="day.slots.length > 10"
                    class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500"
                  >
                    …
                  </span>
                </div>
              </div>
            </div>

            <!-- Mobile view -->
            <div
              class="isolate grid w-full grid-cols-7 grid-rows-6 gap-px lg:hidden"
            >
              <button
                v-for="day in days.calendarSlots"
                :key="day.date"
                type="button"
                :disabled="!day.slots || day.slots.length === 0"
                :class="[
                  day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                  (day.isSelected || day.isToday) && 'font-semibold',
                  day.isSelected && 'text-white',
                  !day.isSelected && day.isToday && 'text-indigo-600',
                  !day.isSelected
                    && day.isCurrentMonth
                    && !day.isToday
                    && 'text-gray-900',
                  !day.isSelected
                    && !day.isCurrentMonth
                    && !day.isToday
                    && 'text-gray-500',
                  'flex h-14 flex-col px-3 py-2 hover:bg-gray-100 focus:z-10',
                ]"
                @click="
                  day.slots
                  && day.slots.length > 0
                  && openCreateEventModal(day.date, day.slots)
                "
              >
                <time
                  :datetime="day.date"
                  :class="[
                    day.isSelected
                      && 'flex size-6 items-center justify-center rounded-full',
                    day.isSelected && day.isToday && 'bg-indigo-600',
                    day.isSelected && !day.isToday && 'bg-gray-900',
                    'ml-auto',
                  ]"
                >
                  {{ day?.date?.split('-').pop().replace(/^0/, '') }}
                </time>
                <span class="sr-only"
                  >{{ day.slots ? day.slots.length : '0' }} slots</span
                >
                <span
                  v-if="day.slots && day.slots.length > 0"
                  class="-mx-0.5 mt-auto flex flex-wrap-reverse"
                >
                  <span
                    class="mx-0.5 mb-1 size-1.5 rounded-full bg-green-400"
                  />
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Create Event Modal -->
        <CreateCalendarEvent
          :open="showCreateEventModal"
          :close-create-event-page="closeCreateEventModal"
          :selected-date="selectedDate"
          :available-slots="selectedSlots"
          :mentor-program="mentorProgram"
          :selected-slot="selectedSlot"
          :rounding-minutes="props.roundingMinutes"
          :mentor_program_id="props.mentorProgram.id"
          style="z-index: 1000"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
