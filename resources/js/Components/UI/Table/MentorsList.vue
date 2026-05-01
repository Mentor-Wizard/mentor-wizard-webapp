M
<script setup lang="ts">
import { EnvelopeIcon, UserIcon } from '@heroicons/vue/20/solid';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppPagination from '@/Components/Navigation/AppPagination.vue';

const page = usePage();
const mentors = computed(() => page.props.mentors ?? {});
</script>

<template>
  <div v-if="mentors['data'] && mentors['data'].length" class="justify-center">
    <ul
      role="list"
      class="grid grid-cols-1 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
    >
      <li
        v-for="mentor in mentors['data']"
        :key="mentor.email"
        class="col-span-1 flex flex-col divide-y divide-gray-200 rounded-lg bg-white text-center shadow-sm"
      >
        <div class="flex flex-1 flex-col p-8">
          <img
            class="mx-auto size-20 shrink-0 rounded-full"
            :src="mentor?.profile?.avatar"
            alt=""
          />
          <h3 class="mt-4 text-sm font-medium text-gray-900">
            {{ mentor.username }}
          </h3>
          <dl class="mt-1 flex grow flex-col justify-between">
            <dt class="sr-only">Title</dt>
            <dd class="text-sm text-gray-500">
              {{ mentor?.profile?.title }}
            </dd>
            <hr class="my-1 h-px border-0 bg-gray-200" />
            <dt class="sr-only">Description</dt>
            <dd class="text-sm text-gray-500">
              {{ mentor?.profile?.description }}
            </dd>
          </dl>
        </div>
        <div>
          <div class="mt-px flex divide-x divide-gray-200">
            <div class="flex w-0 flex-1">
              <a
                :href="`mailto:${mentor.email}`"
                class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-900"
              >
                <EnvelopeIcon
                  class="size-8 pl-2 text-gray-400"
                  aria-hidden="true"
                />
                Contact Mentor
              </a>
              <a
                v-if="mentor?.profile?.linkedin"
                :href="`${mentor.profile.linkedin}`"
                class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent bg-blue-100 py-4 text-sm font-semibold text-gray-900"
              >
                <UserIcon class="size-5 text-gray-400" aria-hidden="true" />
                Linked In
              </a>
            </div>
          </div>
        </div>
      </li>
    </ul>
    <div class="my-5 flex justify-center">
      <AppPagination :data="mentors" />
    </div>
  </div>
</template>
