<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
import {
  getFormattedMonth,
  getTitleMonth,
  shownMonth,
} from '@modules/Calendar/resources/js/Stores/Calendar/helpers.js';
import { computed, onMounted, ref } from 'vue';

const container = ref(null);
const containerNav = ref(null);
const containerOffset = ref(null);
const filterDate = ref(new Date().toISOString());
const existNextMonthEvents = ref(true);
const existPreviousMonthEvents = ref(true);
const props = defineProps({
  days: {
    type: Object,
    default: () => {},
  },
  currentDate: {
    type: String,
    default: () =>
      new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      }),
  },
  openShowEditEventPage: {
    type: Function,
    default: () => {},
  },
  scrollDate: {
    type: Function,
    default: () => {},
  },
  hours: {
    type: Array,
    default: () => [],
  },
  weekDays: {
    type: Array,
    default: () => [],
  },
});

const checkNextMonthEvents = () => {
  const formattedDate = new Date(filterDate.value);
  const nextMonth = getFormattedMonth(
    new Date(
      formattedDate.setMonth(formattedDate.getMonth() + 1),
    ).toISOString(),
  );
  existNextMonthEvents.value = !!Object.prototype.hasOwnProperty.call(
    props.days?.calendarView,
    nextMonth,
  );
};

const checkPreviousMonthEvents = () => {
  const formattedDate = new Date(filterDate.value);
  const previousMonth = getFormattedMonth(
    new Date(
      formattedDate.setMonth(formattedDate.getMonth() - 1),
    ).toISOString(),
  );
  existPreviousMonthEvents.value = !!Object.prototype.hasOwnProperty.call(
    props.days?.calendarView,
    previousMonth,
  );
};

const getShownMonth = computed(() => {
  if (!props?.days.calendarView) {
    return {};
  }
  return props.days?.calendarView[getFormattedMonth(filterDate.value)];
});
const scrollMonth = (direction) => {
  const formattedDate = new Date(filterDate.value);
  if (direction === 'next') {
    filterDate.value = new Date(
      formattedDate.setMonth(formattedDate.getMonth() + 1),
    ).toISOString();
  } else if (direction === 'previous') {
    filterDate.value = new Date(
      formattedDate.setMonth(formattedDate.getMonth() - 1),
    ).toISOString();
  } else {
    console.log('wrong direction');
  }
  checkNextMonthEvents();
  checkPreviousMonthEvents();
};

