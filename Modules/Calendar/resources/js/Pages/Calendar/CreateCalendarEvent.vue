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
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import {
  capitalize,
  errors,
  isFormValid,
  sessionTypes,
  timeZone,
  validateForm,
} from '@modules/Calendar/resources/js/Stores/Calendar/helpers.js';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
  },
  closeCreateEventPage: {
    type: Function,
    default: () => {},
  },
  availableColours: {
    type: Object,
    default: () => {},
  },
  selectedDate: {
    type: String,
    default: null,
  },
  availableSlots: {
    type: Array,
    default: () => [],
  },
  mentorProgram: {
    type: Object,
    default: null,
  },
  selectedSlot: {
    type: Object,
    default: null,
  },
  roundingMinutes: {
    type: Number,
    default: 5,
  },
  mentorProgramId: {
    type: Number,
    default: null,
  },
});

const availableSessionTypes = computed(() => {
  const options = props.mentorProgram?.session_type_options;
  if (options && options.length > 0) {
    return sessionTypes.filter((t) => options.includes(t.value));
  }
  return sessionTypes;
});

const availableDurations = computed(() => {
  const options = props.mentorProgram?.session_duration_options;
  if (options && options.length > 0) {
    return options.map((minutes) => ({
      value: minutes,
      label: `${minutes} min`,
    }));
  }
  return [];
});

let form = useForm({
  title: props.mentorProgram?.name,
  webLink: '',
  fromDate: props.selectedDate,
  toDate: props.selectedDate,
  fromTime: '09:00',
  toTime: '10:00',
  type: 'Individual',
  session_type: availableSessionTypes.value[0]?.value ?? '',
  description: props.mentorProgram?.description ?? '',
  colour: 'blue',
  timezone: timeZone,
  mentor_program_id: props.mentorProgramId,
  selectedDuration: props.mentorProgram?.session_duration ?? 60,
});

const availableColoursList = ref([]);
const availableColoursScheme = ref({});
const usingSlotsMode = ref(false);
const activeSelectedSlot = ref(null);

const getSessionDurationMinutes = () => Number(form.selectedDuration) || 60;

const roundDateToMinutes = (date, minutes) => {
  const ms = 1000 * 60 * minutes;
  return new Date(Math.round(date.getTime() / ms) * ms);
};

const roundTimeString = (timeStr, minutes) => {
  const [h, m] = timeStr.split(':').map(Number);
  const d = new Date();
  d.setHours(h, m, 0, 0);
  const rd = roundDateToMinutes(d, minutes);
  return rd.toTimeString().slice(0, 5);
};

const formatTime = (isoOrDate) => {
  const d = isoOrDate instanceof Date ? isoOrDate : new Date(isoOrDate);
  return d.toTimeString().slice(0, 5);
};

const selectSlot = (slot) => {
  if (!slot) {
    return;
  }
  usingSlotsMode.value = true;
  activeSelectedSlot.value = slot;
  if (props.selectedDate) {
    form.fromDate = props.selectedDate;
    form.toDate = props.selectedDate;
  }
  const startTime = new Date(slot.start);
  const sessionMinutes = getSessionDurationMinutes();
  const desiredEnd = new Date(startTime.getTime() + sessionMinutes * 60000);
  const slotEnd = new Date(slot.end);
  const endTime = desiredEnd <= slotEnd ? desiredEnd : slotEnd;
  form.fromTime = formatTime(startTime);
  form.toTime = formatTime(endTime);
};

