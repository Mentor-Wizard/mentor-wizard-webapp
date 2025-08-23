<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div
    v-if="data.links.length > 3"
    class="mt-6 flex items-center justify-between border-t border-gray-200 px-4 sm:px-0"
  >
    <div class="-mt-px flex w-0 flex-1">
      <Link
        v-if="data.prev_page_url"
        :href="data.prev_page_url"
        preserve-scroll
        preserve-state
        class="inline-flex items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
        :class="{ 'pointer-events-none text-gray-300': !data.prev_page_url }"
      ></Link>
    </div>

    <div class="hidden md:-mt-px md:flex">
      <template v-for="(link, index) in data.links" :key="index">
        <Link
          v-if="link.url"
          :href="link.url"
          preserve-scroll
          preserve-state
          :class="[
            'inline-flex items-center border-t-2 px-4 pt-4 text-sm font-medium',
            link.active ?
              'border-indigo-500 text-indigo-600'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
          ]"
          v-html="link.label"
        />
        <span
          v-else
          class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-400"
          v-html="link.label"
        />
      </template>
    </div>

    <div class="-mt-px flex w-0 flex-1 justify-end">
      <Link
        v-if="data.next_page_url"
        :href="data.next_page_url"
        preserve-scroll
        preserve-state
        class="inline-flex items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
        :class="{ 'pointer-events-none text-gray-300': !data.next_page_url }"
      ></Link>
    </div>
  </div>
  <div
    class="mt-4 flex justify-between border-t border-gray-200 px-4 pt-4 md:hidden"
  >
    <Link
      v-if="data.prev_page_url"
      :href="data.prev_page_url"
      preserve-scroll
      preserve-state
      class="text-sm text-gray-700 hover:text-gray-900"
    >
      &laquo; Previous
    </Link>
    <span v-else class="text-sm text-gray-400">&laquo; Previous</span>

    <Link
      v-if="data.next_page_url"
      :href="data.next_page_url"
      preserve-scroll
      preserve-state
      class="text-sm text-gray-700 hover:text-gray-900"
    >
      Next &raquo;
    </Link>
    <span v-else class="text-sm text-gray-400">Next &raquo;</span>
  </div>
</template>
