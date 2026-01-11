<script setup>
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  Listbox,
  ListboxButton,
  ListboxOption,
  ListboxOptions,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import {
  CalendarIcon,
  CheckIcon,
  ChevronUpDownIcon,
  ClockIcon,
  UserGroupIcon,
  UserIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

import {
  capitalize,
  errors,
  isFormValid,
  timeZone,
  validateForm,
} from '@/Stores/Calendar/helpers.js';
const props = defineProps({
  locale: {
    type: String,
    default: null,
  },
  permissions: {
    type: String,
    default: 'view',
  },
  availableColours: {
    type: Object,
    default: () => {},
  },
  calendarEvent: {
    type: Object,
    default: () => {},
  },
});
const mode = ref('show');
const event = ref({
  id: '',
  title: '',
  fromDate: '',
  fromDateFormatted: '',
  toDateFormatted: '',
  fromTime: '',
  toTime: '',
  type: 'Individual',
  href: '',
  description: '',
  colour: '',
  mentor_program_id: props.calendarEvent?.mentor_program_id,
  timezone: ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'),
});

const changeMode = (newMode) => {
  mode.value = newMode;
};

const deleteEvent = () => {
  form.delete(route('pages.calendar.delete', { id: event.value.id }), {
    onSuccess: () => {
      form.reset();
      handleClose();
    },
    onError: (serverErrors) => {
      console.log('Server validation errors:', serverErrors);
    },
  });
};

let form = useForm({
  id: '',
  title: '',
  fromDate: '',
  fromTime: '09:00',
  toDate: '',
  toTime: '10:00',
  type: 'Individual',
  webLink: '',
  description: '',
  colour: '',
  timezone: timeZone,
  mentor_program_id: props?.calendarEvent?.mentor_program_id,
});
const availableColours = props.availableColours;
const permissions = ref(props.permissions);
const availableColoursScheme = ref({});

onMounted(() => {
  const eventData = props.calendarEvent;
  availableColours.value = props.availableColours;
  availableColoursScheme.value = availableColours.value.reduce(
    (acc, colour) => {
      acc[colour] = `bg-${colour}-500`;
      return acc;
    },
    {},
  );

  if (eventData) {
    event.value = eventData;
    permissions.value = props.permissions;

    Object.assign(form, {
      id: eventData.id || '',
      title: eventData.title || '',
      fromDate: eventData.fromDate || '',
      fromTime: eventData.fromTime || '',
      toDate: eventData.toDate || '',
      toTime: eventData.toTime || '',
      webLink: eventData.webLink || '',
      type: eventData.type || '',
      colour: eventData.colour || '',
      description: eventData.description || '',
    });
  }
});

const handleSubmit = () => {
  if (validateForm(form, errors)) {
    form.patch(route('pages.calendar.edit', { id: event.value.id }), {
      onSuccess: () => {
        form.reset();
        handleClose();
      },
      onError: (serverErrors) => {
        console.log('Server validation errors:', serverErrors);
        Object.keys(serverErrors).forEach((field) => {
          if (Object.prototype.hasOwnProperty.call(errors.value, field)) {
            errors.value[field] = serverErrors[field];
          }
        });
      },
    });
  }
};

const handleClose = () => {
  console.log('close');
  console.log(props.calendarEvents);
  router.visit(route('pages.calendar.index'), {});
};

watch(
  () => form.fromDate,
  (newFromDate) => {
    if (newFromDate && form.toDate < newFromDate) {
      form.toDate = newFromDate;
    }
  },
);

watch(
  () => form.fromTime,
  (newFromTime) => {
    if (newFromTime && form.fromDate === form.toDate) {
      const [hours, minutes] = newFromTime.split(':').map(Number);
      const newEndTime = new Date();
      newEndTime.setHours(hours + 1, minutes);
      form.toTime = newEndTime.toTimeString().slice(0, 5);
    }
  },
);
</script>

<template>
  <TransitionRoot as="template" :show="true">
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
        <div
          class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
        />
      </TransitionChild>

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
        >
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
              class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left
              shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
            >
              <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                <button
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2
                  focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                  @click="handleClose"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
              </div>

              <div v-if="mode === 'edit'" class="sm:flex sm:items-start">
                <div
                  class="mt-3 w-full text-center sm:mt-0 sm:ml-0 sm:text-left"
                >
                  <DialogTitle
                    as="h3"
                    class="mb-4 text-base leading-6 font-semibold text-gray-900"
                  >
                    Edit Event
                  </DialogTitle>

                  <form class="space-y-4" @submit.prevent="handleSubmit">
                    <!-- CalendarEvent Title -->
                    <div
                      class="rounded-lg border border-indigo-100 bg-gradient-to-r from-indigo-50 to-purple-50 p-4"
                    >
                      <div class="mb-2 flex items-center">
                        <div class="flex-shrink-0">
                          <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100"
                          >
                            <span class="text-sm font-semibold text-indigo-600"
                              >T</span
                            >
                          </div>
                        </div>
                        <h4 class="ml-3 text-sm font-medium text-gray-700">
                          Event Title
                        </h4>
                      </div>
                      <p class="ml-11 text-lg font-semibold text-gray-900">
                        {{ event.title || 'No title provided' }}
                      </p>
                    </div>

                    <div>
                      <label
                        for="webLink"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Web link
                      </label>

                      <div class="mt-2">
                        <input
                          id="webLink"
                          v-model="form.webLink"
                          type="text"
                          class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1
                          ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600
                          focus:ring-inset sm:text-sm sm:leading-6"
                          :class="{ 'ring-red-300': errors.webLink }"
                          placeholder="Enter weblink"
                        />
                        <p
                          v-if="errors.webLink"
                          class="mt-2 text-sm text-red-600"
                        >
                          {{ errors.webLink }}
                        </p>
                      </div>
                    </div>

                    <div>
                      <label
                        for="title"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Description
                      </label>
                      <div class="mt-2">
                        <textarea
                          id="description"
                          v-model="form.description"
                          maxlength="2000"
                          rows="3"
                          class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1
                          ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600
                          focus:ring-inset sm:text-sm sm:leading-6"
                          :class="{ 'ring-red-300': errors.description }"
                          placeholder="Enter event description"
                        />
                        <p
                          v-if="errors.description"
                          class="mt-2 text-sm text-red-600"
                        >
                          {{ errors.description }}
                        </p>
                      </div>
                    </div>
                    <div>
                      <label
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Event Type
                      </label>
                      <div class="mt-1">
                        <span
                          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                          :class="
                            event.type === 'Individual' ?
                              'bg-blue-100 text-blue-800'
                            : 'bg-purple-100 text-purple-800'
                          "
                        >
                          <UserIcon
                            v-if="event.type === 'Individual'"
                            class="mr-1 h-3 w-3"
                          />
                          <UserGroupIcon v-else class="mr-1 h-3 w-3" />
                          {{ event.type || 'Not specified' }}
                        </span>
                      </div>
                    </div>

                    <div v-if="availableColours">
                      <label
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Color
                      </label>
                      <Listbox v-model="form.colour">
                        <div class="relative mt-2">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg border border-gray-300 bg-white py-2 pr-10
                            pl-3 text-left shadow-md focus:outline-none focus-visible:border-indigo-500
                            focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2
                            focus-visible:ring-offset-orange-300 sm:text-sm"
                          >
                            <span class="flex items-center">
                              <span
                                class="mr-2 inline-block h-4 w-6 rounded border border-gray-300 align-middle"
                                :class="availableColoursScheme[form.colour]"
                              />
                              <span class="block truncate">{{
                                capitalize(form.colour)
                              }}</span>
                              <span></span>
                            </span>
                            <span
                              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
                            >
                              <ChevronUpDownIcon
                                class="h-5 w-5 text-gray-400"
                                aria-hidden="true"
                              />
                            </span>
                          </ListboxButton>
                          <transition
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                          >
                            <ListboxOptions
                              class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1
                              text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                            >
                              <ListboxOption
                                v-for="availableColour in availableColours"
                                :key="availableColour"
                                v-slot="{ active, selected }"
                                :value="availableColour"
                                as="template"
                              >
                                <li
                                  :class="[
                                    active ?
                                      'bg-amber-100 text-amber-900'
                                    : 'text-gray-900',
                                    'relative cursor-default py-2 pr-4 pl-10 select-none',
                                  ]"
                                >
                                  <span
                                    :class="[
                                      selected ? 'font-medium' : 'font-normal',
                                      'flex items-center truncate',
                                    ]"
                                  >
                                    <span
                                      class="mr-2 inline-block h-4 w-6 rounded border border-gray-300 align-middle"
                                      :class="
                                        availableColoursScheme[availableColour]
                                      "
                                    />
                                    <span class="align-middle">{{
                                      capitalize(availableColour) || 'No color'
                                    }}</span>
                                  </span>
                                  <span
                                    v-if="selected"
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-amber-600"
                                  >
                                    <CheckIcon
                                      class="h-5 w-5"
                                      aria-hidden="true"
                                    />
                                  </span>
                                </li>
                              </ListboxOption>
                            </ListboxOptions>
                          </transition>
                        </div>
                      </Listbox>
                    </div>

                    <div
                      class="rounded-lg border border-indigo-100 bg-gradient-to-r from-indigo-50 to-purple-50 p-4"
                    >
                      <!-- Date & Time Section -->
                      <div
                        class="rounded-lg border border-gray-200 bg-gray-50 p-4"
                      >
                        <h4
                          class="mb-4 flex items-center text-sm font-medium text-gray-700"
                        >
                          <ClockIcon class="mr-2 h-4 w-4 text-gray-500" />
                          Schedule
                        </h4>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                          <!-- Start Date & Time -->
                          <div
                            class="rounded-md border border-gray-200 bg-white p-3"
                          >
                            <div class="mb-2 flex items-center space-x-2">
                              <CalendarIcon class="h-4 w-4 text-green-500" />
                              <span
                                class="text-xs font-medium tracking-wider text-green-600 uppercase"
                                >Start</span
                              >
                            </div>
                            <div class="space-y-1">
                              <p class="text-sm font-semibold text-gray-900">
                                {{ event.fromDateFormatted || 'Not set' }}
                              </p>
                              <div class="flex items-center space-x-1">
                                <ClockIcon class="h-3 w-3 text-gray-400" />
                                <span class="text-sm text-gray-600">{{
                                  event.fromTime || 'Not set'
                                }}</span>
                              </div>
                            </div>
                          </div>

                          <!-- End Date & Time -->
                          <div
                            class="rounded-md border border-gray-200 bg-white p-3"
                          >
                            <div class="mb-2 flex items-center space-x-2">
                              <CalendarIcon class="h-4 w-4 text-red-500" />
                              <span
                                class="text-xs font-medium tracking-wider text-red-600 uppercase"
                                >End</span
                              >
                            </div>
                            <div class="space-y-1">
                              <p class="text-sm font-semibold text-gray-900">
                                {{ event.toDateFormatted || 'Not set' }}
                              </p>
                              <div class="flex items-center space-x-1">
                                <ClockIcon class="h-3 w-3 text-gray-400" />
                                <span class="text-sm text-gray-600">{{
                                  event.toTime || 'Not set'
                                }}</span>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Duration Display -->
                        <div
                          v-if="event.duration"
                          class="mt-4 border-t border-gray-200 pt-3"
                        >
                          <div
                            class="flex items-center justify-center space-x-2 text-sm text-gray-600"
                          >
                            <ClockIcon class="h-4 w-4" />
                            <span
                              >Duration:
                              <span class="font-semibold"
                                >{{ event.duration }} minutes
                              </span></span
                            >
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <div v-if="mode === 'show'" class="sm:flex sm:items-start">
                <div
                  class="mt-3 w-full text-center sm:mt-0 sm:ml-0 sm:text-left"
                >
                  <DialogTitle
                    as="h3"
                    class="mb-6 border-b border-gray-200 pb-2 text-lg leading-6 font-semibold text-gray-900"
                  >
                    <CalendarIcon class="mr-2 inline h-5 w-5 text-indigo-600" />
                    Event Details
                  </DialogTitle>

                  <div class="space-y-6">
                    <!-- CalendarEvent Title -->
                    <div
                      class="rounded-lg border border-indigo-100 bg-gradient-to-r from-indigo-50 to-purple-50 p-4"
                    >
                      <div class="mb-2 flex items-center">
                        <div class="flex-shrink-0">
                          <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100"
                          >
                            <span class="text-sm font-semibold text-indigo-600"
                              >T</span
                            >
                          </div>
                        </div>
                        <h4 class="ml-3 text-sm font-medium text-gray-700">
                          Event Title
                        </h4>
                      </div>
                      <p class="ml-11 text-lg font-semibold text-gray-900">
                        {{ event.title || 'No title provided' }}
                      </p>
                    </div>

                    <div v-if="availableColours">
                      <label
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Color
                      </label>
                      <Listbox v-model="form.colour">
                        <div class="relative mt-2">
                          <span class="flex items-center">
                            <span
                              class="mr-2 inline-block h-4 w-6 rounded border border-gray-300 align-middle"
                              :class="availableColoursScheme[form.colour]"
                            />
                            <span class="block truncate">{{
                              capitalize(form.colour)
                            }}</span>
                          </span>
                        </div>
                      </Listbox>
                    </div>

                    <!-- CalendarEvent Type -->
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0">
                        <div
                          class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100"
                        >
                          <UserIcon
                            v-if="event.type === 'Individual'"
                            class="h-4 w-4 text-green-600"
                          />
                          <UserGroupIcon
                            v-else
                            class="h-4 w-4 text-green-600"
                          />
                        </div>
                      </div>
                      <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-medium text-gray-700">
                          Event Type
                        </h4>
                        <div class="mt-1">
                          <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="
                              event.type === 'Individual' ?
                                'bg-blue-100 text-blue-800'
                              : 'bg-purple-100 text-purple-800'
                            "
                          >
                            <UserIcon
                              v-if="event.type === 'Individual'"
                              class="mr-1 h-3 w-3"
                            />
                            <UserGroupIcon v-else class="mr-1 h-3 w-3" />
                            {{ event.type || 'Not specified' }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Date & Time Section -->
                    <div
                      class="rounded-lg border border-gray-200 bg-gray-50 p-4"
                    >
                      <h4
                        class="mb-4 flex items-center text-sm font-medium text-gray-700"
                      >
                        <ClockIcon class="mr-2 h-4 w-4 text-gray-500" />
                        Schedule
                      </h4>

                      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Start Date & Time -->
                        <div
                          class="rounded-md border border-gray-200 bg-white p-3"
                        >
                          <div class="mb-2 flex items-center space-x-2">
                            <CalendarIcon class="h-4 w-4 text-green-500" />
                            <span
                              class="text-xs font-medium tracking-wider text-green-600 uppercase"
                              >Start</span
                            >
                          </div>
                          <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-900">
                              {{ event.fromDateFormatted || 'Not set' }}
                            </p>
                            <div class="flex items-center space-x-1">
                              <ClockIcon class="h-3 w-3 text-gray-400" />
                              <span class="text-sm text-gray-600">{{
                                event.fromTime || 'Not set'
                              }}</span>
                            </div>
                          </div>
                        </div>

                        <!-- End Date & Time -->
                        <div
                          class="rounded-md border border-gray-200 bg-white p-3"
                        >
                          <div class="mb-2 flex items-center space-x-2">
                            <CalendarIcon class="h-4 w-4 text-red-500" />
                            <span
                              class="text-xs font-medium tracking-wider text-red-600 uppercase"
                              >End</span
                            >
                          </div>
                          <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-900">
                              {{ event.toDateFormatted || 'Not set' }}
                            </p>
                            <div class="flex items-center space-x-1">
                              <ClockIcon class="h-3 w-3 text-gray-400" />
                              <span class="text-sm text-gray-600">{{
                                event.toTime || 'Not set'
                              }}</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Duration Display -->
                      <div
                        v-if="event.duration"
                        class="mt-4 border-t border-gray-200 pt-3"
                      >
                        <div
                          class="flex items-center justify-center space-x-2 text-sm text-gray-600"
                        >
                          <ClockIcon class="h-4 w-4" />
                          <span
                            >Duration:
                            <span class="font-semibold"
                              >{{ event.duration }} minutes
                            </span></span
                          >
                        </div>
                      </div>
                    </div>

                    <!-- Description -->
                    <div v-if="event.description" class="space-y-3">
                      <div class="flex items-center space-x-2">
                        <div
                          class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100"
                        >
                          <svg
                            class="h-4 w-4 text-yellow-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0
                              01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                          </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-700">
                          Description
                        </h4>
                      </div>
                      <div
                        class="ml-10 rounded-lg border border-gray-200 bg-gray-50 p-4"
                      >
                        <p
                          class="text-sm leading-relaxed whitespace-pre-wrap text-gray-800"
                        >
                          {{ event.description }}
                        </p>
                      </div>
                    </div>

                    <div v-else class="space-y-3">
                      <div class="flex items-center space-x-2">
                        <div
                          class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100"
                        >
                          <svg
                            class="h-4 w-4 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0
                              01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                          </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-700">
                          Description
                        </h4>
                      </div>
                      <div
                        class="ml-10 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4"
                      >
                        <p class="text-sm text-gray-500 italic">
                          No description provided
                        </p>
                      </div>
                    </div>

                    <div
                      v-if="event.webLink"
                      class="mt-6 border-t border-gray-200 pt-4"
                    >
                      <a
                        :href="event.webLink"
                        target="_blank"
                        class="inline-flex items-center rounded-md border border-transparent bg-indigo-100
                        px-4 py-2 text-sm font-medium text-indigo-700 transition-colors duration-200
                        hover:bg-indigo-200 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                      >
                        <svg
                          class="mr-2 h-4 w-4"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                          />
                        </svg>
                        Join Event
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  v-if="mode === 'show' && permissions === 'edit'"
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold
                  text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2
                  focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed
                  disabled:opacity-50 sm:ml-3 sm:w-auto"
                  @click="changeMode('edit')"
                >
                  Edit Event
                </button>
                <button
                  v-if="permissions === 'edit'"
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold
                  text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2
                  focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:cursor-not-allowed
                  disabled:opacity-50 sm:ml-3 sm:w-auto"
                  @click="deleteEvent()"
                >
                  Delete Event
                </button>
                <button
                  v-if="mode === 'edit' && permissions === 'edit'"
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm
                  font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed
                   disabled:opacity-50 sm:ml-3 sm:w-auto"
                  @click="changeMode('show')"
                >
                  Show Event
                </button>
                <button
                  v-if="mode === 'edit' && permissions === 'edit'"
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold
                  text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2
                  focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed
                  disabled:opacity-50 sm:ml-3 sm:w-auto"
                  :disabled="!isFormValid"
                  @click="handleSubmit"
                >
                  Save Changes
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm
                  font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset
                  hover:bg-gray-50 sm:mt-0 sm:w-auto"
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
