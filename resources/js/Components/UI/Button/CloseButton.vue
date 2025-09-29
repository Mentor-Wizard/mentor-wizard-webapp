<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline/index.js';
</script>

<template>
  <button
    type="button"
    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
  >
    <span class="sr-only">Close</span>
    <XMarkIcon class="size-6" aria-hidden="true" />
  </button>
</template>
