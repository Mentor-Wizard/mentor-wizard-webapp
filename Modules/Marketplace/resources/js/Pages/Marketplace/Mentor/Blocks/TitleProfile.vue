<script setup>
import { BriefcaseIcon } from '@heroicons/vue/16/solid';
import { UserGroupIcon } from '@heroicons/vue/16/solid';
import { GlobeAltIcon } from '@heroicons/vue/16/solid';
import { ClockIcon } from '@heroicons/vue/16/solid';
import { BookmarkIcon } from '@heroicons/vue/16/solid';
import { StarIcon } from '@heroicons/vue/20/solid/index.js';

defineProps({
  data: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <main class="max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
    <div class="grid grid-cols-3 gap-4 py-6">
      <div class="relative w-full">
        <img
          :src="data.avatar"
          alt="mentor.profile.name"
          class="aspect-square w-full overflow-hidden rounded-2xl object-cover"
        />
        <div
          class="absolute top-2 right-2 flex items-center gap-2 rounded-full bg-green-200 px-3 py-1 text-sm font-medium text-green-800 shadow"
        >
          <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
          There are places today
        </div>
      </div>

      <div class="col-span-2 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between">
            <div>
              <div class="mb-1 flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">
                  {{ data.name }}
                </h1>
              </div>
              <p class="mb-3 text-gray-600">{{ data.title }}</p>
            </div>

            <div class="mb-4 flex items-center gap-2">
              <div class="flex items-center">
                <StarIcon
                  v-for="rating in [0, 1, 2, 3, 4]"
                  :key="rating"
                  :class="[
                    data.rating > rating ? 'text-yellow-400' : 'text-gray-200',
                    'size-5 shrink-0',
                  ]"
                  aria-hidden="true"
                />
                <span class="ms-2 font-semibold text-gray-900">{{
                  data.rating
                }}</span>
                <span class="ms-2 text-gray-500"
                  >({{ data.reviews }} reviews)</span
                >
              </div>
            </div>
          </div>
          <div class="mb-4 flex flex-wrap gap-2">
            <span
              v-for="(stack, index) in data.stacks"
              :key="index"
              class="rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800"
              >{{ stack }}</span
            >
          </div>

          <div class="mb-6 grid grid-cols-4 gap-4">
            <div class="flex items-center gap-2 text-gray-600">
              <BriefcaseIcon class="h-8 w-8 text-gray-500" />
              <div>
                <div class="text-sm text-gray-500">Experience</div>
                <div class="font-medium">{{ data.experience }}</div>
              </div>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
              <UserGroupIcon class="h-8 w-8 text-gray-500" />
              <div>
                <div class="text-sm text-gray-500">Number of students</div>
                <div class="font-medium">{{ data.mentiCount }}</div>
              </div>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
              <GlobeAltIcon class="h-8 w-8 text-gray-500" />
              <div>
                <div class="text-sm text-gray-500">Languages</div>
                <div
                  v-for="(language, index) in data.languages"
                  :key="index"
                  class="font-medium"
                >
                  {{ language }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
              <ClockIcon class="h-8 w-8 text-gray-500" />
              <div>
                <div class="text-sm text-gray-500">Responds in</div>
                <div class="font-medium">&lt; 2 hours</div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between">
          <div class="flex items-baseline gap-1">
            <span class="text-2xl font-bold text-blue-600"
              >from {{ data.rate }} {{ data.currency }}</span
            >
            <span class="text-gray-500">/hour</span>
          </div>
          <div class="flex gap-3">
            <button
              class="flex items-center gap-2 rounded-lg border border-blue-400 px-4 py-2 text-blue-600 transition-colors hover:bg-blue-50"
            >
              <BookmarkIcon class="h-5 w-5 fill-none stroke-blue-600" />
              Save Profile
            </button>

            <a
              v-if="data.mainProgramSlug"
              :href="route('pages.mentor.program.book', data.mainProgramSlug)"
              class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
            >
              Book Consultation
            </a>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
