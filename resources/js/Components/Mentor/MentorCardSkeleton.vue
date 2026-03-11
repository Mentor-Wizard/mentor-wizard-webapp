<script setup>
defineProps({
  view: {
    type: String,
    default: 'grid',
  },
});
</script>

<template>
  <div
    class="overflow-hidden rounded-xl border border-gray-200 bg-white"
    :class="view === 'list' ? 'flex flex-row' : ''"
  >
    <div
      :class="
        view === 'list' ?
          'h-48 w-48 flex-shrink-0 animate-pulse bg-gray-200 sm:w-56'
        : 'aspect-[4/3] w-full animate-pulse bg-gray-200'
      "
    />
    <div
      class="flex flex-col p-4"
      :class="view === 'list' ? 'flex-1 justify-center' : ''"
    >
      <div class="mb-3 flex items-start justify-between gap-2">
        <div class="h-5 w-32 animate-pulse rounded bg-gray-200" />
        <div class="h-5 w-16 animate-pulse rounded bg-gray-200" />
      </div>
      <div class="mb-3 h-4 w-full animate-pulse rounded bg-gray-200" />
      <div class="mb-3 flex flex-wrap gap-2">
        <div class="h-6 w-16 animate-pulse rounded-full bg-gray-200" />
        <div class="h-6 w-20 animate-pulse rounded-full bg-gray-200" />
        <div class="h-6 w-14 animate-pulse rounded-full bg-gray-200" />
      </div>
      <div :class="view === 'list' ? 'mb-3 flex items-center gap-4' : ''">
        <div
          class="h-4 w-24 animate-pulse rounded bg-gray-200"
          :class="view === 'list' ? '' : 'mb-3'"
        />
        <div
          class="h-4 w-32 animate-pulse rounded bg-gray-200"
          :class="view === 'list' ? '' : 'mb-3'"
        />
      </div>
      <div
        class="mt-auto animate-pulse rounded-lg bg-gray-200"
        :class="view === 'list' ? 'h-10 w-32' : 'h-10 w-full'"
      />
    </div>
  </div>
</template>
