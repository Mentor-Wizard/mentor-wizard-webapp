<script setup lang="ts">
import {ref, onMounted, computed, watch} from 'vue'
import {router, useForm, usePage} from "@inertiajs/vue3";

import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption
} from '@headlessui/vue'
import {
    XMarkIcon,
    ChevronUpDownIcon,
    CheckIcon,
    CalendarIcon,
    ClockIcon,
    UserIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const timeZone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
const mode = ref('show');
const permissions = ref('permissions');
const event = ref<EventFormData>({
    id: '',
    title: '',
    fromDate: '',
    fromDateFormatted: '',
    toDateFormatted: '',
    fromTime: '',
    toTime: '',
    type: 'Individual',
    description: '',
    timezone: timeZone
});


interface EventFormData {
    title: string;
    fromDate: string;
    fromDateFormatted: string;
    toDate: string;
    toDateFormatted: string;
    fromTime: string;
    toTime: string;
    description: string;
    duration: string;
    type: 'Group' | 'Individual';
    timeZone: string;
}

const changeMode = (newMode: string) => {
    console.log(newMode)
    mode.value = newMode;
}

const deleteEvent = () => {
    form.delete(route("pages.calendar.delete", {id: event.value.id}), {
        onSuccess: () => {
            form.reset();
            handleClose();
        },
        onError: (serverErrors) => {
            console.log('Server validation errors:', serverErrors);
        },
    })
}

const errors = ref({
    "toDate": null,
    "fromTime": null,
    "fromDate": null,
    "toTime": null,
    "title": null,
    'description': null
});

let form = useForm({
    title: '',
    fromDate: '',
    toDate: '',
    fromTime: '09:00',
    toTime: '10:00',
    type: 'Individual',
    description: '',
    timezone: timeZone
});

const eventTypes = [
    {value: 'Individual', label: 'Individual', icon: UserIcon},
    {value: 'Group', label: 'Group', icon: UserGroupIcon}
];

onMounted(() => {
    const now = new Date();
    const eventData = usePage().props.event;

    console.log(eventData);
    if (eventData && eventData.length > 0) {
        event.value = eventData[0];
        permissions.value = usePage().props.permissions;

        Object.assign(form, {
            title: eventData[0].title || '',
            fromDate: eventData[0].fromDate || '',
            toDate: eventData[0].toDate || '',
            fromTime: eventData[0].fromTime || '',
            toTime: eventData[0].toTime || '',
            type: eventData[0].type || '',
            description: eventData[0].description || ''
        });
    }
});

const selectedEventType = computed(() =>
    eventTypes.find(type => type.value === form.type)
);

const isFormValid = computed(() => {
    return form.title.trim() &&
        form.fromDate &&
        form.toDate &&
        form.fromTime &&
        form.toTime;
});

const validateForm = () => {
    errors.value = {fromTime: null, fromDate: null, title: null, toDate: null, toTime: null, description: null};

    if (!form.title.trim()) {
        errors.value.title = 'Title is required';
    }

    if (!form.fromDate) {
        errors.value.fromDate = 'Start date is required';
    }

    if (!form.toDate) {
        errors.value.toDate = 'End date is required';
    }

    if (!form.fromTime) {
        errors.value.fromTime = 'Start time is required';
    }

    if (!form.toTime) {
        errors.value.toTime = 'End time is required';
    }

    if (form.description.length > 1000) {
        errors.value.description = 'Description is more than 1000 characters';
    }

    if (form.fromDate && form.toDate) {
        const fromDateTime = new Date(`${form.fromDate}T${form.fromTime}`);
        const toDateTime = new Date(`${form.toDate}T${form.toTime}`);

        if (fromDateTime >= toDateTime) {
            errors.value.toDate = 'End date/time must be after start date/time';
        }
    }
    console.log(errors.value);
    let errorStatus = false;

    Object.keys(errors.value).forEach(key => {
        console.log(key);
        if (errors.value[key]) {
            errorStatus = true;
        }
    });

    return !errorStatus;
};

const handleSubmit = () => {
    if (validateForm()) {
        form.patch(route("pages.calendar.edit", {id: event.value.id}), {
            onSuccess: () => {
                form.reset();
                handleClose();
            },
            onError: (serverErrors) => {
                console.log('Server validation errors:', serverErrors);
                Object.keys(serverErrors).forEach(field => {
                    if (errors.value.hasOwnProperty(field)) {
                        errors.value[field] = serverErrors[field];
                    }
                });
            },
        });
    }
};

const handleClose = () => {
    console.log('close')
    router.visit(route('pages.calendar'), {})
};

watch(() => form.fromDate, (newFromDate) => {
    if (newFromDate && form.toDate < newFromDate) {
        form.toDate = newFromDate;
    }
});

watch(() => form.fromTime, (newFromTime) => {
    if (newFromTime && form.fromDate === form.toDate) {
        const [hours, minutes] = newFromTime.split(':').map(Number);
        const newEndTime = new Date();
        newEndTime.setHours(hours + 1, minutes);
        form.toTime = newEndTime.toTimeString().slice(0, 5);
    }
});
</script>

<template>
    <TransitionRoot as="template" :show=true>
        <Dialog as="div" class="relative z-50" @close="handleClose">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"/>
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                            <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                                <button
                                    type="button"
                                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="handleClose"
                                >
                                    <span class="sr-only">Close</span>
                                    <XMarkIcon class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>

                            <div v-if="mode === 'edit'" class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:ml-0 sm:mt-0 sm:text-left w-full">
                                    <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900 mb-4">
                                        Edit Event
                                    </DialogTitle>

                                    <form @submit.prevent="handleSubmit" class="space-y-4">
                                        <div>
                                            <label for="title"
                                                   class="block text-sm font-medium leading-6 text-gray-900">
                                                Event Title
                                            </label>
                                            <div class="mt-2">
                                                <input
                                                    id="title"
                                                    v-model="form.title"
                                                    type="text"
                                                    class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    :class="{ 'ring-red-300': errors.title }"
                                                    placeholder="Enter event title"
                                                />
                                                <p v-if="errors.title" class="mt-2 text-sm text-red-600">{{
                                                        errors.title
                                                    }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="title"
                                                   class="block text-sm font-medium leading-6 text-gray-900">
                                                Description
                                            </label>
                                            <div class="mt-2">
                                                <input
                                                    id="title"
                                                    v-model="form.description"
                                                    type="text"
                                                    class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    :class="{ 'ring-red-300': errors.description }"
                                                    placeholder="Enter event title"
                                                />
                                                <p v-if="errors.description" class="mt-2 text-sm text-red-600">{{
                                                        errors.description
                                                    }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium leading-6 text-gray-900">
                                                Event Type
                                            </label>
                                            <Listbox v-model="form.type">
                                                <div class="relative mt-2">
                                                    <ListboxButton
                                                        class="relative w-full cursor-default rounded-lg bg-white pl-2 py-2 pl-3 pr-10 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm border border-gray-300">
                                                    <span
                                                        class="flex items-center">
                                                            <component :is="selectedEventType?.icon"
                                                                       class="h-5 w-5 text-gray-400 mr-3"/>
                                                            <span class="block truncate">{{
                                                                    selectedEventType?.label
                                                                }}</span>
                                                    </span>
                                                        <span
                                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                            <ChevronUpDownIcon class="h-5 w-5 text-gray-400"
                                                                               aria-hidden="true"/>
                                                        </span>
                                                    </ListboxButton>
                                                    <transition
                                                        leave-active-class="transition duration-100 ease-in"
                                                        leave-from-class="opacity-100"
                                                        leave-to-class="opacity-0">
                                                        <ListboxOptions
                                                            class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white pl-2 py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm z-10">
                                                            <ListboxOption
                                                                v-for="type in eventTypes"
                                                                :key="type.value"
                                                                v-slot="{ active, selected }"
                                                                :value="type.value"
                                                                as="template"
                                                            >
                                                                <li :class="[active ? 'bg-amber-100 text-amber-900' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-10 pr-4']">
                                                          <span
                                                              :class="[selected ? 'font-medium' : 'font-normal', 'flex items-center truncate']">
                                                              <component :is="type.icon" class="h-5 w-5 mr-3"/>
                                                                {{ type.label }}
                                                          </span>
                                                                    <span v-if="selected"
                                                                          class="absolute inset-y-0 left-0 flex items-center pl-3 text-amber-600">
                                                              <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                                                           </span>
                                                                </li>
                                                            </ListboxOption>
                                                        </ListboxOptions>
                                                    </transition>
                                                </div>
                                            </Listbox>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="from-date"
                                                       class="block text-sm font-medium leading-6 text-gray-900">
                                                    <CalendarIcon class="inline h-4 w-4 mr-1"/>
                                                    From Date
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="from-date"
                                                        v-model="form.fromDate"
                                                        type="date"
                                                        class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        :class="{ 'ring-red-300': errors.fromDate }"
                                                    />
                                                    <p v-if="errors.fromDate" class="mt-1 text-sm text-red-600">
                                                        {{ errors.fromDate }}</p>
                                                </div>
                                            </div>

                                            <div>
                                                <label for="to-date"
                                                       class="block text-sm font-medium leading-6 text-gray-900">
                                                    <CalendarIcon class="inline h-4 w-4 mr-1"/>
                                                    To Date
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="to-date"
                                                        v-model="form.toDate"
                                                        type="date"
                                                        :min="form.fromDate"
                                                        class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        :class="{ 'ring-red-300': errors.toDate }"
                                                    />
                                                    <p v-if="errors.toDate" class="mt-1 text-sm text-red-600">
                                                        {{ errors.toDate }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="from-time"
                                                       class="block text-sm font-medium leading-6 text-gray-900">
                                                    <ClockIcon class="inline h-4 w-4 mr-1"/>
                                                    From Time
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="from-time"
                                                        v-model="form.fromTime"
                                                        type="time"
                                                        class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        :class="{ 'ring-red-300': errors.fromTime }"
                                                    />
                                                    <p v-if="errors.fromTime" class="mt-1 text-sm text-red-600">
                                                        {{ errors.fromTime }}</p>
                                                </div>
                                            </div>

                                            <div>
                                                <label for="to-time"
                                                       class="block text-sm font-medium leading-6 text-gray-900">
                                                    <ClockIcon class="inline h-4 w-4 mr-1"/>
                                                    To Time
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="to-time"
                                                        v-model="form.toTime"
                                                        type="time"
                                                        class="block w-full rounded-md border-0 pl-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        :class="{ 'ring-red-300': errors.toTime }"
                                                    />
                                                    <p v-if="errors.toTime" class="mt-1 text-sm text-red-600">
                                                        {{ errors.toTime }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!--                            <div v-if="mode === 'show'" class="sm:flex sm:items-start">-->
                            <!--                                <div class="mt-3 text-center sm:ml-0 sm:mt-0 sm:text-left w-full">-->
                            <!--                                    <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900 mb-4">-->
                            <!--                                        Event Record-->
                            <!--                                    </DialogTitle>-->
                            <!--                                        <div>-->
                            <!--                                            <label for="title"-->
                            <!--                                                   class="block text-sm font-medium leading-6 text-gray-900">-->
                            <!--                                                Event Title-->
                            <!--                                            </label>-->
                            <!--                                            <div class="mt-2">-->
                            <!--                                                {{event.title}}-->
                            <!--                                            </div>-->
                            <!--                                        </div>-->
                            <!--                                        <div>-->
                            <!--                                            <label for="title"-->
                            <!--                                                   class="block text-sm font-medium leading-6 text-gray-900">-->
                            <!--                                                Description-->
                            <!--                                            </label>-->
                            <!--                                            <div class="mt-2">-->
                            <!--                                                {{event.description}}-->
                            <!--                                            </div>-->
                            <!--                                        </div>-->
                            <!--                                        <div>-->
                            <!--                                            <label class="block text-sm font-medium leading-6 text-gray-900">-->
                            <!--                                                Event Type-->
                            <!--                                            </label>-->
                            <!--                                            <div class="relative mt-2">-->
                            <!--                                             {{event.type}}-->
                            <!--                                        </div>-->
                            <!--                                        </div>-->

                            <!--                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">-->
                            <!--                                            <div>-->
                            <!--                                                <label for="from-date"-->
                            <!--                                                       class="block text-sm font-medium leading-6 text-gray-900">-->
                            <!--                                                    <CalendarIcon class="inline h-4 w-4 mr-1"/>-->
                            <!--                                                    From Date-->
                            <!--                                                </label>-->
                            <!--                                                <div class="mt-2">-->
                            <!--                                                    {{event.fromDate}}-->
                            <!--                                                </div>-->
                            <!--                                            </div>-->

                            <!--                                            <div>-->
                            <!--                                                <label for="to-date"-->
                            <!--                                                       class="block text-sm font-medium leading-6 text-gray-900">-->
                            <!--                                                    <CalendarIcon class="inline h-4 w-4 mr-1"/>-->
                            <!--                                                    To Date-->
                            <!--                                                </label>-->
                            <!--                                                <div class="mt-2">-->
                            <!--                                                   {{event.toDate}}-->
                            <!--                                                </div>-->
                            <!--                                            </div>-->
                            <!--                                        </div>-->
                            <!--                                </div>-->
                            <!--                            </div>-->

                            <div v-if="mode === 'show'" class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:ml-0 sm:mt-0 sm:text-left w-full">
                                    <DialogTitle as="h3"
                                                 class="text-lg font-semibold leading-6 text-gray-900 mb-6 pb-2 border-b border-gray-200">
                                        <CalendarIcon class="inline h-5 w-5 mr-2 text-indigo-600"/>
                                        Event Details
                                    </DialogTitle>

                                    <div class="space-y-6">
                                        <!-- Event Title -->
                                        <div
                                            class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 border border-indigo-100">
                                            <div class="flex items-center mb-2">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                        <span class="text-indigo-600 font-semibold text-sm">T</span>
                                                    </div>
                                                </div>
                                                <h4 class="ml-3 text-sm font-medium text-gray-700">Event Title</h4>
                                            </div>
                                            <p class="ml-11 text-lg font-semibold text-gray-900">
                                                {{ event.title || 'No title provided' }}</p>
                                        </div>

                                        <!-- Event Type -->
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div
                                                    class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <UserIcon v-if="event.type === 'Individual'"
                                                              class="h-4 w-4 text-green-600"/>
                                                    <UserGroupIcon v-else class="h-4 w-4 text-green-600"/>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-medium text-gray-700">Event Type</h4>
                                                <div class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="event.type === 'Individual' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                            <UserIcon v-if="event.type === 'Individual'" class="h-3 w-3 mr-1"/>
                            <UserGroupIcon v-else class="h-3 w-3 mr-1"/>
                            {{ event.type || 'Not specified' }}
                        </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Date & Time Section -->
                                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                            <h4 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
                                                <ClockIcon class="h-4 w-4 mr-2 text-gray-500"/>
                                                Schedule
                                            </h4>

                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <!-- Start Date & Time -->
                                                <div class="bg-white rounded-md p-3 border border-gray-200">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <CalendarIcon class="h-4 w-4 text-green-500"/>
                                                        <span
                                                            class="text-xs font-medium text-green-600 uppercase tracking-wider">Start</span>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <p class="text-sm font-semibold text-gray-900">
                                                            {{ event.fromDateFormatted || 'Not set' }}</p>
                                                        <div class="flex items-center space-x-1">
                                                            <ClockIcon class="h-3 w-3 text-gray-400"/>
                                                            <span class="text-sm text-gray-600">{{
                                                                    event.fromTime || 'Not set'
                                                                }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- End Date & Time -->
                                                <div class="bg-white rounded-md p-3 border border-gray-200">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <CalendarIcon class="h-4 w-4 text-red-500"/>
                                                        <span
                                                            class="text-xs font-medium text-red-600 uppercase tracking-wider">End</span>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <p class="text-sm font-semibold text-gray-900">
                                                            {{ event.toDateFormatted || 'Not set' }}</p>
                                                        <div class="flex items-center space-x-1">
                                                            <ClockIcon class="h-3 w-3 text-gray-400"/>
                                                            <span class="text-sm text-gray-600">{{
                                                                    event.toTime || 'Not set'
                                                                }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Duration Display -->
                                            <div v-if="event.duration" class="mt-4 pt-3 border-t border-gray-200">
                                                <div
                                                    class="flex items-center justify-center space-x-2 text-sm text-gray-600">
                                                    <ClockIcon class="h-4 w-4"/>
                                                    <span>Duration: <span class="font-semibold">{{
                                                            event.duration
                                                        }}</span></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div v-if="event.description" class="space-y-3">
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24"
                                                         stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <h4 class="text-sm font-medium text-gray-700">Description</h4>
                                            </div>
                                            <div class="ml-10 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                                <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">
                                                    {{ event.description }}</p>
                                            </div>
                                        </div>

                                        <div v-else class="space-y-3">
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                         stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <h4 class="text-sm font-medium text-gray-700">Description</h4>
                                            </div>
                                            <div
                                                class="ml-10 p-4 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                                <p class="text-sm text-gray-500 italic">No description provided</p>
                                            </div>
                                        </div>

                                        <div v-if="event.href" class="mt-6 pt-4 border-t border-gray-200">
                                            <a :href="event.href"
                                               target="_blank"
                                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                Join Event
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                <button
                                    v-if="mode === 'show' && permissions == 'edit'"
                                    @click="changeMode('edit')"
                                    type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Edit Event
                                </button>
                                <button
                                    v-if="permissions == 'edit'"
                                    @click="deleteEvent()"
                                    type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Delete Event
                                </button>
                                <button
                                    v-if="mode === 'edit' && permissions == 'edit'"
                                    @click="changeMode('show')"
                                    type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Show Event
                                </button>
                                <button
                                    v-if="mode === 'edit' && permissions == 'edit'"
                                    type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!isFormValid"
                                    @click="handleSubmit"
                                >
                                    Save Changes
                                </button>
                                <button
                                    type="button"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                    @click="handleClose"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
