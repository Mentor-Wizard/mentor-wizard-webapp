<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AppModal from '@/Components/AppModal.vue';
import DangerButton from '@/Components/UI/Button/DangerButton.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import SelectField from '@/Components/UI/Forms/SelectField.vue';
import TextArea from '@/Components/UI/Forms/TextArea.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  program: {
    type: Object,
    default: null,
  },
  currencies: {
    type: Object,
    required: true,
  },
  sessionTypeOptions: {
    type: Array,
    required: true,
  },
  sessionDurationOptions: {
    type: Array,
    required: true,
  },
});

const page = usePage();

const showDeleteModal = ref(false);
const showSetMainModal = ref(false);
const isSettingMain = ref(false);

const notification = ref({ show: false, success: false, message: '' });

const showNotification = (success, message) => {
  notification.value = { show: true, success, message };
  setTimeout(() => {
    notification.value.show = false;
  }, 5000);
};

watch(
  () => page.props.flash?.success,
  (value) => {
    if (value) {
      showNotification(true, value);
    }
  },
  { immediate: true },
);

watch(
  () => page.props.flash?.error,
  (value) => {
    if (value) {
      showNotification(false, value);
    }
  },
  { immediate: true },
);

const toDateInput = (datetimeStr) => {
  if (!datetimeStr) return '';
  return new Date(datetimeStr).toISOString().slice(0, 10);
};

const toTimeInput = (datetimeStr) => {
  if (!datetimeStr) return '08:00';
  const d = new Date(datetimeStr);
  return d.toTimeString().slice(0, 5);
};

const combineDateTime = (date, time) => (date ? `${date} ${time}` : null);

const startDate = ref(toDateInput(props.program?.start_time));
const startTimeOfDay = ref(toTimeInput(props.program?.start_time));
const endDate = ref(toDateInput(props.program?.end_time));
const endTimeOfDay = ref(toTimeInput(props.program?.end_time));

const form = useForm({
  name: props.program?.name ?? '',
  description: props.program?.description ?? '',
  cost: props.program?.cost ?? '',
  currency_id: props.program?.currency_id ?? '',
  slug: props.program?.slug ?? '',
  session_type_options: props.program?.session_type_options ?? [
    ...props.sessionTypeOptions,
  ],
  session_duration: props.program?.session_duration ?? 60,
  need_confirmation: props.program?.need_confirmation ?? false,
  start_time: combineDateTime(startDate.value, startTimeOfDay.value),
  end_time: combineDateTime(endDate.value, endTimeOfDay.value),
});

watch([startDate, startTimeOfDay], () => {
  form.start_time = combineDateTime(startDate.value, startTimeOfDay.value);
});

watch([endDate, endTimeOfDay], () => {
  form.end_time = combineDateTime(endDate.value, endTimeOfDay.value);
});

const isEdit = computed(() => {
  return props.program !== null && typeof props.program === 'object';
});

const isAlreadyMain = computed(() => {
  return props.program?.is_main === true;
});

const submit = () => {
  if (isEdit.value) {
    form.patch(route('mentor-program.update', props.program.slug));
  } else {
    form.post(route('mentor-program.store'), {
      onSuccess: () => {
        form.reset();
      },
    });
  }
};

const confirmDelete = () => {
  showDeleteModal.value = true;
};

const deleteProgram = () => {
  form.delete(route('mentor-program.destroy', props.program.slug), {
    onSuccess: () => {
      showDeleteModal.value = false;
    },
  });
};

