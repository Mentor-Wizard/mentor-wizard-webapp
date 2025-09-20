<script setup>
import {MagnifyingGlassIcon} from "@heroicons/vue/16/solid";
import {CalendarIcon} from "@heroicons/vue/16/solid";
import {AcademicCapIcon} from "@heroicons/vue/16/solid";
</script>

<template>
    <div class="grid grid-cols-3 gap-4 justify-items-start">
      <div class="bg-white rounded-lg p-6 max-w-sm text-center shadow-sm">
          <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <MagnifyingGlassIcon class="w-8 h-8 text-white"/>
          </div>

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Find a mentor</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Search among hundreds of verified experts, filter by specialization, price and rating
          </p>
      </div>

      <div class="bg-white rounded-lg p-6 max-w-sm text-center shadow-sm justify-self-center">
          <div class="w-16 h-16 bg-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <CalendarIcon class="w-8 h-8 text-white"/>
          </div>

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Book a session</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Choose a convenient time on your mentor's calendar and book individual or group sessions
          </p>
      </div>

      <div class="bg-white rounded-lg p-6 max-w-sm text-center shadow-sm justify-self-end">
          <div class="w-16 h-16 bg-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <AcademicCapIcon class="w-8 h-8 text-white"/>
          </div>

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Develop yourself</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Get knowledge, challenges, and feedback from industry experts for rapid growth
          </p>
      </div>
    </div>

</template>
