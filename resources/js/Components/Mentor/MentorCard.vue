<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  mentor: Object,
  view: {
    type: String,
    default: 'grid',
  },
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:shadow-lg"
    :class="view === 'list' ? 'flex flex-row' : 'flex flex-col'"
  >
    <div
      v-if="mentor.availability"
      class="absolute top-3 z-10 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
      :class="[
        view === 'list' ? 'left-3' : 'right-3',
        {
          'bg-green-100 text-green-800': mentor.availability === 'today',
          'bg-yellow-100 text-yellow-800': mentor.availability === 'tomorrow',
          'bg-red-100 text-red-800': mentor.availability === 'booked',
        },
      ]"
    >
      <span
        class="h-2 w-2 rounded-full"
        :class="{
          'bg-green-500': mentor.availability === 'today',
          'bg-yellow-500': mentor.availability === 'tomorrow',
          'bg-red-500': mentor.availability === 'booked',
        }"
      ></span>
      {{ mentor.availabilityLabel }}
    </div>

    <div
      :class="
        view === 'list'
          ? 'h-auto w-48 flex-shrink-0 sm:w-56'
          : 'aspect-[4/3] w-full'
      "
      class="overflow-hidden"
    >
      <img
        class="h-full w-full object-cover"
        :src="mentor.image"
        :alt="mentor.name"
      />
    </div>

    <div
      class="flex flex-col p-4"
      :class="view === 'list' ? 'flex-1 justify-center' : ''"
    >
      <div class="mb-3 flex items-start justify-between gap-2">
        <h3 class="text-lg leading-tight font-semibold text-gray-900">
          {{ mentor.name }}
        </h3>
        <p class="font-bold whitespace-nowrap text-blue-600">
          {{ mentor.currency.symbol }}{{ mentor.price }}/hr
        </p>
      </div>

      <p class="mb-3 text-sm leading-relaxed text-gray-600">
        {{ mentor.title }}
      </p>

      <div class="mb-3 flex flex-wrap gap-2">
        <span
          v-for="tag in mentor.tags.slice(0, 3)"
          :key="tag"
          class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
        >
          {{ tag }}
        </span>
        <span
          v-if="mentor.tags.length > 3"
          class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500"
        >
          +{{ mentor.tags.length - 3 }} more
        </span>
      </div>

      <div
        class="flex items-center gap-4"
        :class="view === 'list' ? 'mb-3 flex-wrap' : 'mb-3 flex-col items-start gap-1'"
      >
        <div class="flex items-center text-sm">
          <div
            class="stars relative inline-block h-5 w-[5rem] overflow-hidden text-gray-300"
            :aria-label="`Rating: ${mentor.rating} out of 5`"
            role="img"
          >
            &star;&star;&star;&star;&star;
            <div
              class="absolute top-0 left-0 h-full overflow-hidden text-yellow-400"
              :style="{ width: (mentor.rating / 5) * 100 + '%' }"
            >
              &star;&star;&star;&star;&star;
            </div>
          </div>
          <span class="ml-2 text-gray-700">
            {{ mentor.rating }} ({{ mentor.reviews }} reviews)
          </span>
        </div>

        <div class="text-sm text-gray-600">
          {{ mentor.experience }}+ years experience
        </div>
      </div>

      <Link
        :href="route('page.mentor', { mentor: mentor.slug })"
        class="mt-auto block rounded-lg bg-blue-600 py-2.5 text-center text-base font-medium text-white transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
        :class="view === 'list' ? 'w-auto self-start px-8' : 'w-full'"
      >
        View Profile
      </Link>
    </div>
  </div>
</template>
