<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid';
import { computed, defineEmits, defineProps } from 'vue';

const props = defineProps({
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
});

const emit = defineEmits(['update:page']);

const pages = computed(() => {
  const arr = [];
  for (let i = 1; i <= props.totalPages; i++) {
    arr.push(i);
  }
  return arr;
});

function goToPage(p) {
  if (p < 1 || p > props.totalPages) return;
  emit('update:page', p);
}
</script>

<template>
  <div
    v-if="totalPages > 1"
    class="mt-8 flex items-center justify-center space-x-1"
  >
    <button
      :disabled="page <= 1"
      class="rounded p-2 text-sm"
      :class="
        page <= 1 ?
          'cursor-not-allowed text-gray-400'
        : 'text-blue-600 hover:bg-gray-100'
      "
      @click="goToPage(page - 1)"
    >
      <ChevronLeftIcon class="h-4 w-4" />
    </button>

    <button
      v-for="p in pages"
      :key="p"
      class="rounded px-3 py-1 text-sm font-semibold"
      :class="{
        'bg-blue-600 text-white': p === page,
        'text-gray-500 hover:text-blue-600': p !== page,
      }"
      @click="goToPage(p)"
    >
      {{ p }}
    </button>

    <button
      :disabled="page >= totalPages"
      class="rounded p-2 text-sm"
      :class="
        page >= totalPages ?
          'cursor-not-allowed text-gray-400'
        : 'text-blue-600 hover:bg-gray-100'
      "
      @click="goToPage(page + 1)"
    >
      <ChevronRightIcon class="h-4 w-4" />
    </button>
  </div>
</template>
