<script setup>
import Checkbox from "@/Components/UI/Forms/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/UI/Forms/InputError.vue";
import InputLabel from "@/Components/UI/Forms/InputLabel.vue";
import PrimaryButton from "@/Components/UI/Forms/PrimaryButton.vue";
import TextInput from "@/Components/UI/Forms/TextInput.vue";
import GithubLogo from "@/Components/UI/Logo/GithubLogo.vue";
import GoogleLogo from "@/Components/UI/Logo/GoogleLogo.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <GuestLayout>

    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Log in to your account</h2>
    </div>

    <div class="mt-3 sm:mx-auto sm:w-full sm:max-w-[480px]">
      <form @submit.prevent="submit">
        <div>
          <InputLabel for="email" value="Email" />

          <TextInput id="email" type="email" class="mt-1" v-model="form.email" required autofocus
            autocomplete="username" />

          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div class="mt-4">
          <InputLabel for="password" value="Password" />

          <TextInput id="password" type="password" class="mt-1" v-model="form.password" required
            autocomplete="current-password" />

          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div class="flex items-center justify-between mt-5">
          <div class="flex gap-3">
            <div class="flex h-6 shrink-0 items-center">
              <Checkbox name="remember-me" v-model:checked="form.remember" />
            </div>
            <label for="remember-me" class="block text-sm/6 text-gray-900">Remember me</label>
          </div>
        </div>

        <div class="mt-5">
          <PrimaryButton :class="{ 'opacity-25 cursor-not-allowed': form.processing }" :disabled="form.processing">
            Log in
          </PrimaryButton>
        </div>
      </form>

      <div class="grid grid-flow-col gap-5 justify-center mt-5">
        <Link v-if="canResetPassword" :href="route('register')"
          class="text-sm font-semibold text-gray-600 underline hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-4">
        Haven't registered yet?
        </Link>
        <Link v-if="canResetPassword" :href="route('password.request')"
          class="text-sm font-semibold text-gray-600 underline hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-4">
        Forgot your password?
        </Link>
      </div>

      <div class="relative mt-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
          <div class="w-full border-t border-gray-200" />
        </div>
        <div class="relative flex justify-center text-sm/6 font-medium">
          <span class="bg-white px-6 text-gray-900">Or continue with</span>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-2 gap-4">
        <a :href="route('auth.socialite.redirect', { driver: 'google' })"
          class="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 shadow-xs ring-gray-300 ring-inset hover:bg-gray-50 focus-visible:ring-transparent">
          <GoogleLogo class="w-auto" />
          <span class="text-sm/6 font-semibold">Google</span>
        </a>

        <a :href="route('auth.socialite.redirect', { driver: 'github' })"
          class="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 shadow-xs ring-gray-300 ring-inset hover:bg-gray-50 focus-visible:ring-transparent">
          <GithubLogo class="w-auto" />
          <span class="text-sm/6 font-semibold">Github</span>
        </a>
      </div>
    </div>
  </GuestLayout>
</template>