onMounted(() => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];
  availableColoursList.value = props.availableColours;
  availableColoursScheme.value = availableColoursList?.value?.reduce(
    (acc, colour) => {
      acc[colour] = `bg-${colour}-500`;
      return acc;
    },
    {},
  );

  // Initialize with selectedDate if provided (for mentor program booking)
  if (props.selectedDate) {
    form.fromDate = props.selectedDate;
    form.toDate = props.selectedDate;

    // Prefill from explicit selected slot if provided, else first slot
    if (props.selectedSlot) {
      usingSlotsMode.value = true;
      activeSelectedSlot.value = props.selectedSlot;
      const startTime = new Date(props.selectedSlot.start);
      const sessionMinutes = getSessionDurationMinutes();
      const desiredEnd = new Date(startTime.getTime() + sessionMinutes * 60000);
      const slotEnd = new Date(props.selectedSlot.end);
      const endTime = desiredEnd <= slotEnd ? desiredEnd : slotEnd;
      form.fromTime = formatTime(startTime);
      form.toTime = formatTime(endTime);
    } else if (props.availableSlots && props.availableSlots.length > 0) {
      const firstSlot = props.availableSlots[0];
      usingSlotsMode.value = true;
      activeSelectedSlot.value = firstSlot;
      const startTime = new Date(firstSlot.start);
      const sessionMinutes = getSessionDurationMinutes();
      const desiredEnd = new Date(startTime.getTime() + sessionMinutes * 60000);
      const slotEnd = new Date(firstSlot.end);
      const endTime = desiredEnd <= slotEnd ? desiredEnd : slotEnd;
      form.fromTime = formatTime(startTime);
      form.toTime = formatTime(endTime);
    }
  } else {
    if (!form.fromDate) {
      form.fromDate = today;
    }
    if (!form.toDate) {
      form.toDate = today;
    }
  }

  // Set mentor program ID if provided
  if (props.mentorProgram) {
    form.mentor_program_id = props.mentorProgram.id;
  }
});

if (!form.title?.trim()) {
  errors.value.title = 'Title is required';
}

if (!form.fromTime) {
  errors.value.fromTime = 'Start time is required';
}

if (!form.toTime) {
  errors.value.toTime = 'End time is required';
}

if ((form.description?.length ?? 0) > 2000) {
  errors.value.description = 'Description is more than 2000 characters';
}

if (form.fromDate && form.toDate) {
  const fromDateTime = new Date(`${form.fromDate}T${form.fromTime}`);
  const toDateTime = new Date(`${form.toDate}T${form.toTime}`);
  const currentTime = new Date();
  if (fromDateTime >= toDateTime) {
    errors.value.toDate = 'End date/time must be after start date/time';
  }
  if (currentTime > fromDateTime) {
    errors.value.fromDate = 'Start date/time must be in the future';
  }
  // Validate within selected slot only when using slot mode
  if (usingSlotsMode.value && activeSelectedSlot.value) {
    const slotStart = new Date(activeSelectedSlot.value.start);
    const slotEnd = new Date(activeSelectedSlot.value.end);
    if (!(fromDateTime >= slotStart && toDateTime <= slotEnd)) {
      errors.value.fromTime = 'Time must be within the chosen slot';
    }
  }
}

const errorStatus = ref(false);

Object.keys(errors.value).forEach((key) => {
  if (errors.value[key]) {
    errorStatus.value = true;
  }
});

const handleSubmit = () => {
  // Round custom inputs to grid before validation/submit
  if (!usingSlotsMode.value) {
    form.fromTime = roundTimeString(form.fromTime, props.roundingMinutes);
    form.toTime = roundTimeString(form.toTime, props.roundingMinutes);
  }
  if (validateForm(form, errors)) {
    form.post(route('pages.calendar.store'), {
      onSuccess: () => {
        handleClose();
        form.reset();
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
  form.reset();
  errors.value = {
    fromTime: null,
    fromDate: null,
    title: null,
    toDate: null,
    toTime: null,
    webLink: null,
    colour: null,
    description: null,
  };
  usingSlotsMode.value = false;
  activeSelectedSlot.value = null;
  props.closeCreateEventPage();
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
      const base = new Date();
      base.setHours(hours, minutes, 0, 0);
      const sessionMinutes = getSessionDurationMinutes();
      const computedEnd = new Date(base.getTime() + sessionMinutes * 60000);
      // If using a slot, clamp to slot end
      if (usingSlotsMode.value && activeSelectedSlot.value) {
        const slotEnd = new Date(activeSelectedSlot.value.end);
        form.toTime = formatTime(
          computedEnd <= slotEnd ? computedEnd : slotEnd,
        );
      } else {
        // Round to grid for custom
        form.toTime = roundTimeString(
          formatTime(computedEnd),
          props.roundingMinutes,
        );
      }
    }
  },
);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      // Reinitialize form when modal opens
      if (props.selectedDate) {
        form.fromDate = props.selectedDate;
        form.toDate = props.selectedDate;

        // If slot selected, prefer it
        if (props.selectedSlot) {
          usingSlotsMode.value = true;
          activeSelectedSlot.value = props.selectedSlot;
          const startTime = new Date(props.selectedSlot.start);
          const sessionMinutes = getSessionDurationMinutes();
          const desiredEnd = new Date(
            startTime.getTime() + sessionMinutes * 60000,
          );
          const slotEnd = new Date(props.selectedSlot.end);
          const endTime = desiredEnd <= slotEnd ? desiredEnd : slotEnd;
          form.fromTime = formatTime(startTime);
          form.toTime = formatTime(endTime);
        } else if (props.availableSlots && props.availableSlots.length > 0) {
          const firstSlot = props.availableSlots[0];
          usingSlotsMode.value = true;
          activeSelectedSlot.value = firstSlot;
          const startTime = new Date(firstSlot.start);
          const sessionMinutes = getSessionDurationMinutes();
          const desiredEnd = new Date(
            startTime.getTime() + sessionMinutes * 60000,
          );
          const slotEnd = new Date(firstSlot.end);
          const endTime = desiredEnd <= slotEnd ? desiredEnd : slotEnd;
          form.fromTime = formatTime(startTime);
          form.toTime = formatTime(endTime);
        }
      }

      // Set mentor program ID if provided
      if (props.mentorProgram) {
        form.mentor_program_id = props.mentorProgram.id;
      }
    }
  },
);
</script>

