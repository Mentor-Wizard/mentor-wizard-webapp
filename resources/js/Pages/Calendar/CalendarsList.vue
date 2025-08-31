<script setup lang="ts">
import DailyView from "@/Components/Calendar/DailyView.vue";
import MonthlyView from "@/Components/Calendar/MonthlyView.vue";
import WeeklyView from "@/Components/Calendar/WeeklyView.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon, EllipsisHorizontalIcon} from "@heroicons/vue/20/solid";
import {computed, ref} from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router, usePage} from "@inertiajs/vue3";
import CreateEvent from "@/Pages/Calendar/CreateEvent.vue";

const locale = usePage().props.locale;
const todayDate = ref(new Date().toLocaleDateString(String
(locale || "uk-UA"), {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    weekday: 'long',
    timeZone: 'UTC'
}));
const showCreatePage = ref(false);
const permissions = ref(usePage().props.permissions)
const daysData = ref(usePage().props.events);
const hours = ["12AM", "1AM", "2AM", "3AM", "4AM", "5AM", "6AM", "7AM", "8AM", "9AM", "10AM", "11AM",
    "12PM", "1PM", "2PM", "3PM", "4PM", "5PM", "6PM", "7PM", "8PM", "9PM", "10PM", "11PM"];

const weekDays = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
const currentTab = ref('Month view');
const currentDate = ref(new Date());
const isLoading = ref(false);

const setTodayDate = () => {
    currentDate.value = new Date();
    refreshData();
};

const changeTab = (tab: string) => {
    currentTab.value = tab;
    refreshData();
}
const scrollDate = (direction: string, date = null) => {
    adjustDate(direction, date);
    isLoading.value = true;
    refreshData();
}

const refreshData = () => {
    console.log(permissions.value);
    router.visit(route('pages.calendar'), {
        method: 'get',
        data: {
            'date': currentDate.value,
            'mode': currentTab.value,
            'timezone': "UTC"
        },
        preserveState: true,
        only: ['events'],
        onSuccess: (page) => {
            daysData.value = page.props.events;
            permissions.value = page.props.permissions??'view';
            isLoading.value = false;
        },
        onError: (errors) => {
            console.error('Failed to fetch calendar data:', errors);
            isLoading.value = false;
        }
    });
}

const openCreateEventPage = () => {
    showCreatePage.value = true;
}
const openShowEditEventPage = (eventId) => {
    console.log(eventId);
    console.log("open show edit event page");

    router.visit(route('pages.calendar.show',{ id: eventId }), {
        method: 'get',
        preserveState: true,
        // only: ['events'],
        onSuccess: () => {
            console.log('edit page opened')
        },
        onError: (errors) => {
            console.error('Failed to fetch calendar data:', errors);
        }
    });
}
const closeCreateEventPage = () => {
    showCreatePage.value = false;
}

const adjustDate = (direction: string, exactDate: string | null) => {
    const newDate = new Date(currentDate.value);
    switch (currentTab.value) {
        case 'Day view':
            if (direction === 'previous') {
                newDate.setDate(currentDate.value.getDate() - 1);
            } else if (direction === 'next') {
                newDate.setDate(currentDate.value.getDate() + 1);
            } else if (direction === "exact date") {
                newDate.setDate(new Date(exactDate).getDate());
                newDate.setMonth(new Date(exactDate).getMonth());
                newDate.setFullYear(new Date(exactDate).getFullYear());
            }
            break;
        case 'Week view':
            if (direction === 'previous') {
                newDate.setDate(currentDate.value.getDate() - 7);
            } else if (direction === 'next') {
                newDate.setDate(currentDate.value.getDate() + 7);
            } else if (direction === "exact date") {
                newDate.setDate(new Date(exactDate).getDate());
                newDate.setMonth(new Date(exactDate).getMonth());
                newDate.setFullYear(new Date(exactDate).getFullYear());
                currentTab.value = 'Day view';
            }
            break;
        case 'Month view':
            if (direction === 'previous') {
                newDate.setMonth(currentDate.value.getMonth() - 1);
            } else if (direction === 'next') {
                newDate.setMonth(currentDate.value.getMonth() + 1);
            } else if (direction === "exact date") {
                newDate.setDate(new Date(exactDate).getDate());
                newDate.setMonth(new Date(exactDate).getMonth());
                newDate.setFullYear(new Date(exactDate).getFullYear());
                currentTab.value = 'Day view';
            }
            break;
        default:
            console.error('Invalid unit specified');
            return;
    }
    currentDate.value = newDate;
};

const formatWeekRange = () => {
    const d = new Date(currentDate.value);
    const day = d.getDay() === 0 ? 7 : d.getDay();
    const start = new Date(d);
    start.setHours(0, 0, 0, 0);
    start.setDate(d.getDate() - (day - 1));

    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    const localeValue = typeof locale === 'string' ? locale : String(locale || "uk-UA");
    const monthFmt = new Intl.DateTimeFormat(localeValue, {month: "short"});
    const pad2 = (n: number) => String(n).padStart(2, "0");
    const cleanMonth = (m: string) => m.replace(/\.$/, "");
    const startLabel = `${pad2(start.getDate())} ${cleanMonth(monthFmt.format(start))}`;
    const endLabel = `${pad2(end.getDate())} ${cleanMonth(monthFmt.format(end))}`;
    return `${startLabel} - ${endLabel}`;
}

