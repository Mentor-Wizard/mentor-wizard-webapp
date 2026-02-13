<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import {
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  EllipsisHorizontalIcon,
} from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

import DailyView from '@/Components/Calendar/DailyView.vue';
import MonthlyView from '@/Components/Calendar/MonthlyView.vue';
import WeeklyView from '@/Components/Calendar/WeeklyView.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCalendar } from '@/Stores/calendar.js';
import { adjustDate, formatWeekRange } from '@/Stores/Calendar/helpers.js';

const props = defineProps({
  locale: {
    type: String,
    default: null,
  },
  permissions: {
    type: String,
    default: 'view',
  },
  calendarEvents: {
    type: Object,
    default: () => {},
  },
  availableColours: {
    type: Object,
    default: () => {},
  },
});
const locale = props.locale;

const timezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
const todayDate = ref(
  new Date().toLocaleDateString(String(locale || 'uk-UA'), {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    weekday: 'long',
  }),
);

const permissions = ref(props.permissions);
const daysData = ref(props.calendarEvents);
const calendar = useCalendar();
const { hours, weekDays } = storeToRefs(calendar);

const currentTab = ref('Month view');
const currentDate = ref(new Date());
const isLoading = ref(false);

const setTodayDate = () => {
  currentDate.value = new Date();
  refreshData();
};

const changeTab = (tab) => {
  currentTab.value = tab;
  refreshData();
};
const scrollDate = (direction, date = null) => {
  let adjustInfo = adjustDate(
    currentDate.value,
    currentTab.value,
    direction,
    date,
  );
  currentDate.value = adjustInfo.date;
  currentTab.value = adjustInfo.tab;
  isLoading.value = true;
  refreshData();
};

const refreshData = () => {
  router.visit(route('pages.calendar.index'), {
    method: 'get',
    data: {
      date: currentDate.value,
      mode: currentTab.value,
      timezone: timezone.value,
    },
    preserveState: true,
    only: ['calendarEvents'],
    onSuccess: (page) => {
      daysData.value = page.props.calendarEvents;
      permissions.value = page.props.permissions ?? 'view';
      isLoading.value = false;
    },
    onError: (errors) => {
      console.error('Failed to fetch calendar data:', errors);
      isLoading.value = false;
    },
  });
};

const openShowEditEventPage = (eventId) => {
  router.visit(
    route('pages.calendar.show', {
      calendarEvent: eventId,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    }),
    {
      method: 'get',
      preserveState: true,
      // only: ['events'],
      onSuccess: () => {
        console.log('edit page opened');
      },
      onError: (errors) => {
        console.error('Failed to fetch calendar data:', errors);
      },
    },
  );
};

const scrollButtonName = computed(() => {
  if (currentTab.value === 'Day view') {
    if (currentDate.value.toDateString() === new Date().toDateString()) {
      return 'Today';
    } else {
      return new Date(currentDate.value).toLocaleDateString(
        String(locale || 'uk-UA'),
        {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        },
      );
    }
  } else if (currentTab.value === 'Week view') {
    return formatWeekRange(locale, currentDate.value);
  } else if (currentTab.value === 'Month view') {
    return new Date(currentDate.value).toLocaleString(
      String(locale || 'uk-UA'),
      { month: 'short' },
    );
  } else {
    return 'Today';
  }
});

