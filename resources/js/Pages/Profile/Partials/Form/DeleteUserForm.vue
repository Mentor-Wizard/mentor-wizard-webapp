<script setup>
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline/index.js';
import { DialogTitle } from '@headlessui/vue';
import SecondaryButton from '@/Components/UI/Button/SecondaryButton.vue';
import DangerButton from '@/Components/UI/Button/DangerButton.vue';
import CloseButton from '@/Components/UI/Button/CloseButton.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;

  nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;

  form.clearErrors();
  form.reset();
};
</script>

<template>
  <div
    class="grid max-w-7xl grid-cols-1 gap-x-8 gap-y-10 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8"
  >
    <div>
      <h2 class="text-base/7 font-semibold">Delete account</h2>
      <p class="mt-1 text-sm/6 text-gray-400">
        No longer want to use our service? You can delete your account here.
        This action is not reversible. All information related to this account
        will be deleted permanently.
      </p>
    </div>

    <div class="flex items-start md:col-span-2">
      <DangerButton @click="confirmUserDeletion">
        Yes, delete my account
      </DangerButton>
    </div>

    <Modal
      :model-value="confirmingUserDeletion"
      @update:modelValue="closeModal"
    >
      <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
        <CloseButton @click="closeModal" />
      </div>
      <div class="sm:flex sm:items-start">
        <div
          class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:size-10"
        >
          <ExclamationTriangleIcon
            class="size-6 text-red-600"
            aria-hidden="true"
          />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <DialogTitle as="h3" class="text-base font-semibold text-gray-900"
            >Deactivate account</DialogTitle
          >
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Are you sure you want to deactivate your account? All of your data
              will be permanently removed from our servers forever. This action
              cannot be undone.
            </p>
          </div>

          <div class="mt-6">
            <InputLabel for="password" value="Password" class="sr-only" />

            <TextInput
              id="password"
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="mt-1 block w-3/4"
              placeholder="Password"
              @keyup.enter="deleteUser"
            />

            <InputError :message="form.errors.password" class="mt-2" />
          </div>
        </div>
      </div>
      <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
        <DangerButton @click="deleteUser">Deactivate</DangerButton>
        <SecondaryButton
          class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:mt-0 sm:w-auto"
          @click="closeModal"
          >Cancel
        </SecondaryButton>
      </div>
    </Modal>
  </div>
</template>