onMounted(() => {
  const currentMinute = new Date().getHours() * 60;
  shownMonth.value = new Date(props.currentDate)
    .toISOString()
    .split('T')[0]
    .slice(0, 7);
  filterDate.value = new Date().toISOString();
  container.value.scrollTop =
    ((container.value.scrollHeight
      - containerNav.value.offsetHeight
      - containerOffset.value.offsetHeight)
      * currentMinute)
    / 1440;
  checkNextMonthEvents();
  checkPreviousMonthEvents();
});
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="isolate flex flex-auto overflow-hidden bg-white">
      <div ref="container" class="flex flex-auto flex-col overflow-auto">
        <div
          ref="containerNav"
          class="sticky top-0 z-10 grid flex-none grid-cols-7 bg-white text-xs text-gray-500 shadow-sm ring-1 ring-black/5 md:hidden"
        >
          <button
            v-for="weekDay in weekDays"
            :key="weekDay"
            type="button"
            class="flex flex-col items-center pt-3 pb-1.5"
          >
            {{ weekDay }}
          </button>
        </div>
        <div class="flex w-full flex-auto">
          <div class="w-14 flex-none bg-white ring-1 ring-gray-100" />
          <div class="grid flex-auto grid-cols-1 grid-rows-1">
            <!-- Horizontal lines -->
            <div
              class="col-start-1 col-end-2 row-start-1 grid divide-y divide-gray-100"
              style="grid-template-rows: repeat(48, minmax(3.5rem, 1fr))"
            >
              <div ref="containerOffset" class="row-end-1 h-7"></div>
              <div v-for="hour in hours" :key="hour">
                <div
                  class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400"
                >
                  {{ hour }}
                </div>
              </div>
              <div />
            </div>

            <!-- Events -->
            <ol
              class="col-start-1 col-end-2 row-start-1 grid grid-cols-1"
              style="
                grid-template-rows: 1.75rem repeat(288, minmax(0, 1fr)) auto;
              "
            >
              <template v-for="event in days.calendarEvents" :key="event.id">
                <li
                  class="relative mt-px flex"
                  :style="{
                    'grid-row': `${event.startIndex} / span ${event.durationIndex}`,
                  }"
                >
                  <a
                    :class="`group absolute inset-1 flex flex-col overflow-hidden rounded-md bg-${event.colour}-50 px-1.5 py-0.5 text-xs leading-tight hover:bg-${event.colour}-100`"
                    @click="openShowEditEventPage(event.id)"
                  >
                    <p
                      :class="`truncate font-semibold text-${event.colour}-700`"
                    >
                      {{ event.title }}
                    </p>
                    <p
                      :class="`truncate text-${event.colour}-500 group-hover:text-${event.colour}-700`"
                    >
                      <time :datetime="`${event.dateTime}`">{{
                        event.time
                      }}</time>
                    </p>
                  </a>
                </li>
              </template>
            </ol>
          </div>
        </div>
      </div>
      <div
        class="hidden w-1/2 max-w-md flex-none border-l border-gray-100 px-8 py-10 md:block"
      >
        <div class="flex items-center text-center text-gray-900">
          <button
            v-if="existPreviousMonthEvents"
            type="button"
            class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500"
            @click="scrollMonth('previous')"
          >
            <span class="sr-only">Previous month</span>
            <ChevronLeftIcon class="size-5" aria-hidden="true" />
          </button>
          <div class="flex-auto text-sm font-semibold">
            {{ getTitleMonth(filterDate) }}
          </div>
          <button
            v-if="existNextMonthEvents"
            type="button"
            class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500"
            @click="scrollMonth('next')"
          >
            <span class="sr-only">Next month</span>
            <ChevronRightIcon class="size-5" aria-hidden="true" />
          </button>
        </div>
        <div class="mt-6 grid grid-cols-7 text-center text-xs/6 text-gray-500">
          <div v-for="weekDay in weekDays" :key="weekDay">
            {{ weekDay }}
          </div>
        </div>
        <div
          v-if="getShownMonth"
          class="isolate mt-2 grid grid-cols-7 gap-px rounded-lg bg-gray-200 text-sm shadow-sm ring-1 ring-gray-200"
        >
          <button
            v-for="(day, dayIdx) in getShownMonth"
            :key="day.date"
            type="button"
            :disabled="!day.hasEvent"
            :class="[
              'py-1.5 hover:bg-gray-100 focus:z-10',
              day.isCurrentMonth || day?.hasEvent ? 'bg-white' : 'bg-gray-50',
              (day.isSelected || day.isToday) && 'font-semibold',
              day.isSelected && 'text-white',
              (day.hasEvent
                || (!day.isSelected && day.isCurrentMonth && !day.isToday))
                && 'text-gray-900',
              !day.isSelected
                && !day.isCurrentMonth
                && !day.isToday
                && 'text-gray-400',
              day.isToday && !day.isSelected && 'text-indigo-600',
              dayIdx === 0 && 'rounded-tl-lg',
              dayIdx === 6 && 'rounded-tr-lg',
              dayIdx === days.length - 7 && 'rounded-bl-lg',
              dayIdx === days.length - 1 && 'rounded-br-lg',
            ]"
            @click="scrollDate('exact date', day.date)"
          >
            <time
              :datetime="day.date"
              :class="[
                'mx-auto flex size-7 items-center justify-center rounded-full',
                day.isSelected && day.isToday && 'bg-indigo-600',
                day.isSelected && !day.isToday && 'bg-gray-900',
                day.hasEvent && 'bg-gray-100',
              ]"
            >
              {{ day.date?.split('-').pop().replace(/^0/, '') }}
            </time>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