onMounted(() => {
  refreshData();
});
</script>
<template>
  <AuthenticatedLayout>
    <header
      class="flex flex-none items-center justify-between border-b border-gray-200 px-6 py-4"
    >
      <div>
        <h1 class="text-base font-semibold text-gray-900">
          <time class="sm:hidden">{{ todayDate + ' (' + timezone + ')' }}</time>
          <time class="sm:hidden">{{ todayDate + ' (' + timezone + ')' }}</time>
          <time class="hidden sm:inline">{{
            todayDate + ' (' + timezone + ')'
          }}</time>
        </h1>
      </div>

      <div v-if="timezone" class="flex items-center">
        <div
          class="relative flex items-center rounded-md bg-white shadow-xs md:items-stretch"
        >
          <button
            type="button"
            :disabled="
              !daysData['hasEventsBefore'] && currentTab === 'Month view'
            "
            :class="[
              'flex h-9 w-12 items-center justify-center rounded-l-md border-y border-l '
                + 'border-gray-300 pr-1 text-gray-400 focus:relative md:w-9 md:pr-0',
              daysData['hasEventsBefore'] ?
                'hover:text-gray-500 md:hover:bg-gray-50'
              : 'cursor-not-allowed',
            ]"
            @click="scrollDate('previous')"
          >
            <span class="sr-only">Previous day</span>
            <ChevronLeftIcon class="size-5" aria-hidden="true" />
          </button>
          <button
            type="button"
            class="hidden border-y border-gray-300 px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus:relative md:block"
          >
            {{ scrollButtonName }}
          </button>
          <span class="relative -mx-px h-5 w-px bg-gray-300 md:hidden" />
          <button
            type="button"
            :disabled="
              !daysData['hasEventsAfter'] && currentTab === 'Month view'
            "
            :class="[
              'flex h-9 w-12 items-center justify-center rounded-r-md border-y border-r '
                + 'border-gray-300 pl-1 text-gray-400 focus:relative md:w-9 md:pl-0',
              daysData['hasEventsAfter'] ?
                'hover:text-gray-500 md:hover:bg-gray-50'
              : 'cursor-not-allowed',
            ]"
            @click="scrollDate('next')"
          >
            <span class="sr-only">Next day</span>
            <ChevronRightIcon class="size-5" aria-hidden="true" />
          </button>
        </div>
        <div class="hidden md:ml-4 md:flex md:items-center">
          <Menu as="div" class="relative">
            <MenuButton
              type="button"
              class="flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
            >
              {{ currentTab }}
              <ChevronDownIcon
                class="-mr-1 size-5 text-gray-400"
                aria-hidden="true"
              />
            </MenuButton>

            <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <MenuItems
                class="absolute right-0 z-10 mt-3 w-36 origin-top-right overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden"
              >
                <div class="py-1">
                  <MenuItem v-slot="{ active }">
                    <a
                      :class="[
                        active ?
                          'bg-gray-100 text-gray-900 outline-hidden'
                        : 'text-gray-700',
                        'block px-4 py-2 text-sm',
                      ]"
                      @click="changeTab('Day view')"
                      >Day view</a
                    >
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a
                      :class="[
                        active ?
                          'bg-gray-100 text-gray-900 outline-hidden'
                        : 'text-gray-700',
                        'block px-4 py-2 text-sm',
                      ]"
                      @click="changeTab('Week view')"
                      >Week view</a
                    >
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a
                      :class="[
                        active ?
                          'bg-gray-100 text-gray-900 outline-hidden'
                        : 'text-gray-700',
                        'block px-4 py-2 text-sm',
                      ]"
                      @click="changeTab('Month view')"
                      >Month view</a
                    >
                  </MenuItem>
                </div>
              </MenuItems>
            </transition>
          </Menu>
        </div>
        <Menu as="div" class="relative ml-6 md:hidden">
          <MenuButton
            class="relative flex items-center rounded-full border border-transparent text-gray-400 outline-offset-8 hover:text-gray-500"
          >
            <span class="absolute -inset-2"></span>
            <span class="sr-only">Open menu</span>
            <EllipsisHorizontalIcon class="size-5" aria-hidden="true" />
          </MenuButton>

          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
          >
            <MenuItems
              class="absolute right-0 z-10 mt-3 w-36 origin-top-right divide-y divide-gray-100 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden"
            >
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <a
                    href="#"
                    :class="[
                      active ?
                        'bg-gray-100 text-gray-900 outline-hidden'
                      : 'text-gray-700',
                      'block px-4 py-2 text-sm',
                    ]"
                    >Create event</a
                  >
                </MenuItem>
              </div>
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <a
                    :class="[
                      active ?
                        'bg-gray-100 text-gray-900 outline-hidden'
                      : 'text-gray-700',
                      'block px-4 py-2 text-sm',
                    ]"
                    @click="setTodayDate()"
                    >Go to today</a
                  >
                </MenuItem>
              </div>
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <a
                    :class="[
                      active ?
                        'bg-gray-100 text-gray-900 outline-hidden'
                      : 'text-gray-700',
                      'block px-4 py-2 text-sm',
                    ]"
                    @click="changeTab('Day view')"
                    >Day view</a
                  >
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <a
                    :class="[
                      active ?
                        'bg-gray-100 text-gray-900 outline-hidden'
                      : 'text-gray-700',
                      'block px-4 py-2 text-sm',
                    ]"
                    @click="changeTab('Week view')"
                    >Week view</a
                  >
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <a
                    :class="[
                      active ?
                        'bg-gray-100 text-gray-900 outline-hidden'
                      : 'text-gray-700',
                      'block px-4 py-2 text-sm',
                    ]"
                    @click="changeTab('Month view')"
                    >Month view</a
                  >
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </div>
    </header>

    <MonthlyView
      v-if="currentTab === 'Month view'"
      :days="daysData"
      :week-days="weekDays"
      :scroll-date="scrollDate"
      :open-show-edit-event-page="openShowEditEventPage"
    />
    <WeeklyView
      v-if="currentTab === 'Week view'"
      :calendar-events="daysData"
      :scroll-date="scrollDate"
      :hours="hours"
      :week-days="weekDays"
      :open-show-edit-event-page="openShowEditEventPage"
    />
    <DailyView
      v-if="currentTab === 'Day view'"
      :days="daysData"
      :hours="hours"
      :week-days="weekDays"
      :scroll-date="scrollDate"
      :open-show-edit-event-page="openShowEditEventPage"
    />
  </AuthenticatedLayout>
</template>
