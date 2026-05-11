<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid';
import { computed } from 'vue';

const props = defineProps({
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
});

const emit = defineEmits(['update:page']);

const visiblePages = computed(() => {
  const total = props.totalPages;
  const current = props.page;

  if (total <= 5) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const pages = new Set([1, total]);
  for (
    let i = Math.max(2, current - 1);
    i <= Math.min(total - 1, current + 1);
    i++
  ) {
    pages.add(i);
  }

  const sorted = [...pages].sort((a, b) => a - b);
  const result = [];

  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) {
      result.push('...');
    }
    result.push(sorted[i]);
  }

  return result;
});

function goToPage(p) {
  if (p < 1 || p > props.totalPages) return;
  emit('update:page', p);
}
</script>

<template>
  <div
    v-if="totalPages > 1"
    class="mt-8 flex items-center justify-center gap-1"
  >
    <button
      :disabled="page <= 1"
      aria-label="Previous page"
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

    <template v-for="(p, idx) in visiblePages" :key="idx">
      <span v-if="p === '...'" class="px-2 text-gray-400">...</span>
      <button
        v-else
        class="rounded px-3 py-1 text-sm font-semibold"
        :class="{
          'bg-blue-600 text-white': p === page,
          'text-gray-500 hover:bg-gray-100 hover:text-blue-600': p !== page,
        }"
        :aria-current="p === page ? 'page' : undefined"
        @click="goToPage(p)"
      >
        {{ p }}
      </button>
    </template>

    <button
      :disabled="page >= totalPages"
      aria-label="Next page"
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
