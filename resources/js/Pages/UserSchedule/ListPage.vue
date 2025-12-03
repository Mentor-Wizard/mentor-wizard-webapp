<script setup lang="ts">
import {
  Listbox,
  ListboxButton,
  ListboxOption,
  ListboxOptions,
  Switch,
} from '@headlessui/vue';
import {
  CheckIcon,
  ChevronUpDownIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import {router, usePage} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch} from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PopUp from "@/Components/UI/Notifications/PopUp.vue";

interface Schedule {
  id?: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  type: string;
  day_off_date?: string;
  timezone: string;
  comment?: string;
  enabled: boolean;
  _prevStartTime?: string;
}

interface Props {
  schedules: Schedule[];
  scheduleTypes: Array<{ value: string; label: string }>;
  timezone: string;
  success?: string;
  error?: string;
}

const props = defineProps<Props>();
const timezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
const daysOfWeek = [
  {value: 1, label: 'Monday'},
  {value: 2, label: 'Tuesday'},
  {value: 3, label: 'Wednesday'},
  {value: 4, label: 'Thursday'},
  {value: 5, label: 'Friday'},
  {value: 6, label: 'Saturday'},
  {value: 0, label: 'Sunday'},
];

// Filter schedule types for weekdays (exclude Day off and Weekend)
const weekdayScheduleTypes = computed(() =>
  props.scheduleTypes.filter(
    (type) => type.value !== 'Day off' && type.value !== 'Weekend'
  )
);

// Generate time options (00:00 to 23:30 in 30-minute intervals)
const timeOptions = ref<string[]>([]);
for (let hour = 0; hour < 24; hour++) {
  for (let minute of [0, 30]) {
    const hourFormatted = hour.toString().padStart(2, '0');
    const minuteFormatted = minute.toString().padStart(2, '0');
    timeOptions.value.push(`${hourFormatted}:${minuteFormatted}`);
  }
}

// Group existing schedules by day of week (excluding Day off)
const schedulesByDay = computed(() => {
  const grouped: Record<number, Schedule[]> = {};
  daysOfWeek.forEach((day) => {
    grouped[day.value] = props.schedules
      .filter((schedule) => schedule.day_of_week === day.value && schedule.type !== 'Day off')
      .map((schedule) => ({
        ...schedule,
        enabled: true,
        // Format times to HH:mm without seconds
        start_time: schedule.start_time ? schedule.start_time.substring(0, 5) : schedule.start_time,
        end_time: schedule.end_time ? schedule.end_time.substring(0, 5) : schedule.end_time,
      }));
  });
  return grouped;
});

// Group exclusions (Day off schedules)
const exclusionSchedules = computed(() => {
  return props.schedules
    .filter((schedule) => schedule.type === 'Day off')
    .map((schedule) => ({
      ...schedule,
      enabled: true,
      // Format times to HH:mm without seconds
      start_time: schedule.start_time ? schedule.start_time.substring(0, 5) : schedule.start_time,
      end_time: schedule.end_time ? schedule.end_time.substring(0, 5) : schedule.end_time,
    }));
});

// Local state for managing schedules
const localSchedules = ref<Record<number, Schedule[]>>({});
const localExclusions = ref<Schedule[]>([]);

// Initialize local schedules
daysOfWeek.forEach((day) => {
  localSchedules.value[day.value] = schedulesByDay.value[day.value].length
    ? [...schedulesByDay.value[day.value]]
    : [];
});

// Initialize local exclusions
localExclusions.value = exclusionSchedules.value.length
  ? [...exclusionSchedules.value]
  : [];

