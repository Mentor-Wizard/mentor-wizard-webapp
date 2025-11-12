<script setup>
import { ClockIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

const props = defineProps({
  days: {
    type: Object,
    default: () => {},
  },
  scrollDate: {
    type: Function,
    default: () => {},
  },
  openShowEditEventPage: {
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

const selectedDay = computed(() => {
  if (!props.days.calendarView) return null;
  return Object.values(props.days.calendarView).find((day) => day?.isSelected);
});
</script>

<template>
  <div class="lg:flex lg:h-full lg:flex-col">
    <div class="shadow-sm ring-1 ring-black/5 lg:flex lg:flex-auto lg:flex-col">
      <div
        class="grid grid-cols-7 gap-px border-b border-gray-300 bg-gray-200 text-center text-xs/6 font-semibold text-gray-700 lg:flex-none"
      >
        <div
          v-for="weekDay in props.weekDays"
          :key="weekDay"
          class="bg-white py-2"
        >
          <div class="bg-white py-2">
            {{ weekDay.slice(0, 1)
            }}<span class="sr-only sm:not-sr-only">{{
              weekDay.slice(1, 3)
            }}</span>
          </div>
        </div>
      </div>
      <div class="flex bg-gray-200 text-xs/6 text-gray-700 lg:flex-auto">
        <div
          class="hidden w-full lg:grid lg:grid-cols-7 lg:grid-rows-6 lg:gap-px"
        >
          <div
            v-for="day in days.calendarView"
            :key="day.date"
            :class="[
              day.isCurrentMonth ? 'bg-white' : 'bg-gray-50 text-gray-500',
              'relative px-3 py-2',
            ]"
            @click="scrollDate('exact date', day.date)"
            @disabled="!day.events || day.events.length === 0"
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
            <ol v-if="day.events && day.events.length > 0" class="mt-2">
              <li v-for="event in day.events.slice(0, 2)" :key="event.id">
                <a
                  class="group flex"
                  @click.prevent.stop="props.openShowEditEventPage(event.id)"
                >
                  <p
                    class="flex-auto truncate font-medium text-gray-900 group-hover:text-indigo-600"
                  >
                    {{ event.name }}
                  </p>
                  <time
                    :datetime="event.datetime"
                    class="ml-3 hidden flex-none text-gray-500 group-hover:text-indigo-600 xl:block"
                  >
                    {{ event.time }}
                  </time>
                </a>
              </li>
              <li v-if="day.events.length > 2" class="text-gray-500">
                + {{ day.events.length - 2 }} more
              </li>
            </ol>
          </div>
        </div>
        <div
          class="isolate grid w-full grid-cols-7 grid-rows-6 gap-px lg:hidden"
        >
          <button
            v-for="day in days.calendarView"
            :key="day.date"
            type="button"
            :disabled="!day.events || day.events.length === 0"
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
            @click="scrollDate('exact date', day.date)"
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
              >{{ day.events ? day.events.length : '-' }} events</span
            >
            <span
              v-if="day.events && day.events.length > 0"
              class="-mx-0.5 mt-auto flex flex-wrap-reverse"
            >
              <span
                v-for="event in day.events"
                :key="event.id"
                class="mx-0.5 mb-1 size-1.5 rounded-full bg-gray-400"
              />
            </span>
          </button>
        </div>
      </div>
    </div>
    <div v-if="selectedDay?.events?.length > 0" class="px-4 py-10 sm:px-6">
      <ol
        class="divide-y divide-gray-100 overflow-hidden rounded-lg bg-white text-sm shadow-sm ring-1 ring-black/5"
      >
        <li
          v-for="event in selectedDay.events"
          :key="event.id"
          class="group flex p-4 pr-6 focus-within:bg-gray-50 hover:bg-gray-50"
        >
          <div class="flex-auto">
            <p class="font-semibold text-gray-900">{{ event.name }}</p>
            <time
              :datetime="event.datetime"
              class="mt-2 flex items-center text-gray-700"
            >
              <ClockIcon class="mr-2 size-5 text-gray-400" aria-hidden="true" />
              {{ event.time }}
            </time>
          </div>
          <a
            class="ml-6 flex-none self-center rounded-md bg-white px-3 py-2 font-semibold text-gray-900 opacity-0 shadow-xs ring-1 ring-gray-300 ring-inset group-hover:opacity-100 hover:ring-gray-400 focus:opacity-100"
            @click.prevent.stop="props.openShowEditEventPage(event.id)"
            >Edit<span class="sr-only"> {{ event.title }}</span></a
          >
        </li>
      </ol>
    </div>
  </div>
</template>
