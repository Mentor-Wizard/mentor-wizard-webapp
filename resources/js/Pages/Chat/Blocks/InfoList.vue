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
import {ref} from "vue";
import {useCaseFileType} from "../useCaseFileType.js";

const {getColorByFileName, getIconByFileName} = useCaseFileType()

// TODO - fake files. After connecting to the backend, you need to delete
const files = ref([
  {id: 1, name: 'ReactPatterns.pdf', created_at: '10.10.2025 10:26', url: '#'},
  {id: 2, name: 'ReactPatterns.vue', created_at: '10.10.2025 10:26', url: '#' },
  {id: 3, name: 'ReactPatterns.png', created_at: '10.10.2025 10:26', url: '#'},
  {id: 4, name: 'ReactPatterns.doc', created_at: '10.10.2025 10:26', url: '#'},
  {id: 4, name: 'ReactPatterns.zip', created_at: '10.10.2025 10:26', url: '#'},
])

</script>

<template>
<div class="relative max-w-sm mx-auto pb-4">
    <div class="flex justify-center">
        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white">
            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="David Miller" class="w-full h-full object-cover rounded-full" />
        </div>
    </div>

    <div class="mt-4 text-center">
        <h2 class="text-xl font-bold text-gray-900 leading-tight">David Miller</h2>
        <p class="text-base text-gray-600 mt-1">Front-end Developer</p>
        <p class="text-sm text-gray-500 mt-2">Member since Oct 2022</p>
    </div>

    <div class="mt-6 flex justify-center space-x-4">
        <button class="flex items-center justify-center px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium shadow-md transition duration-150 ease-in-out">
            <CalendarIcon class="h-4 w-4 me-2" />
            Book Session
        </button>

        <button class="flex items-center justify-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition duration-150 ease-in-out">
            <UserIcon class="h-4 w-4 text-gray-600 me-2" />
            Profile
        </button>
    </div>
</div>
<section>
    <div class="flex items-center text-gray-700 font-semibold mt-3">
        <CalendarIcon class="h-5 w-5 me-2 text-blue-600" />
        <h5>Upcoming Sessions</h5>
    </div>

    <div class="bg-blue-50 p-2 rounded-lg border border-blue-100">
        <div class="flex justify-between items-start">
            <h5>Code Review Session</h5>
            <button class="p-1 rounded-full">
                <EllipsisVerticalIcon class="h-6 w-6 text-gray-400 hover:text-gray-600 " />
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-1">Thursday, July 4 • 2:00 - 3:00 PM</p>
        <div class="mt-3 flex space-x-4 text-sm font-medium">
            <a href="#" class="flex items-center text-blue-600 hover:text-blue-800">
                <VideoCameraIcon class="h-4 w-4 text-blue-600 me-1" />
                Join
            </a>
            <a href="#" class="flex items-center text-gray-500 hover:text-gray-700">
                <PencilIcon class="h-4 w-4 text-gray-600 me-1" />
                Edit
            </a>
        </div>
    </div>
</section>
<section class="mt-2 pt-2 border-t border-gray-200 space-y-4">
    <div class="flex items-center text-gray-700 font-semibold mb-3">
        <FolderIcon class="h-6 w-6 me-2 text-blue-600" />
        <h3 class="text-lg">Shared Files</h3>
    </div>

    <div class="space-y-4">
        <div v-for="file in files" :key="file.id" class="flex justify-between items-center">
            <div class="flex items-center">
                <component :is="getIconByFileName(file.name)" class="h-8 w-8 me-2" :class="getColorByFileName(file.name)" />
                <div>
                    <p class="text-sm text-gray-800 font-medium">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ file.created_at }}</p>
                </div>
            </div>
            <a :href="file.url" download class="text-gray-400 hover:text-gray-600">
                <ArrowDownTrayIcon class="h-4 w-4 text-gray-600" />
            </a>
        </div>
    </div>
</section>

<section class="mt-2 pt-2 border-t border-gray-200 space-y-4">
    <div class="flex items-center text-gray-700 font-semibold mb-4">
        <BoltIcon class="h-4 w-4 text-blue-600" />
        <h3>Quick Actions</h3>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <button class="flex items-center px-4 py-2 justify-center border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
            <ShareIcon class="h-4 w-4 text-gray-600 me-1" />
            Share Screen
        </button>

        <button class="flex items-center px-4 py-2 justify-center border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
            <CodeBracketIcon class="h-4 w-4 text-gray-600 me-1" />
            Code Snippet
        </button>

        <button class="flex items-center px-4 py-2 justify-center border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
            <CalendarDaysIcon class="h-4 w-4 text-gray-600 me-1" />
            Task
        </button>

        <button class="flex items-center px-4 py-2 justify-center border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
            <ChartBarIcon class="h-4 w-4 text-gray-600 me-1" />
            Progress
        </button>

    </div>
</section>

<section class="mt-2 pt-2 border-t border-gray-200 space-y-4">
    <div class="px-2 py-2 space-y-2">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-700">Mute notifications</p>
            <label for="toggle-mute" class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" value="" id="toggle-mute" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <div class="cursor-pointer flex justify-between items-center">
            <p class="text-sm text-gray-700">Archive conversation</p>
            <button class="text-gray-400 hover:text-gray-600">
                <ArchiveBoxIcon class="h-4 w-4 text-gray-400" />
            </button>
        </div>

        <div class="cursor-pointer flex justify-between items-center">
            <p class="text-sm text-gray-700">Block user</p>
            <button class="text-gray-400 hover:text-gray-600">
                <NoSymbolIcon class="h-4 w-4 text-gray-400" />
            </button>
        </div>
    </div>
</section>
</template>

<style scoped>

</style>