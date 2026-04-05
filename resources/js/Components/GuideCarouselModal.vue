<script setup>
import { DialogTitle } from '@headlessui/vue';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { ref } from 'vue';

import AppModal from '@/Components/AppModal.vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  steps: {
    type: Array,
    required: true,
    // [{ image: '/path/to/image.png', caption: 'Step description' }]
  },
});

const isOpen = defineModel({ type: Boolean, required: true });

const currentStep = ref(0);

function prev() {
  if (currentStep.value > 0) currentStep.value--;
}

function next(total) {
  if (currentStep.value < total - 1) currentStep.value++;
}

function open() {
  currentStep.value = 0;
  isOpen.value = true;
}

defineExpose({ open });
</script>

<template>
  <AppModal v-model="isOpen">
    <div>
      <div class="flex items-start justify-between">
        <DialogTitle class="text-base font-semibold text-gray-900">
          {{ title }}
        </DialogTitle>
        <button
          type="button"
          class="-mr-1 rounded p-1 text-gray-400 hover:text-gray-600"
          @click="isOpen = false"
        >
          <XMarkIcon class="size-5" />
        </button>
      </div>

      <div class="mt-4">
        <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
          <img
            :src="steps[currentStep].image"
            :alt="`Step ${currentStep + 1}`"
            class="h-64 w-full object-contain"
          />
        </div>

        <p class="mt-3 text-sm text-gray-600">
          <span class="font-medium">Step {{ currentStep + 1 }} of {{ steps.length }}:</span>
          {{ steps[currentStep].caption }}
        </p>
      </div>

      <div class="mt-5 flex items-center justify-between">
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40"
          :disabled="currentStep === 0"
          @click="prev"
        >
          <ChevronLeftIcon class="size-4" />
          Previous
        </button>

        <div class="flex gap-1.5">
          <span
            v-for="(_, i) in steps"
            :key="i"
            class="size-2 rounded-full transition-colors"
            :class="i === currentStep ? 'bg-indigo-600' : 'bg-gray-300'"
          />
        </div>

        <button
          v-if="currentStep < steps.length - 1"
          type="button"
          class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100"
          @click="next(steps.length)"
        >
          Next
          <ChevronRightIcon class="size-4" />
        </button>

        <button
          v-else
          type="button"
          class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
          @click="isOpen = false"
        >
          Got it
        </button>
      </div>
    </div>
  </AppModal>
</template>
