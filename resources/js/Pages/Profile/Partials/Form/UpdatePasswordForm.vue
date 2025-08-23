<script setup>
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputSuccess from '@/Components/UI/Forms/InputSuccess.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value.focus();
      }
      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value.focus();
      }
    },
  });
};
</script>

<template>
  <div
    class="grid max-w-7xl grid-cols-1 gap-x-8 gap-y-10 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8"
  >
    <div>
      <h2 class="text-base/7 font-semibold">Change password</h2>
      <p class="mt-1 text-sm/6 text-gray-400">
        Update your password associated with your account.
      </p>
    </div>

    <form class="md:col-span-2" @submit.prevent="updatePassword">
      <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:max-w-xl sm:grid-cols-6">
        <div class="col-span-full">
          <InputLabel for="current_password" value="Current password" />

          <div class="mt-2">
            <TextInput
              id="current_password"
              ref="currentPasswordInput"
              v-model="form.current_password"
              type="password"
              autocomplete="current_password"
              required
            />
          </div>

          <InputError class="mt-2" :message="form.errors.current_password" />
        </div>

        <div class="col-span-full">
          <InputLabel for="password" value="New Password" />

          <div class="mt-2">
            <TextInput
              id="password"
              ref="passwordInput"
              v-model="form.password"
              type="password"
              autocomplete="password"
              required
            />
          </div>

          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div class="col-span-full">
          <InputLabel for="password_confirmation" value="Confirm Password" />

          <div class="mt-2">
            <TextInput
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="password_confirmation"
              required
            />
          </div>

          <InputError
            class="mt-2"
            :message="form.errors.password_confirmation"
          />
        </div>
      </div>

      <div class="mt-8 flex">
        <div class="w-auto">
          <PrimaryButton
            :class="{ 'cursor-not-allowed opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Save
          </PrimaryButton>

          <InputSuccess message="Saved" :is-show="form.recentlySuccessful" />
        </div>
      </div>
    </form>
  </div>
</template>
