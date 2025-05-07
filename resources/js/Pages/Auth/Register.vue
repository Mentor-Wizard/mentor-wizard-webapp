<script setup>
import { ref } from "vue";
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions, } from "@headlessui/vue";
import { CheckIcon, ChevronDownIcon } from "@heroicons/vue/20/solid";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/UI/Forms/InputError.vue";
import InputLabel from "@/Components/UI/Forms/InputLabel.vue";
import PrimaryButton from "@/Components/UI/Button/PrimaryButton.vue";
import TextInput from "@/Components/UI/Forms/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const publishingOptions = [
    {
        title: "Mentor",
        description:
            "You are a person with experience and are ready to pass on your knowledge to others.",
        current: true,
    },
    {
        title: "Coach",
        description:
            "You help other people achieve a certain goal in their profession or personal life.",
        current: false,
    },
    {
        title: "Menti",
        description: "Looking for someone who can support and give advice.",
        current: false,
    },
    {
        title: "User",
        description: "Want to be a simple user on the site.",
        current: false,
    },
];

const selected = ref(publishingOptions[0]);

const form = useForm({
  username: "",
  email: "email@admin.com",
  password: "",
  password_confirmation: "",
});

const submit = () => {
  console.log("test");
  form.post(route("register"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};
</script>

<template>
  <GuestLayout>

    <Head title="Register" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600"> {{ status }}
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Register</h2>
    </div>

    <div class="mt-3 sm:mx-auto sm:w-full sm:max-w-[480px]">
    <form @submit.prevent="submit">

      <Listbox as="div" v-model="selected">

        <ListboxLabel class="block text-sm/6 font-medium text-gray-900">Choose your role:</ListboxLabel>

        <ListboxLabel class="sr-only">Change published status</ListboxLabel>
        <div class="relative">
          <div class="w-full inline-flex divide-x divide-indigo-700 rounded-md outline-hidden">
            <div class="w-full inline-flex items-center gap-x-1.5 rounded-l-md bg-indigo-600 px-3 py-2 text-white">
              <CheckIcon class="-ml-0.5 size-5" aria-hidden="true" />
              <p class="text-sm font-semibold">{{ selected.title }}</p>
            </div>
            <ListboxButton class="inline-flex items-center rounded-l-none rounded-r-md bg-indigo-600 p-2 outline-hidden hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-400">
              <span class="sr-only">Change published status</span>
              <ChevronDownIcon class="size-5 text-white forced-colors:text-[Highlight]" aria-hidden="true" />
            </ListboxButton>
          </div>

          <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <ListboxOptions class="w-full absolute right-0 z-10 mt-2 w-72 origin-top-right divide-y divide-gray-200 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden">
              <ListboxOption as="template" v-for="option in publishingOptions" :key="option.title" :value="option" v-slot="{ active, selected }">
                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'cursor-default p-4 text-sm select-none']">
                  <div class="flex flex-col">
                    <div class="flex justify-between">
                      <p :class="selected ? 'font-semibold' : 'font-normal'">{{ option.title }}</p>
                      <span v-if="selected" :class="active ? 'text-white' : 'text-indigo-600'">
                        <CheckIcon class="size-5" aria-hidden="true" />
                      </span>
                    </div>
                    <p :class="[active ? 'text-indigo-200' : 'text-gray-500', 'mt-2']">{{ option.description }}</p>
                  </div>
                </li>
              </ListboxOption>
            </ListboxOptions>
          </transition>
        </div>
      </Listbox>

      <div class="mt-4">
        <InputLabel for="username" value="User Name" />

        <TextInput id="username" type="text" class="mt-1 block w-full" v-model="form.username" required autofocus
          autocomplete="username" />

        <InputError class="mt-2" :message="form.errors.username" />
      </div>

      <div class="mt-4">
        <InputLabel for="email" value="Email" />

        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
          autocomplete="email" />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="mt-4">
        <InputLabel for="password" value="Password" />

        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
          autocomplete="new-password" />

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="mt-4">
        <InputLabel for="password_confirmation" value="Confirm Password" />

        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
          v-model="form.password_confirmation" required autocomplete="new-password" />

        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <div class="mt-4 flex items-center">
        <PrimaryButton class="mt-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Register
        </PrimaryButton>
      </div>

      <div class="mt-4 flex items-center justify-center">
        <Link :href="route('login')"
          class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Already registered?
        </Link>
      </div>

      <div>
          <div class="relative mt-10">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
              <div class="w-full border-t border-gray-200" />
            </div>
            <div class="relative flex justify-center text-sm/6 font-medium">
              <span class="bg-white px-6 text-gray-900">Or continue with</span>
            </div>
          </div>

          <div class="mt-5 grid grid-cols-2 gap-4">
            <a href="#" class="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus-visible:ring-transparent">
              <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24">
                <path d="M12.0003 4.75C13.7703 4.75 15.3553 5.36002 16.6053 6.54998L20.0303 3.125C17.9502 1.19 15.2353 0 12.0003 0C7.31028 0 3.25527 2.69 1.28027 6.60998L5.27028 9.70498C6.21525 6.86002 8.87028 4.75 12.0003 4.75Z" fill="#EA4335" />
                <path d="M23.49 12.275C23.49 11.49 23.415 10.73 23.3 10H12V14.51H18.47C18.18 15.99 17.34 17.25 16.08 18.1L19.945 21.1C22.2 19.01 23.49 15.92 23.49 12.275Z" fill="#4285F4" />
                <path d="M5.26498 14.2949C5.02498 13.5699 4.88501 12.7999 4.88501 11.9999C4.88501 11.1999 5.01998 10.4299 5.26498 9.7049L1.275 6.60986C0.46 8.22986 0 10.0599 0 11.9999C0 13.9399 0.46 15.7699 1.28 17.3899L5.26498 14.2949Z" fill="#FBBC05" />
                <path d="M12.0004 24.0001C15.2404 24.0001 17.9654 22.935 19.9454 21.095L16.0804 18.095C15.0054 18.82 13.6204 19.245 12.0004 19.245C8.8704 19.245 6.21537 17.135 5.2654 14.29L1.27539 17.385C3.25539 21.31 7.3104 24.0001 12.0004 24.0001Z" fill="#34A853" />
              </svg>
              <span class="text-sm/6 font-semibold">Google</span>
            </a>

            <a href="#" class="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus-visible:ring-transparent">
              <svg class="size-5 fill-[#24292F]" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd" />
              </svg>
              <span class="text-sm/6 font-semibold">GitHub</span>
            </a>
          </div>
        </div>

    </form>
    </div>
  </GuestLayout>
</template>