const setAsMain = () => {
  isSettingMain.value = true;
  router.patch(
    route('mentor-program.set-main', props.program.slug),
    {},
    {
      onSuccess: () => {
        showSetMainModal.value = false;
      },
      onFinish: () => {
        isSettingMain.value = false;
      },
    },
  );
};
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl leading-tight font-semibold text-gray-800">
          {{ isEdit ? 'Edit Mentor Program' : 'Create New Mentor Program' }}
        </h2>
        <Link
          :href="route('mentor-program.list')"
          class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
        >
          &larr; Back to Programs
        </Link>
      </div>
    </template>

    <PopUp
      :show-status="notification.show"
      :success="notification.success"
      :message="notification.message"
    />

    <div class="py-12">
      <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-8">
            <form
              class="space-y-8 divide-y divide-gray-200"
              @submit.prevent="submit"
            >
              <div class="space-y-6">
                <div>
                  <div class="space-y-6">
                    <div>
                      <InputLabel for="name" value="Program Name" />
                      <TextInput id="name" v-model="form.name" type="text" />
                      <InputError
                        v-if="form.errors.name"
                        :message="form.errors.name"
                        class="mt-2"
                      />
                    </div>

                    <div>
                      <InputLabel for="description" value="Description" />
                      <TextArea
                        id="description"
                        v-model="form.description"
                        rows="3"
                      />
                      <InputError
                        v-if="form.errors.description"
                        :message="form.errors.description"
                        class="mt-2"
                      />
                    </div>

                    <div>
                      <InputLabel value="Session Types" />
                      <p class="mb-2 text-sm text-gray-500">
                        Select which session types are available for booking.
                      </p>
                      <div class="space-y-2">
                        <label
                          v-for="option in sessionTypeOptions"
                          :key="option"
                          class="flex cursor-pointer items-center gap-2"
                        >
                          <input
                            v-model="form.session_type_options"
                            type="checkbox"
                            :value="option"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                          />
                          <span class="text-sm text-gray-700">{{
                            option
                          }}</span>
                        </label>
                      </div>
                      <InputError
                        v-if="form.errors.session_type_options"
                        :message="form.errors.session_type_options"
                        class="mt-2"
                      />
                    </div>

                    <div>
                      <InputLabel
                        for="session_duration"
                        value="Session Duration (minutes)"
                      />
                      <select
                        id="session_duration"
                        v-model="form.session_duration"
                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                        required
                      >
                        <option
                          v-for="duration in sessionDurationOptions"
                          :key="duration"
                          :value="duration"
                        >
                          {{ duration }} min
                        </option>
                      </select>
                      <InputError
                        v-if="form.errors.session_duration"
                        :message="form.errors.session_duration"
                        class="mt-2"
                      />
                    </div>

                    <div class="flex items-center gap-3">
                      <input
                        id="need_confirmation"
                        v-model="form.need_confirmation"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <div>
                        <InputLabel
                          for="need_confirmation"
                          value="Require mentor confirmation"
                        />
                        <p class="text-sm text-gray-500">
                          New bookings will be created as pending and require
                          your confirmation.
                        </p>
                      </div>
                    </div>

                    <div class="space-y-4">
                      <div>
                        <p class="mb-1 text-sm font-medium text-gray-700">
                          Available From
                        </p>
                        <div class="grid grid-cols-2 gap-x-4">
                          <div>
                            <InputLabel for="start_date" value="Date" />
                            <TextInput
                              id="start_date"
                              v-model="startDate"
                              type="date"
                            />
                          </div>
                          <div>
                            <InputLabel for="start_time_of_day" value="Time" />
                            <TextInput
                              id="start_time_of_day"
                              v-model="startTimeOfDay"
                              type="time"
                            />
                          </div>
                        </div>
                        <InputError
                          v-if="form.errors.start_time"
                          :message="form.errors.start_time"
                          class="mt-2"
                        />
                      </div>

                      <div>
                        <p class="mb-1 text-sm font-medium text-gray-700">
                          Available Until
                        </p>
                        <div class="grid grid-cols-2 gap-x-4">
                          <div>
                            <InputLabel for="end_date" value="Date" />
                            <TextInput
                              id="end_date"
                              v-model="endDate"
                              type="date"
                            />
                          </div>
                          <div>
                            <InputLabel for="end_time_of_day" value="Time" />
                            <TextInput
                              id="end_time_of_day"
                              v-model="endTimeOfDay"
                              type="time"
                            />
                          </div>
                        </div>
                        <InputError
                          v-if="form.errors.end_time"
                          :message="form.errors.end_time"
                          class="mt-2"
                        />
                      </div>
                    </div>

                    <div
                      class="mb-2 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2"
                    >
                      <div>
                        <InputLabel for="cost" value="Cost" />
                        <TextInput
                          id="cost"
                          v-model="form.cost"
                          type="number"
                          required
                        />
                        <InputError
                          v-if="form.errors.cost"
                          :message="form.errors.cost"
                          class="mt-2"
                        />
                      </div>

                      <div>
                        <InputLabel for="currency_id" value="Currency" />
                        <SelectField
                          id="currency_id"
                          v-model="form.currency_id"
                          :list="currencies"
                          :placeholder="'Select currency'"
                          required
                        />
                        <InputError
                          v-if="form.errors.currency_id"
                          :message="form.errors.currency_id"
                          class="mt-2"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-6">
                <div class="flex justify-between">
                  <div>
                    <button
                      v-if="isEdit && !isAlreadyMain"
                      type="button"
                      class="inline-flex justify-center rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 shadow-xs ring-1 ring-amber-300 ring-inset hover:bg-amber-100"
                      @click="showSetMainModal = true"
                    >
                      Set as Main Consultation
                    </button>
                    <span
                      v-else-if="isEdit && isAlreadyMain"
                      class="inline-flex items-center rounded-md bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200 ring-inset"
                    >
                      Main Consultation
                    </span>
                  </div>

                  <div class="flex space-x-3">
                    <DangerButton
                      v-if="isEdit"
                      type="button"
                      class="inline-flex justify-center"
                      @click="confirmDelete"
                    >
                      Delete Program
                    </DangerButton>

                    <PrimaryButton
                      :disabled="form.processing"
                      class="inline-flex justify-center"
                    >
                      {{ isEdit ? 'Update Program' : 'Create Program' }}
                    </PrimaryButton>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal v-model="showDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Are you sure you want to delete this program?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Once this program is deleted, all of its resources and data will be
          permanently deleted.
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <PrimaryButton @click="showDeleteModal = false">
            Cancel
          </PrimaryButton>
          <DangerButton :disabled="form.processing" @click="deleteProgram">
            Delete Program
          </DangerButton>
        </div>
      </div>
    </AppModal>

    <!-- Set as Main Consultation Modal -->
    <AppModal v-model="showSetMainModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Set as Main Consultation?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          This will make <strong>{{ program?.name }}</strong> the main
          consultation program. Your current main consultation program will lose
          its main status and will no longer be shown as the primary option on
          your profile.
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <PrimaryButton @click="showSetMainModal = false">
            Cancel
          </PrimaryButton>
          <button
            type="button"
            :disabled="isSettingMain"
            class="inline-flex justify-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 disabled:opacity-50"
            @click="setAsMain"
          >
            Confirm
          </button>
        </div>
      </div>
    </AppModal>
  </AuthenticatedLayout>
</template>
