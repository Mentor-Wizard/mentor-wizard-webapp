<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/16/solid';
import { CalendarIcon } from '@heroicons/vue/16/solid';
import { AcademicCapIcon } from '@heroicons/vue/16/solid';
</script>

<template>
  <div class="grid grid-cols-3 justify-items-start gap-4">
    <div class="max-w-sm rounded-lg bg-white p-6 text-center shadow-sm">
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-500"
      >
        <MagnifyingGlassIcon class="h-8 w-8 text-white" />
      </div>

      <h2 class="mb-3 text-xl font-semibold text-gray-900">Find a mentor</h2>

      <p class="text-sm leading-relaxed text-gray-600">
        Search among hundreds of verified experts, filter by specialization,
        price and rating
      </p>
    </div>

    <div
      class="max-w-sm justify-self-center rounded-lg bg-white p-6 text-center shadow-sm"
    >
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-500"
      >
        <CalendarIcon class="h-8 w-8 text-white" />
      </div>

      <h2 class="mb-3 text-xl font-semibold text-gray-900">Book a session</h2>

      <p class="text-sm leading-relaxed text-gray-600">
        Choose a convenient time on your mentor's calendar and book individual
        or group sessions
      </p>
    </div>

    <div
      class="max-w-sm justify-self-end rounded-lg bg-white p-6 text-center shadow-sm"
    >
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500"
      >
        <AcademicCapIcon class="h-8 w-8 text-white" />
      </div>

      <h2 class="mb-3 text-xl font-semibold text-gray-900">Develop yourself</h2>

      <p class="text-sm leading-relaxed text-gray-600">
        Get knowledge, challenges, and feedback from industry experts for rapid
        growth
      </p>
    </div>
  </div>
</template>
