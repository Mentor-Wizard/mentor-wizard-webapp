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
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
}

interface Props {
  schedules: Schedule[];
  scheduleTypes: Array<{ value: string; label: string }>;
  timezone: string;
}

const props = defineProps<Props>();

const daysOfWeek = [
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
  { value: 0, label: 'Sunday' },
];

// Generate time options (00:00 to 23:30 in 30-minute intervals)
const timeOptions = ref<string[]>([]);
for (let hour = 0; hour < 24; hour++) {
  for (let minute of [0, 30]) {
    const h = hour.toString().padStart(2, '0');
    const m = minute.toString().padStart(2, '0');
    timeOptions.value.push(`${h}:${m}`);
  }
}

// Group existing schedules by day of week
const schedulesByDay = computed(() => {
  const grouped: Record<number, Schedule[]> = {};
  daysOfWeek.forEach((day) => {
    grouped[day.value] = props.schedules
      .filter((s) => s.day_of_week === day.value)
      .map((s) => ({
        ...s,
        enabled: true,
        // Format times to HH:mm without seconds
        start_time: s.start_time ? s.start_time.substring(0, 5) : s.start_time,
        end_time: s.end_time ? s.end_time.substring(0, 5) : s.end_time,
      }));
  });
  return grouped;
});

// Local state for managing schedules
const localSchedules = ref<Record<number, Schedule[]>>({});

// Initialize local schedules
daysOfWeek.forEach((day) => {
  localSchedules.value[day.value] = schedulesByDay.value[day.value].length
    ? [...schedulesByDay.value[day.value]]
    : [];
});

const errors = ref<Record<string, string>>({});

const addScheduleForDay = (dayOfWeek: number) => {
  if (!localSchedules.value[dayOfWeek]) {
    localSchedules.value[dayOfWeek] = [];
  }

  localSchedules.value[dayOfWeek].push({
    day_of_week: dayOfWeek,
    start_time: '09:00',
    end_time: '17:00',
    type: 'Working Day',
    timezone: props.timezone,
    enabled: true,
  });
};

const removeSchedule = (dayOfWeek: number, index: number) => {
  const schedule = localSchedules.value[dayOfWeek][index];

  if (schedule.id) {
    // Delete from backend if it has an ID
    router.delete(route('user-schedule.destroy', { userSchedule: schedule.id }), {
      preserveScroll: true,
      onSuccess: () => {
        localSchedules.value[dayOfWeek].splice(index, 1);
      },
      onError: (serverErrors) => {
        console.error('Error deleting schedule:', serverErrors);
      },
    });
  } else {
    // Just remove from local state if not saved yet
    localSchedules.value[dayOfWeek].splice(index, 1);
  }
};

const saveAllSchedules = () => {
  // Clear previous errors
  errors.value = {};

  // Collect all enabled schedules
  const schedulesToSave: Schedule[] = [];
  daysOfWeek.forEach((day) => {
    if (localSchedules.value[day.value]) {
      localSchedules.value[day.value].forEach((schedule, index) => {
        if (schedule.enabled) {
          // Client-side validation for non-day-off schedules
          if (schedule.type !== 'Day off') {
            const otherSchedules = localSchedules.value[day.value].filter(
              (_, i) => i !== index && localSchedules.value[day.value][i].enabled && localSchedules.value[day.value][i].type !== 'Day off'
            );

            for (const other of otherSchedules) {
              if (
                (schedule.start_time < other.end_time &&
                  schedule.end_time > other.start_time) ||
                (other.start_time < schedule.end_time &&
                  other.end_time > schedule.start_time)
              ) {
                errors.value[`${day.value}-${index}`] =
                  'Time slot overlaps with another schedule';
                return;
              }
            }
          }

          schedulesToSave.push({
            ...schedule,
            day_of_week: day.value,
          });
        }
      });
    }
  });

  if (Object.keys(errors.value).length > 0) {
    return;
  }

  // Save all schedules sequentially
  let currentIndex = 0;
  const saveNext = () => {
    if (currentIndex >= schedulesToSave.length) {
      // All saved successfully - reload page
      router.visit(route('user-schedule.index'), {
        preserveScroll: true,
      });
      return;
    }

    const schedule = schedulesToSave[currentIndex];
    const form = useForm({
      day_of_week: schedule.day_of_week,
      start_time: schedule.start_time,
      end_time: schedule.end_time,
      type: schedule.type,
      day_off_date: schedule.day_off_date,
      timezone: schedule.timezone,
      comment: schedule.comment,
    });

    form.post(route('user-schedule.store'), {
      preserveScroll: true,
      onSuccess: () => {
        currentIndex++;
        saveNext();
      },
      onError: (serverErrors) => {
        console.error('Server validation errors:', serverErrors);
        // Find the day and index for this schedule
        daysOfWeek.forEach((day) => {
          const index = localSchedules.value[day.value]?.findIndex(
            (s) => s === schedule
          );
          if (index !== undefined && index >= 0) {
            if (serverErrors.start_time) {
              errors.value[`${day.value}-${index}`] = serverErrors.start_time;
            } else {
              errors.value[`${day.value}-${index}`] = 'Error saving schedule';
            }
          }
        });
      },
    });
  };

  saveNext();
};

const getDayLabel = (dayValue: number): string => {
  return daysOfWeek.find((d) => d.value === dayValue)?.label || '';
};

const getTypeLabel = (typeValue: string): string => {
  return (
    props.scheduleTypes.find((t) => t.value === typeValue)?.label || typeValue
  );
};
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Weekly Schedule</h1>
        <p class="mt-2 text-sm text-gray-600">
          Manage your weekly schedule by adding time slots for each day. Toggle
          schedules on/off and set specific types for different days.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        @click="saveAllSchedules"
      >
        Save All Changes
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
            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
            Add Schedule
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
            class="grid grid-cols-12 items-start gap-3 rounded-md border border-gray-200 bg-gray-50 p-3"
          >
            <!-- Toggle -->
            <div class="col-span-1 flex items-center pt-2">
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

            <!-- Start Time (hidden for day-off) -->
            <div v-if="schedule.type !== 'Day off'" class="col-span-2">
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
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                          </span>
                        </li>
                      </ListboxOption>
                    </ListboxOptions>
                  </transition>
                </div>
              </Listbox>
            </div>

            <!-- End Time (hidden for day-off) -->
            <div v-if="schedule.type !== 'Day off'" class="col-span-2">
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
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                          </span>
                        </li>
                      </ListboxOption>
                    </ListboxOptions>
                  </transition>
                </div>
              </Listbox>
            </div>

            <!-- Type -->
            <div
              :class="[
                schedule.type === 'Day off' ? 'col-span-5' : 'col-span-3',
              ]"
            >
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
                        v-for="type in scheduleTypes"
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
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                          </span>
                        </li>
                      </ListboxOption>
                    </ListboxOptions>
                  </transition>
                </div>
              </Listbox>
            </div>

            <!-- Day Off Date (conditional) -->
            <div v-if="schedule.type === 'Day off'" class="col-span-3">
              <label class="block text-xs font-medium text-gray-700">
                Day Off Date
              </label>
              <input
                v-model="schedule.day_off_date"
                type="date"
                :disabled="!schedule.enabled"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
              />
            </div>

            <!-- Actions -->
            <div
              :class="[
                schedule.type === 'Day off' ? 'col-span-3' : 'col-span-4',
                'flex items-end justify-end',
              ]"
            >
              <button
                type="button"
                class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                @click="removeSchedule(day.value, index)"
              >
                <TrashIcon class="h-4 w-4" aria-hidden="true" />
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
    </div>
  </div>
</template>