// Helper function to add one hour to a time string (HH:mm format)
const addOneHour = (timeStr: string): string => {
  const [hours, minutes] = timeStr.split(':').map(Number);
  const newHours = (hours + 1) % 24;
  return `${newHours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
};

// Watch for start_time changes and auto-update end_time
watch(localSchedules, (newSchedules) => {
  daysOfWeek.forEach((day) => {
    if (newSchedules[day.value]) {
      newSchedules[day.value].forEach((schedule, index) => {
        // Store previous start_time to detect changes
        if (!schedule._prevStartTime) {
          schedule._prevStartTime = schedule.start_time;
        } else if (schedule._prevStartTime !== schedule.start_time) {
          // Start time changed, update end_time
          schedule.end_time = addOneHour(schedule.start_time);
          schedule._prevStartTime = schedule.start_time;
        }
      });
    }
  });
}, {deep: true});

const errors = ref<Record<string, string>>({});

// Track IDs of schedules to delete
const scheduleIdsToDelete = ref<number[]>([]);

// Notification state
const notification = ref<{ show: boolean; success: boolean; message: string }>({
  show: false,
  success: false,
  message: '',
});

const showNotification = (success: boolean, message: string) => {
  notification.value = {show: true, success, message};
  setTimeout(() => {
    notification.value.show = false;
  }, 5000);
};
const refreshSchedules = () => {
  router.reload({
    only: ['schedules'],
  });
};

// Check for flash messages on mount
onMounted(() => {
  const page = usePage();
  timezone.value = page.props.timezone?page.props.timezone:ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
  if(page.props.schedules.length == 0){
    showNotification(true, "There are no schedules set");
  }
});

const addScheduleForDay = (dayOfWeek: number) => {
  if (!localSchedules.value[dayOfWeek]) {
    localSchedules.value[dayOfWeek] = [];
  }

  localSchedules.value[dayOfWeek].push({
    day_of_week: dayOfWeek,
    start_time: '09:00',
    end_time: '17:00',
    type: 'Working Day',
    timezone: timezone.value,
    enabled: true,
  });
};

const removeSchedule = (dayOfWeek: number, index: number) => {
  const schedule = localSchedules.value[dayOfWeek][index];

  // If it has an ID, mark it for deletion
  if (schedule.id) {
    scheduleIdsToDelete.value.push(schedule.id);
  }

  // Remove from local state
  localSchedules.value[dayOfWeek].splice(index, 1);
};

const addExclusion = () => {
  localExclusions.value.push({
    day_of_week: 1, // Default to Monday, will be set via day_off_date
    start_time: '09:00',
    end_time: '17:00',
    type: 'Day off',
    timezone: timezone.value,
    enabled: true,
  });
};

const removeExclusion = (index: number) => {
  const exclusion = localExclusions.value[index];

  // If it has an ID, mark it for deletion
  if (exclusion.id) {
    scheduleIdsToDelete.value.push(exclusion.id);
  }

  // Remove from local state
  localExclusions.value.splice(index, 1);
};

const saveAllSchedules = () => {
  // Clear previous errors
  errors.value = {};

  // Collect all schedules (both enabled and disabled)
  const schedulesToSave: Schedule[] = [];
  daysOfWeek.forEach((day) => {
    if (localSchedules.value[day.value]) {
      localSchedules.value[day.value].forEach((schedule) => {
        if (schedule.enabled) {
          schedulesToSave.push({
            ...schedule,
            day_of_week: day.value,
          });
        }
      });
    }
  });

  // Add enabled exclusions
  localExclusions.value.forEach((exclusion) => {
    if (exclusion.enabled) {
      schedulesToSave.push({
        ...exclusion,
      });
    }
  });

  // Prepare payload
  const payload = {
    schedules: schedulesToSave,
    delete_ids: scheduleIdsToDelete.value,
  };

  // Send batch request
  router.post(route('user-schedule.batch'), payload, {
    preserveScroll: true,
    onSuccess: () => {
      // Clear deletion tracking
      scheduleIdsToDelete.value = [];
      showNotification(true, "Schedule was saved successfully");
      refreshSchedules();
    },
    onError: (serverErrors) => {
      console.error('Server validation errors:', serverErrors);
      showNotification(false, 'Server validation errors:', serverErrors);
      // Map server errors to local error state
      Object.keys(serverErrors).forEach((key) => {
        if (key.startsWith('schedules.')) {
          const match = key.match(/schedules\.(\d+)\./);
          if (match) {
            const index = parseInt(match[1]);
            const schedule = schedulesToSave[index];
            if (schedule) {
              // Find the exact matching schedule in localSchedules
              const dayOfWeek = schedule.day_of_week;
              const localIndex = localSchedules.value[dayOfWeek]?.findIndex(
                (s) => {
                  // Match by ID if both have IDs
                  if (s.id && schedule.id) {
                    return s.id === schedule.id;
                  }
                  // Otherwise match by all properties
                  return s.day_of_week === schedule.day_of_week &&
                    s.start_time === schedule.start_time &&
                    s.end_time === schedule.end_time &&
                    s.type === schedule.type &&
                    s.enabled === schedule.enabled;
                }
              );

              if (localIndex !== undefined && localIndex >= 0) {
                errors.value[`${dayOfWeek}-${localIndex}`] = serverErrors[key];
              }
            }
          }
        }
      });
    },
  });
};

const getTypeLabel = (typeValue: string): string => {
  return (
    props.scheduleTypes.find((type) => type.value === typeValue)?.label || typeValue
  );
};
</script>

<template>
  <AuthenticatedLayout>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Notification Popup -->
      <div>
        <PopUp :show-status="notification.show" :success="notification.success" :message="notification.message"></PopUp>
      </div>


      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Weekly Schedule</h1>
          <h4>Timezone: {{ timezone }}</h4>
          <p class="mt-2 text-sm text-gray-600">
            Manage your weekly schedule by adding time slots for each day.
          </p>
          <p class="text-sm text-gray-600">
            Toggle schedules on/off and set specific types for different days.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="saveAllSchedules"
        >
          Save Changes
        </button>
      </div>

      <div class="space-y-4">
        <div
          v-for="day in daysOfWeek"
          :key="day.value"
          class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ day.label }}
            </h2>
            <button
              type="button"
              class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              @click="addScheduleForDay(day.value)"
            >
              <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true"/>
              Add
            </button>
          </div>

          <div
            v-if="
            !localSchedules[day.value] || localSchedules[day.value].length === 0
          "
            class="py-8 text-center text-sm text-gray-500"
          >
            No schedules for this day. Click "Add Schedule" to create one.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(schedule, index) in localSchedules[day.value]"
              :key="index"

              :class="[
                  !schedule.enabled ? 'bg-gray-200' : '',
                  'grid grid-cols-12 items-start gap-3 rounded-md border border-gray-200 bg-gray-50 p-3',
                ]"
            >
              <!-- Toggle -->
              <div
                class="col-span-1 flex items-center pt-2">
                <Switch
                  v-model="schedule.enabled"
                  :class="[
                  schedule.enabled ? 'bg-indigo-600' : 'bg-gray-200',
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                ]"
                >
                <span
                  aria-hidden="true"
                  :class="[
                    schedule.enabled ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out transform',
                  ]"
                />
                </Switch>
              </div>
              <!-- Start Time -->
              <div
                class="col-span-2">
                <label class="block text-xs font-medium text-gray-700">
                  Start Time
                </label>
                <Listbox
                  v-model="schedule.start_time"
                  :disabled="!schedule.enabled"
                >
                  <div class="relative mt-1">
                    <ListboxButton
                      class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pr-10 pl-3 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    >
                      <span class="block truncate">{{ schedule.start_time }}</span>
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
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                      >
                        <ListboxOption
                          v-for="time in timeOptions"
                          :key="time"
                          v-slot="{ active, selected }"
                          :value="time"
                          as="template"
                        >
                          <li
                            :class="[
                            active ?
                              'bg-indigo-600 text-white'
                            : 'text-gray-900',
                            'relative cursor-default py-2 pr-9 pl-3 select-none',
                          ]"
                          >
                          <span
                            :class="[
                              selected ? 'font-semibold' : 'font-normal',
                              'block truncate',
                            ]"
                          >
                            {{ time }}
                          </span>
                            <span
                              v-if="selected"
                              :class="[
                              active ? 'text-white' : 'text-indigo-600',
                              'absolute inset-y-0 right-0 flex items-center pr-4',
                            ]"
                            >
                            <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                          </span>
                          </li>
                        </ListboxOption>
                      </ListboxOptions>
                    </transition>
                  </div>
                </Listbox>
              </div>

              <!-- End Time -->
              <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-700">
                  End Time
                </label>
                <Listbox v-model="schedule.end_time" :disabled="!schedule.enabled">
                  <div class="relative mt-1">
                    <ListboxButton
                      class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pr-10 pl-3 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    >
                      <span class="block truncate">{{ schedule.end_time }}</span>
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
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                      >
                        <ListboxOption
                          v-for="time in timeOptions"
                          :key="time"
                          v-slot="{ active, selected }"
                          :value="time"
                          as="template"
                        >
                          <li
                            :class="[
                            active ?
                              'bg-indigo-600 text-white'
                            : 'text-gray-900',
                            'relative cursor-default py-2 pr-9 pl-3 select-none',
                          ]"
                          >
                          <span
                            :class="[
                              selected ? 'font-semibold' : 'font-normal',
                              'block truncate',
                            ]"
                          >
                            {{ time }}
                          </span>
                            <span
                              v-if="selected"
                              :class="[
                              active ? 'text-white' : 'text-indigo-600',
                              'absolute inset-y-0 right-0 flex items-center pr-4',
                            ]"
                            >
                            <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                          </span>
                          </li>
                        </ListboxOption>
                      </ListboxOptions>
                    </transition>
                  </div>
                </Listbox>
              </div>

              <!-- Type -->
              <div class="col-span-3">
                <label class="block text-xs font-medium text-gray-700">
                  Type
                </label>
                <Listbox v-model="schedule.type" :disabled="!schedule.enabled">
                  <div class="relative mt-1">
                    <ListboxButton
                      class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pr-10 pl-3 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    >
                    <span class="block truncate">{{
                        getTypeLabel(schedule.type)
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
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                      >
                        <ListboxOption
                          v-for="type in weekdayScheduleTypes"
                          :key="type.value"
                          v-slot="{ active, selected }"
                          :value="type.value"
                          as="template"
                        >
                          <li
                            :class="[
                            active ?
                              'bg-indigo-600 text-white'
                            : 'text-gray-900',
                            'relative cursor-default py-2 pr-9 pl-3 select-none',
                          ]"
                          >
                          <span
                            :class="[
                              selected ? 'font-semibold' : 'font-normal',
                              'block truncate',
                            ]"
                          >
                            {{ type.label }}
                          </span>
                            <span
                              v-if="selected"
                              :class="[
                              active ? 'text-white' : 'text-indigo-600',
                              'absolute inset-y-0 right-0 flex items-center pr-4',
                            ]"
                            >
                            <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                          </span>
                          </li>
                        </ListboxOption>
                      </ListboxOptions>
                    </transition>
                  </div>
                </Listbox>
              </div>

              <!-- Actions -->
              <div class="col-span-4 mt-4 flex items-end justify-end" >
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                  @click="removeSchedule(day.value, index)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true"/>
                </button>
              </div>

              <!-- Error message -->
              <div v-if="errors[`${day.value}-${index}`]" class="col-span-12">
                <p class="text-sm text-red-600">
                  {{ errors[`${day.value}-${index}`] }}
                </p>
              </div>

            </div>
          </div>
        </div>

        <!-- Exclusions Section -->
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Exclusions</h2>
              <p class="mt-1 text-sm text-gray-600">
                Define specific days off using the Day Off type.
              </p>
            </div>
            <button
              type="button"
              class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              @click="addExclusion"
            >
              <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true"/>
              Add Exclusion
            </button>
          </div>

          <div
            v-if="!localExclusions || localExclusions.length === 0"
            class="py-8 text-center text-sm text-gray-500"
          >
            No exclusions defined. Click "Add Exclusion" to create one.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(exclusion, index) in localExclusions"
              :key="index"
              class="grid grid-cols-12 items-start gap-3 rounded-md border border-gray-200 bg-gray-50 p-3"
            >
              <!-- Toggle -->
              <div class="col-span-1 flex items-center pt-5">
                <Switch
                  v-model="exclusion.enabled"
                  :class="[
                  exclusion.enabled ? 'bg-indigo-600' : 'bg-gray-200',
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                ]"
                >
                <span
                  aria-hidden="true"
                  :class="[
                    exclusion.enabled ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out transform',
                  ]"
                />
                </Switch>
              </div>

              <!-- Day Off Date -->
              <div class="col-span-4">
                <label class="block text-xs font-medium text-gray-700">
                  Day Off Date
                </label>
                <input
                  v-model="exclusion.day_off_date"
                  type="date"
                  :disabled="!exclusion.enabled"
                  class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                />
              </div>

              <!-- Actions -->
              <div class="col-span-4 flex items-end justify-end">
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                  @click="removeExclusion(index)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true"/>
                </button>
              </div>

              <!-- Error message -->
              <div v-if="errors[`exclusion-${index}`]" class="col-span-12">
                <p class="text-sm text-red-600">
                  {{ errors[`exclusion-${index}`] }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