const scrollButtonName = computed(() => {
    if (currentTab.value === 'Day view') {
        if (currentDate.value.toDateString() === new Date().toDateString()) {
            return "Today"
        } else {
            return new Date(currentDate.value).toLocaleDateString(String
            (locale || "uk-UA"), {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }
    } else if (currentTab.value === 'Week view') {
        return formatWeekRange();
    } else if (currentTab.value === 'Month view') {
        return new Date(currentDate.value).toLocaleString(String
        (locale || "uk-UA"), {month: 'short'});
    } else {
        return "Today"
    }
})

</script>
<template>
    <AuthenticatedLayout>
        <header class="flex flex-none items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h1 class="text-base font-semibold text-gray-900">
                    <time datetime="2022-01-22" class="sm:hidden">{{ todayDate }}</time>
                    <time datetime="2022-01-22" class="hidden sm:inline">{{ todayDate }}</time>
                </h1>
            </div>
            <div class="flex items-center">
                <div class="relative flex items-center rounded-md bg-white shadow-xs md:items-stretch">
                    <button type="button" @click="scrollDate('previous')"
                            class="flex h-9 w-12 items-center justify-center rounded-l-md border-y border-l border-gray-300 pr-1 text-gray-400 hover:text-gray-500 focus:relative md:w-9 md:pr-0 md:hover:bg-gray-50">
                        <span class="sr-only">Previous day</span>
                        <ChevronLeftIcon class="size-5" aria-hidden="true"/>
                    </button>
                    <button type="button"
                            class="hidden border-y border-gray-300 px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus:relative md:block">
                        {{ scrollButtonName }}
                    </button>
                    <span class="relative -mx-px h-5 w-px bg-gray-300 md:hidden"/>
                    <button type="button" @click="scrollDate('next')"
                            class="flex h-9 w-12 items-center justify-center rounded-r-md border-y border-r border-gray-300 pl-1 text-gray-400 hover:text-gray-500 focus:relative md:w-9 md:pl-0 md:hover:bg-gray-50">
                        <span class="sr-only">Next day</span>
                        <ChevronRightIcon class="size-5" aria-hidden="true"/>
                    </button>
                </div>
                <div class="hidden md:ml-4 md:flex md:items-center">
                    <Menu as="div" class="relative">
                        <MenuButton type="button"
                                    class="flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                            {{ currentTab }}
                            <ChevronDownIcon class="-mr-1 size-5 text-gray-400" aria-hidden="true"/>
                        </MenuButton>

                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-3 w-36 origin-top-right overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="changeTab('Day view')"
                                           :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Day
                                            view</a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="changeTab('Week view')"
                                           :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Week
                                            view</a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="changeTab('Month view')"
                                           :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Month
                                            view</a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <div class="ml-6 h-6 w-px bg-gray-300"/>
                    <button type="button" v-if="permissions === 'edit'"
                            @click="openCreateEventPage()"
                            class="ml-6 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                        Add event
                    </button>
                </div>
                <Menu as="div" class="relative ml-6 md:hidden">
                    <MenuButton
                        class="relative flex items-center rounded-full border border-transparent text-gray-400 outline-offset-8 hover:text-gray-500">
                        <span class="absolute -inset-2"></span>
                        <span class="sr-only">Open menu</span>
                        <EllipsisHorizontalIcon class="size-5" aria-hidden="true"/>
                    </MenuButton>

                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="absolute right-0 z-10 mt-3 w-36 origin-top-right divide-y divide-gray-100 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a href="#"
                                       :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Create
                                        event</a>
                                </MenuItem>
                            </div>
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a @click="setTodayDate()"
                                       :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Go
                                        to today</a>
                                </MenuItem>
                            </div>
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a @click="changeTab('Day view')"
                                       :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Day
                                        view</a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a @click="changeTab('Week view')"
                                       :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Week
                                        view</a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a @click="changeTab('Month view')"
                                       :class="[active ? 'bg-gray-100 text-gray-900 outline-hidden' : 'text-gray-700', 'block px-4 py-2 text-sm']">Month
                                        view</a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </header>
        <CreateEvent
            :open="showCreatePage"
            :closeCreateEventPage="closeCreateEventPage"
        />

        <MonthlyView
            :days="daysData"
            :scrollDate="scrollDate"
            :openShowEditEventPage = "openShowEditEventPage"
            v-if="currentTab === 'Month view'"/>
        <WeeklyView
            :events="daysData"
            :scrollDate="scrollDate"
            :hours="hours"
            :weekDays="weekDays"
            :openShowEditEventPage = "openShowEditEventPage"
            v-if="currentTab === 'Week view'"/>
        <DailyView
            :days="daysData"
            :hours="hours"
            :weekDays="weekDays"
            :scrollDate="scrollDate"
            :openShowEditEventPage = "openShowEditEventPage"
            v-if="currentTab === 'Day view'"/>
    </AuthenticatedLayout>
</template>
