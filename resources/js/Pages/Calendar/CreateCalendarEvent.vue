<script setup lang="ts">
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
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
const timeZone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');

const errors = ref({
  toDate: null,
  fromTime: null,
  fromDate: null,
  toTime: null,
  title: null,
  colour: null,
  description: null,
});
const props = defineProps({
  open: {
    type: Boolean,
  },
  closeCreateEventPage: {
    type: Function,
    default: () => {},
  },
  availableColors: {
    type: Array,
    default: () => [],
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
});

let form = useForm({
  title: '',
  fromDate: '',
  toDate: '',
  fromTime: '09:00',
  toTime: '10:00',
  type: 'Individual',
  description: '',
  colour: 'blue',
  timezone: timeZone,
  mentor_program_id: null,
});

// CalendarEvent types
const eventTypes = [
  { value: 'Individual', label: 'Individual', icon: UserIcon },
  { value: 'Group', label: 'Group', icon: UserGroupIcon },
];

const availableColours = ref([]);
const availableColoursScheme = ref({});
const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

onMounted(() => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];
  availableColours.value = usePage().props.availableColours;
  availableColoursScheme.value = availableColours.value.reduce(
    (acc, c) => {
      acc[c] = `bg-${c}-500`;
      return acc;
    },
    {} as Record<string, string>,
  );

  // Initialize with selectedDate if provided (for mentor program booking)
  if (props.selectedDate) {
    form.fromDate = props.selectedDate;
    form.toDate = props.selectedDate;

    // Set initial times from first available slot
    if (props.availableSlots && props.availableSlots.length > 0) {
      const firstSlot = props.availableSlots[0];
      const startTime = new Date(firstSlot.start);
      const endTime = new Date(firstSlot.end);
      form.fromTime = startTime.toTimeString().slice(0, 5);
      form.toTime = endTime.toTimeString().slice(0, 5);
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

const selectedEventType = computed(() =>
  eventTypes.find((type) => type.value === form.type),
);

const isFormValid = computed(() => {
  return (
    form.title.trim()
    && form.fromDate
    && form.toDate
    && form.fromTime
    && form.colour
    && form.toTime
  );
});

const validateForm = () => {
  errors.value = {
    fromTime: null,
    fromDate: null,
    title: null,
    toDate: null,
    toTime: null,
    colour: null,
    description: null,
  };

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

  if (form.description.length > 2000) {
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

    // Validate against available slots for mentor program bookings
    if (props.availableSlots && props.availableSlots.length > 0) {
      const isWithinSlots = props.availableSlots.some((slot) => {
        const slotStart = new Date(slot.start);
        const slotEnd = new Date(slot.end);
        return fromDateTime >= slotStart && toDateTime <= slotEnd;
      });

      if (!isWithinSlots) {
        errors.value.fromTime =
          'Selected time must fall within available slots';
      }
    }
  }

  let errorStatus = false;

  Object.keys(errors.value).forEach((key) => {
    if (errors.value[key]) {
      errorStatus = true;
    }
  });
  return !errorStatus;
};

const handleSubmit = () => {
  if (validateForm()) {
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
  console.log('close');
  form.reset();
  errors.value = {
    fromTime: null,
    fromDate: null,
    title: null,
    toDate: null,
    toTime: null,
    colour: null,
    description: null,
  };
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
      const newEndTime = new Date();
      newEndTime.setHours(hours + 1, minutes);
      form.toTime = newEndTime.toTimeString().slice(0, 5);
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

        // Set initial times from first available slot
        if (props.availableSlots && props.availableSlots.length > 0) {
          const firstSlot = props.availableSlots[0];
          const startTime = new Date(firstSlot.start);
          const endTime = new Date(firstSlot.end);
          form.fromTime = startTime.toTimeString().slice(0, 5);
          form.toTime = endTime.toTimeString().slice(0, 5);
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
                    <div>
                      <label
                        for="title"
                        class="block text-sm leading-6 font-medium text-gray-900"
                      >
                        Event Title
                      </label>
                      <div class="mt-2">
                        <input
                          id="title"
                          v-model="form.title"
                          type="text"
                          class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                          :class="{ 'ring-red-300': errors.title }"
                          placeholder="Enter event title"
                        />
                        <p
                          v-if="errors.title"
                          class="mt-2 text-sm text-red-600"
                        >
                          {{ errors.title }}
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
                        <input
                          id="title"
                          v-model="form.description"
                          type="text"
                          class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm sm:leading-6"
                          :class="{ 'ring-red-300': errors.description }"
                          placeholder="Enter event title"
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
                      <Listbox v-model="form.type">
                        <div class="relative mt-2">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-3 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
                          >
                            <span class="flex items-center">
                              <component
                                :is="selectedEventType?.icon"
                                class="mr-3 h-5 w-5 text-gray-400"
                              />
                              <span class="block truncate">{{
                                selectedEventType?.label
                              }}</span>
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
                                v-for="type in eventTypes"
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
                                      'flex items-center truncate',
                                    ]"
                                  >
                                    <component
                                      :is="type.icon"
                                      class="mr-3 h-5 w-5"
                                    />
                                    {{ type.label }}
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
                  </form>
                </div>
              </div>

              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto"
                  :disabled="!isFormValid"
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
