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

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Знайди ментора</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Шукай серед сотень перевірених експертів, фільтруй за спеціалізацією, ціною та рейтингом
          </p>
      </div>

      <div class="bg-white rounded-lg p-6 max-w-sm text-center shadow-sm justify-self-center">
          <div class="w-16 h-16 bg-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <CalendarIcon class="w-8 h-8 text-white"/>
          </div>

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Бронюй сесії</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Обирай зручний час у календарі ментора та бронюй індивідуальні або групові сесії
          </p>
      </div>

      <div class="bg-white rounded-lg p-6 max-w-sm text-center shadow-sm justify-self-end">
          <div class="w-16 h-16 bg-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
              <AcademicCapIcon class="w-8 h-8 text-white"/>
          </div>

          <h2 class="text-xl font-semibold text-gray-900 mb-3">Розвивайся</h2>

          <p class="text-gray-600 text-sm leading-relaxed">
              Отримуй знання, завдання та зворотній зв'язок від експертів галузі для швидкого зростання
          </p>
      </div>
    </div>

</template>
