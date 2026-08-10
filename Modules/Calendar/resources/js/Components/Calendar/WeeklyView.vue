<script setup>
import { onMounted, ref } from 'vue';

const container = ref(null);
const containerNav = ref(null);
const containerOffset = ref(null);
onMounted(() => {
  const currentMinute = new Date().getHours() * 60;
  container.value.scrollTop =
    ((container.value.scrollHeight
      - containerNav.value.offsetHeight
      - containerOffset.value.offsetHeight)
      * currentMinute)
    / 1440;
});
const props = defineProps({
  calendarEvents: {
    type: Object,
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
  openShowEditEventPage: {
    type: Function,
    default: () => {},
  },
  scrollDate: {
    type: Function,
    default: () => {},
  },
});

const colorClasses = {
  blue: {
    bg: 'bg-blue-50 hover:bg-blue-100',
    title: 'text-blue-700',
    time: 'text-blue-500 group-hover:text-blue-700',
  },
  indigo: {
    bg: 'bg-indigo-50 hover:bg-indigo-100',
    title: 'text-indigo-700',
    time: 'text-indigo-500 group-hover:text-indigo-700',
  },
  emerald: {
    bg: 'bg-emerald-50 hover:bg-emerald-100',
    title: 'text-emerald-700',
    time: 'text-emerald-500 group-hover:text-emerald-700',
  },
  amber: {
    bg: 'bg-amber-50 hover:bg-amber-100',
    title: 'text-amber-700',
    time: 'text-amber-500 group-hover:text-amber-700',
  },
  rose: {
    bg: 'bg-rose-50 hover:bg-rose-100',
    title: 'text-rose-700',
    time: 'text-rose-500 group-hover:text-rose-700',
  },
  gray: {
    bg: 'bg-gray-50 hover:bg-gray-100',
    title: 'text-gray-700',
    time: 'text-gray-500 group-hover:text-gray-700',
  },
};
</script>

<template>
  <div class="flex h-full flex-col">
    <div
      ref="container"
      class="isolate flex flex-auto flex-col overflow-auto bg-white"
    >
      <div
        style="width: 165%"
        class="flex max-w-full flex-none flex-col sm:max-w-none md:max-w-full"
      >
        <div
          ref="containerNav"
          class="sticky top-0 z-30 flex-none bg-white shadow-sm ring-1 ring-black/5 sm:pr-8"
        >
          <div class="grid grid-cols-7 text-sm/6 text-gray-500 sm:hidden">
            <div
              v-for="(day, dayIdx) in calendarEvents.calendarView"
              :key="day.date"
            >
              <button
                type="button"
                :disabled="!day.hasEvents"
                class="flex flex-col items-center pt-2 pb-3"
                @click="scrollDate('exact date', day.date)"
              >
                {{ props?.weekDays[dayIdx]?.slice(0, 1) }}
                <span
                  class="mt-1 flex size-8 items-center justify-center font-semibold text-gray-900"
                  >{{ new Date(day.date).getDate() }}</span
                >
              </button>
            </div>
          </div>

          <div
            class="-mr-px hidden grid-cols-7 divide-x divide-gray-100 border-r border-gray-100 text-sm/6 text-gray-500 sm:grid"
          >
            <div class="col-end-1 w-14" />
            <div
              v-for="(day, dayIdx) in calendarEvents.calendarView"
              :key="day.date"
            >
              <span
                :disabled="!day.hasEvents"
                @click="scrollDate('exact date', day.date)"
                >{{ props.weekDays[dayIdx] }}
                <span
                  class="items-center justify-center font-semibold text-gray-900"
                >
                  {{ new Date(day.date).getDate() }}</span
                >
              </span>
            </div>
          </div>
        </div>
        <div class="flex flex-auto">
          <div
            class="sticky left-0 z-10 w-14 flex-none bg-white ring-1 ring-gray-100"
          />
          <div class="grid flex-auto grid-cols-1 grid-rows-1">
            <!-- Horizontal lines -->
            <div
              class="col-start-1 col-end-2 row-start-1 grid divide-y divide-gray-100"
              style="grid-template-rows: repeat(48, minmax(3.5rem, 1fr))"
            >
              <div ref="containerOffset" class="row-end-1 h-7" />
              <div v-for="hour in hours" :key="hour">
                <div
                  class="sticky left-0 z-20 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400"
                >
                  {{ hour }}
                </div>
              </div>
              <div />
            </div>

            <!-- Vertical lines -->
            <div
              class="col-start-1 col-end-2 row-start-1 hidden grid-cols-7 grid-rows-1 divide-x divide-gray-100 sm:grid sm:grid-cols-7"
            >
              <div class="col-start-1 row-span-full" />
              <div class="col-start-2 row-span-full" />
              <div class="col-start-3 row-span-full" />
              <div class="col-start-4 row-span-full" />
              <div class="col-start-5 row-span-full" />
              <div class="col-start-6 row-span-full" />
              <div class="col-start-7 row-span-full" />
              <div class="col-start-8 row-span-full w-8" />
            </div>

            <!-- Events -->
            <ol
              class="col-start-1 col-end-2 row-start-1 grid grid-cols-1 sm:grid-cols-7 sm:pr-8"
              style="
                grid-template-rows: 1.75rem repeat(288, minmax(0, 1fr)) auto;
              "
            >
              <template
                v-for="event in calendarEvents.calendarEvents"
                :key="event.id"
              >
                <li
                  :class="[
                    'relative mt-px flex',
                    `sm:col-start-${event.dayNumber}`,
                  ]"
                  :style="{
                    'grid-row': `${event.startIndex} / span ${event.durationIndex}`,
                  }"
                >
                  <a
                    :class="[
                      'group absolute inset-1 flex flex-col overflow-hidden rounded-md px-1.5 py-0.5 text-xs leading-tight',
                      colorClasses[event.colour]?.bg ?? colorClasses.blue.bg,
                    ]"
                    @click="props.openShowEditEventPage(event.id)"
                  >
                    <p
                      :class="[
                        'truncate font-semibold',
                        colorClasses[event.colour]?.title
                          ?? colorClasses.blue.title,
                      ]"
                    >
                      {{ event.title }}
                    </p>
                    <p
                      :class="[
                        'truncate',
                        colorClasses[event.colour]?.time
                          ?? colorClasses.blue.time,
                      ]"
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
    </div>
  </div>
</template>
