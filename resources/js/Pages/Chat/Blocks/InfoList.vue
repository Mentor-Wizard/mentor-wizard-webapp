<script setup>
import { CalendarIcon } from '@heroicons/vue/24/solid/index.js';
import { UserIcon } from '@heroicons/vue/24/solid/index.js';
import { EllipsisVerticalIcon } from '@heroicons/vue/24/solid/index.js';
import { PencilIcon } from '@heroicons/vue/24/solid/index.js';
import { VideoCameraIcon } from '@heroicons/vue/24/solid/index.js';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/solid/index.js';
import { FolderIcon } from '@heroicons/vue/24/solid/index.js';
import { BoltIcon } from '@heroicons/vue/24/solid/index.js';
import { ShareIcon } from '@heroicons/vue/24/solid/index.js';
import { CodeBracketIcon } from '@heroicons/vue/24/solid/index.js';
import { CalendarDaysIcon } from '@heroicons/vue/24/solid/index.js';
import { ChartBarIcon } from '@heroicons/vue/24/solid/index.js';
import { NoSymbolIcon } from '@heroicons/vue/24/solid/index.js';
import { ArchiveBoxIcon } from '@heroicons/vue/24/solid/index.js';
import { ref } from 'vue';

import { useCaseFileType } from '../useCaseFileType.js';

const { getColorByFileName, getIconByFileName } = useCaseFileType();

// TODO - fake files. After connecting to the backend, you need to delete
const files = ref([
  {
    id: 1,
    name: 'ReactPatterns.pdf',
    created_at: '10.10.2025 10:26',
    url: '#',
  },
  {
    id: 2,
    name: 'ReactPatterns.vue',
    created_at: '10.10.2025 10:26',
    url: '#',
  },
  {
    id: 3,
    name: 'ReactPatterns.png',
    created_at: '10.10.2025 10:26',
    url: '#',
  },
  {
    id: 4,
    name: 'ReactPatterns.doc',
    created_at: '10.10.2025 10:26',
    url: '#',
  },
  {
    id: 4,
    name: 'ReactPatterns.zip',
    created_at: '10.10.2025 10:26',
    url: '#',
  },
]);
</script>

<template>
  <div class="relative mx-auto max-w-sm pb-4">
    <div class="flex justify-center">
      <div class="h-32 w-32 overflow-hidden rounded-full border-4 border-white">
        <img
          src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
          alt="David Miller"
          class="h-full w-full rounded-full object-cover"
        />
      </div>
    </div>

    <div class="mt-4 text-center">
      <h2 class="text-xl leading-tight font-bold text-gray-900">
        David Miller
      </h2>
      <p class="mt-1 text-base text-gray-600">Front-end Developer</p>
      <p class="mt-2 text-sm text-gray-500">Member since Oct 2022</p>
    </div>

    <div class="mt-6 flex justify-center space-x-4">
      <button
        class="flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-md transition duration-150 ease-in-out hover:bg-blue-700"
      >
        <CalendarIcon class="me-2 h-4 w-4" />
        Book Session
      </button>

      <button
        class="flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition duration-150 ease-in-out hover:bg-gray-50"
      >
        <UserIcon class="me-2 h-4 w-4 text-gray-600" />
        Profile
      </button>
    </div>
  </div>
  <section>
    <div class="mt-3 flex items-center font-semibold text-gray-700">
      <CalendarIcon class="me-2 h-5 w-5 text-blue-600" />
      <h5>Upcoming Sessions</h5>
    </div>

    <div class="rounded-lg border border-blue-100 bg-blue-50 p-2">
      <div class="flex items-start justify-between">
        <h5>Code Review Session</h5>
        <button class="rounded-full p-1">
          <EllipsisVerticalIcon
            class="h-6 w-6 text-gray-400 hover:text-gray-600"
          />
        </button>
      </div>
      <p class="mt-1 text-sm text-gray-600">
        Thursday, July 4 • 2:00 - 3:00 PM
      </p>
      <div class="mt-3 flex space-x-4 text-sm font-medium">
        <a href="#" class="flex items-center text-blue-600 hover:text-blue-800">
          <VideoCameraIcon class="me-1 h-4 w-4 text-blue-600" />
          Join
        </a>
        <a href="#" class="flex items-center text-gray-500 hover:text-gray-700">
          <PencilIcon class="me-1 h-4 w-4 text-gray-600" />
          Edit
        </a>
      </div>
    </div>
  </section>
  <section class="mt-2 space-y-4 border-t border-gray-200 pt-2">
    <div class="mb-3 flex items-center font-semibold text-gray-700">
      <FolderIcon class="me-2 h-6 w-6 text-blue-600" />
      <h3 class="text-lg">Shared Files</h3>
    </div>

    <div class="space-y-4">
      <div
        v-for="file in files"
        :key="file.id"
        class="flex items-center justify-between"
      >
        <div class="flex items-center">
          <component
            :is="getIconByFileName(file.name)"
            class="me-2 h-8 w-8"
            :class="getColorByFileName(file.name)"
          />
          <div>
            <p class="text-sm font-medium text-gray-800">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ file.created_at }}</p>
          </div>
        </div>
        <a :href="file.url" download class="text-gray-400 hover:text-gray-600">
          <ArrowDownTrayIcon class="h-4 w-4 text-gray-600" />
        </a>
      </div>
    </div>
  </section>

  <section class="mt-2 space-y-4 border-t border-gray-200 pt-2">
    <div class="mb-4 flex items-center font-semibold text-gray-700">
      <BoltIcon class="h-4 w-4 text-blue-600" />
      <h3>Quick Actions</h3>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <button
        class="flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50"
      >
        <ShareIcon class="me-1 h-4 w-4 text-gray-600" />
        Share Screen
      </button>

      <button
        class="flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50"
      >
        <CodeBracketIcon class="me-1 h-4 w-4 text-gray-600" />
        Code Snippet
      </button>

      <button
        class="flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50"
      >
        <CalendarDaysIcon class="me-1 h-4 w-4 text-gray-600" />
        Task
      </button>

      <button
        class="flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50"
      >
        <ChartBarIcon class="me-1 h-4 w-4 text-gray-600" />
        Progress
      </button>
    </div>
  </section>

  <section class="mt-2 space-y-4 border-t border-gray-200 pt-2">
    <div class="space-y-2 px-2 py-2">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-700">Mute notifications</p>
        <label
          for="toggle-mute"
          class="relative inline-flex cursor-pointer items-center"
        >
          <input
            id="toggle-mute"
            type="checkbox"
            value=""
            class="peer sr-only"
          />
          <div
            class="peer h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-blue-600 peer-focus:ring-4 peer-focus:ring-blue-300 peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
          ></div>
        </label>
      </div>

      <div class="flex cursor-pointer items-center justify-between">
        <p class="text-sm text-gray-700">Archive conversation</p>
        <button class="text-gray-400 hover:text-gray-600">
          <ArchiveBoxIcon class="h-4 w-4 text-gray-400" />
        </button>
      </div>

      <div class="flex cursor-pointer items-center justify-between">
        <p class="text-sm text-gray-700">Block user</p>
        <button class="text-gray-400 hover:text-gray-600">
          <NoSymbolIcon class="h-4 w-4 text-gray-400" />
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped></style>