<template>
  <TransitionRoot as="template" :show="open">
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
              class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
            >
              <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                <button
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                  @click="handleClose"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
              </div>

              <div class="sm:flex sm:items-start">
                <div
                  class="mt-3 w-full text-center sm:mt-0 sm:ml-0 sm:text-left"
                >
                  <DialogTitle
                    as="h3"
                    class="mb-4 text-base leading-6 font-semibold text-gray-900"
                  >
                    Create New Event
                  </DialogTitle>

                  <form class="space-y-4" @submit.prevent="handleSubmit">
                    <!-- Quick slot selection -->
                    <div
                      v-if="availableSlots && availableSlots.length"
                      class="rounded-md border border-indigo-100 bg-indigo-50/50 p-3"
                    >
                      <div class="mb-2 text-xs font-medium text-indigo-900">
                        Available slots
                      </div>
                      <div class="flex flex-wrap gap-2">
                        <button
                          v-for="slot in availableSlots"
                          :key="slot.start + '-' + slot.end"
                          type="button"
                          class="rounded-full border px-2.5 py-1 text-xs font-medium"
                          :class="
                            (
                              activeSelectedSlot
                              && activeSelectedSlot.start === slot.start
                              && activeSelectedSlot.end === slot.end
                            ) ?
                              'border-indigo-500 bg-indigo-100 text-indigo-800'
                            : 'border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-50'
                          "
                          @click.prevent="selectSlot(slot)"
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
                      </div>
                    </div>

                    <!-- Session duration: selector if options available, static display otherwise -->
                    <div v-if="availableDurations.length > 0">
                      <label
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Session Duration
                      </label>
                      <Listbox v-model="form.selectedDuration">
                        <div class="relative mt-2">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-3 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
                          >
                            <span class="block truncate"
                              >{{ form.selectedDuration }} min</span
                            >
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
                              class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                            >
                              <ListboxOption
                                v-for="duration in availableDurations"
                                :key="duration.value"
                                v-slot="{ active, selected }"
                                :value="duration.value"
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
                                      'block truncate',
                                    ]"
                                    >{{ duration.label }}</span
                                  >
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
                      v-else-if="mentorProgram?.session_duration"
                      class="rounded-md border border-gray-200 bg-gray-50 p-2 text-sm text-gray-700"
                    >
                      Session duration:
                      <span class="font-medium">{{
                        mentorProgram.session_duration
                      }}</span>
                      minutes
                    </div>
                    <div>
                      <label
                        for="title"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Mentor program name
                      </label>
                      <div class="mt-2">
                        {{ form.title }}
                        <p
                          v-if="errors.title"
                          class="mt-2 text-sm text-red-600"
                        >
                          {{ errors.title }}
                        </p>
                      </div>
                    </div>

                    <div v-if="form.webLink && form.webLink !== ''">
                      <label
                        for="webLink"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Link to Event
                      </label>
                      <div class="mt-2">
                        {{ form.webLink }}
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
                        for="description"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Mentor program description
                      </label>
                      <div class="mt-2">
                        {{ form.description }}
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
                        Session Type
                      </label>
                      <Listbox v-model="form.session_type">
                        <div class="relative mt-2">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-3 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
                          >
                            <span class="block truncate">{{
                              form.session_type || 'Select session type'
                            }}</span>
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
                              class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                            >
                              <ListboxOption
                                v-for="type in availableSessionTypes"
                                :key="type.value"
                                v-slot="{ active, selected }"
                                :value="type.value"
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
                                      'block truncate',
                                    ]"
                                    >{{ type.label }}</span
                                  >
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

                    <div v-if="availableColours">
                      <label
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Color
                      </label>
                      <Listbox v-model="form.colour">
                        <div class="relative mt-2">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-3 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
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
                              class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
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

                    <!-- Date/time: read-only when slots available -->
                    <div
                      v-if="availableSlots && availableSlots.length"
                      class="rounded-md border border-gray-200 bg-gray-50 p-3"
                    >
                      <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-700"
                      >
                        <span class="flex items-center gap-1">
                          <CalendarIcon class="h-4 w-4 text-gray-400" />
                          <span class="font-medium">Date:</span>
                          {{ form.fromDate }}
                        </span>
                        <span class="flex items-center gap-1">
                          <ClockIcon class="h-4 w-4 text-gray-400" />
                          <span class="font-medium">Time:</span>
                          {{ form.fromTime }} – {{ form.toTime }}
                        </span>
                      </div>
                      <p
                        v-if="errors.fromTime || errors.fromDate"
                        class="mt-1 text-sm text-red-600"
                      >
                        {{ errors.fromTime || errors.fromDate }}
                      </p>
                    </div>

                    <!-- Date/time: editable when no slots -->
                    <template v-else>
                      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                          <label
                            for="from-date"
                            class="block text-sm leading-6 font-medium text-gray-900"
                          >
                            <CalendarIcon class="mr-1 inline h-4 w-4" />
                            From Date
                          </label>
                          <div class="mt-2">
                            <input
                              id="from-date"
                              v-model="form.fromDate"
                              type="date"
                              class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                              :class="{ 'ring-red-300': errors.fromDate }"
                            />
                            <p
                              v-if="errors.fromDate"
                              class="mt-1 text-sm text-red-600"
                            >
                              {{ errors.fromDate }}
                            </p>
                          </div>
                        </div>

                        <div>
                          <label
                            for="to-date"
                            class="block text-sm leading-6 font-medium text-gray-900"
                          >
                            <CalendarIcon class="mr-1 inline h-4 w-4" />
                            To Date
                          </label>
                          <div class="mt-2">
                            <input
                              id="to-date"
                              v-model="form.toDate"
                              type="date"
                              :min="form.fromDate"
                              class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                              :class="{ 'ring-red-300': errors.toDate }"
                            />
                            <p
                              v-if="errors.toDate"
                              class="mt-1 text-sm text-red-600"
                            >
                              {{ errors.toDate }}
                            </p>
                          </div>
                        </div>
                      </div>

                      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                          <label
                            for="from-time"
                            class="block text-sm leading-6 font-medium text-gray-900"
                          >
                            <ClockIcon class="mr-1 inline h-4 w-4" />
                            From Time
                          </label>
                          <div class="mt-2">
                            <input
                              id="from-time"
                              v-model="form.fromTime"
                              type="time"
                              class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                              :class="{ 'ring-red-300': errors.fromTime }"
                              @blur="
                                form.fromTime = roundTimeString(
                                  form.fromTime,
                                  roundingMinutes,
                                )
                              "
                            />
                            <p
                              v-if="errors.fromTime"
                              class="mt-1 text-sm text-red-600"
                            >
                              {{ errors.fromTime }}
                            </p>
                          </div>
                        </div>

                        <div>
                          <label
                            for="to-time"
                            class="block text-sm leading-6 font-medium text-gray-900"
                          >
                            <ClockIcon class="mr-1 inline h-4 w-4" />
                            To Time
                          </label>
                          <div class="mt-2">
                            <input
                              id="to-time"
                              v-model="form.toTime"
                              type="time"
                              class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                              :class="{ 'ring-red-300': errors.toTime }"
                              @blur="
                                form.toTime = roundTimeString(
                                  form.toTime,
                                  roundingMinutes,
                                )
                              "
                            />
                            <p
                              v-if="errors.toTime"
                              class="mt-1 text-sm text-red-600"
                            >
                              {{ errors.toTime }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </template>
                  </form>
                </div>
              </div>

              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto"
                  :disabled="!isFormValid(form)"
                  @click="handleSubmit"
                >
                  Create Event
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:mt-0 sm:w-auto"
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
