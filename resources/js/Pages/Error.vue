<script setup>
import { computed } from 'vue'
import {Head} from "@inertiajs/vue3";

const props = defineProps({ status: Number })

const title = computed(() => {
  return {
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page not found',
    403: 'Forbidden',
    429: 'Too many requests'
  }[props.status] ?? 'Error'
})

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    404: 'Sorry, we couldn’t find the page you’re looking for.',
    403: 'Sorry, you are forbidden from accessing this page.',
    429: 'You\'re doing that too often! Try again later.'
  }[props.status] ?? 'Whoops, something went wrong on our servers.'
})
</script>

<template>
  <Head :title="title" />
  <main class="grid min-h-screen place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8">
    <div class="text-center">
      <p class="text-base font-semibold text-indigo-600">404</p>
      <h1 class="mt-4 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl">{{ title }}</h1>
      <p class="mt-6 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">{{ description }}</p>
      <div class="mt-10 flex items-center justify-center gap-x-6">
        <a :href="route('pages.welcome')"
           class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Go
          back home</a>
        <a :href="route('pages.welcome')" class="text-sm font-semibold text-gray-900">Contact support <span
            aria-hidden="true">&rarr;</span></a>
      </div>
    </div>
  </main>
</template>
