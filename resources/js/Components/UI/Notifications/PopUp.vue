<script setup lang="ts">
import { CheckIcon } from '@heroicons/vue/20/solid';
interface Props {
  showStatus: boolean;
  success: boolean;
  message: string;
}
const props = defineProps<Props>();
</script>

<template>
  <div
    v-if="props.showStatus"
    class="fixed top-4 right-4 z-50 rounded-md p-4 shadow-lg transition-all"
    :class="props.success ? 'bg-green-500' : 'bg-red-500'"
  >
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <CheckIcon
          v-if="props.success"
          class="h-5 w-5 text-white"
          aria-hidden="true"
        />
        <svg
          v-else
          class="h-5 w-5 text-white"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <div class="ml-3">
        <p class="text-sm font-medium text-white">
          {{ props.message }}
        </p>
      </div>
    </div>
  </div>
</template>
